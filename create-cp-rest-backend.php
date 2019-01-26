<?php

/**
 * README
 * 
 * Here we défine useful functions to easily create HTML forms and REST backend at <?php admin_url('admin-ajax.php') ?>
 * responding to POST requests from the browser to create new posts
 * 
 */

require_once('lib.php'); use\ccn\lib as lib;
require_once('log.php'); use \ccn\lib\log as log;
// We require this for email/etc. validation
require_once('Ccn_Validator.php');
// This is for sending emails
require_once(CCN_LIBRARY_PLUGIN_DIR . '/email/send_email.php'); use \ccn\lib\email as email;


function create_POST_backend($cp_id, $prefix, $soft_action_name, $accepted_users = 'all', $fields, $options = array()) {
    /**
     * Creates a backend to receive POST requests from a form
	 * 
	 * @param string $cp_id
     * @param string $prefix            Prefix of the custom post type that will be used as prefix for the action_name
     * @param string $soft_action_name  A name that will be part of the final action_name. The POST body should contain an attribute 'action'=$action_name
     * @param string $accepted_users    Can be 'all' or 'loggedin' to know who can POST on this interface
     * @param string $fields            The custom post fields that should come in the POST request
     */

    $validation = new Ccn_Validator();
    $action_name = $prefix.'_'.$soft_action_name;
    $html_email_models_dir = CCN_LIBRARY_PLUGIN_DIR . '/email_models'; // TODO delete this (moved in send_email.php)

    $default_options = array(
        'send_email' => array(), // (no email sent by default) array of arrays with elements like array('addresses' => array('coco@example.com'), 'subject' => 'id_of_subject_field', 'model' => 'path_to_html_email_model', 'model_args' => array('title' => 'Merci de nous contacter'))
        'send_to_user' => '', // if the email should be sent to the email address of the user, write here the id of the user email field
        'create_post' => true, // créer ou non un nouveau post de type $cp_id (normalement c'est oui, sauf pour les formulaire de contact par ex)
		'on_before_save_post' => '', // fonction custom, exécutée juste avant de sauver le post
		'on_finish' => '', // fonction custom, exécutée juste avant de renvoyer la réponse du serveur
    );
    $options = lib\assign_default($default_options, $options);
    

    $backend_callback = function() use ($cp_id, $fields, $validation, $html_email_models_dir, $options) {
        $final_response = array('success' => true); // le json final qui sera renvoyé

        // == 1. == sanitize the inputs
        $sanitized = array();
        $meta_keys = array();
        $i=0;
        foreach ($fields as $f) {
            if (isset($_POST[$f['id']])) {
                $res = $validation->isValidField($_POST[$f['id']], $f['type']); // TODO ajouter la regex s'il y en a une et la valider
                if (!$res['valid']) {echo json_encode(array("success" => false, "errno" => $res['reason'], 'descr' => $res['descr'])); die();}
                $sanitized[$f['id']] = $_POST[$f['id']];
            }
            $i++;
        }

        
        if (post_type_exists($cp_id)) {
            
            // == 2. == on vérifie que les fields uniques sont bien uniques
            // on récupère tous les posts de type $cp_id
            $liste_inscriptions = query_posts(array('post_type' => $cp_id));
            $liste_inscriptions_customfields = array_map(function($post) {return get_post_meta($post->ID, '', true);}, $liste_inscriptions);
            // pour chaque champs qui doit être unique, on vérifie que la valeur du champs n'existe pas déjà parmi les posts existants
            foreach($fields as $f) {
                if (isset($f['unique']) && $f['unique']) {
                    $customfields_vals = array_map(function($post) use ($f) {return $post[$f['id']][0];}, $liste_inscriptions_customfields);
                    if (in_array($sanitized[$f['id']], $customfields_vals)) {
                        echo json_encode(array('success' => false, 'errno' => 'DUPLICATE_POST_KEY', 'descr' => 'Une ressource avec l\'attribut '.$f['id'].'='.$sanitized[$f['id']].' existe déjà'));
                        die();
                    }
                }
            }

            // == 3. == on crée un post
            if (options['create_post']) {
				
				// on exécute éventuellement une custom fonction on_before_save_post
				if (function_exists($options['on_before_save_post'])){
					$res = $options["on_before_save_post"]($sanitized);
					echo "####".json_encode(isset($res))."####";
					$final_response['on_before_save_post'] = $res;
					if (!isset($res) || !isset($res['success']) || $res['success'] !== true) {
						$final_response['success'] = false;
						echo json_encode($final_response);
						die();
					}
				}
				
                $args = array(
                    'post_type' => $cp_id,
                    'meta_input' => $sanitized
                );
                $args['post_title'] = (isset($_POST['post_title'])) ? $_POST['post_title'] : 'undefined';
                $args['post_status'] = (isset($_POST['post_status']) && $validation->isValidPostStatus($_POST['post_status'])['valid']) ? $_POST['post_status'] : 'publish';
                $res = wp_insert_post($args);

                if ($res == 0) {
                    echo json_encode(
						$final_response = array_merge($final_reponse,
							array('success' => false, 'errno' => 'POST_CREATION_FAILED', 'descr' => 'Impossible de créer un post de type '.$cp_id.' avec les paramètres fournis :(')
						)
					);
                    die();
                } else {
                    $final_response = array_merge($final_reponse, array('success' => true, 'id' => $res, 'create_post' => true, 'email' => false));
                }
            }
        }
        
        if (count($options['send_email'] > 0)) {

            // == 4. == on envoie un email
            $final_response['email'] = array();

            foreach ($options['send_email'] as $email_obj) {
                $send_result = email\send( 
                            $data           = $_POST, 
                            $to_addresses   = $email_obj['addresses'], 
                            $subject        = $email_obj['subject'],
                            $model          = $email_obj['model'],
                            $model_args     = $email_obj['model_args'],
                            $options        = array('computed_data' => $email_obj['computed_data'])
                        );
                $final_response['success'] = $send_result['success'];
                array_push($final_response['email'], $send_result);
            }
        }
		
		// == 5. == we execute a custom function if defined in options
		if (function_exists($options["on_finish"])) {
			$res = $options["on_finish"]($sanitized);
			$final_response['on_finish'] = $res;
			if (!isset($res) || !isset($res['success']) || $res['success'] !== true) {
				$final_response['success'] = false;
				echo json_encode($final_response);
				die();
			}
		}

        echo json_encode($final_response);
        die();
    };

    add_action('wp_ajax_'.$action_name, $backend_callback ); // for logged-in users
    if ($accepted_users == 'all') add_action('wp_ajax_nopriv_'.$action_name, $backend_callback ); // for non-logged-in users
}

?>