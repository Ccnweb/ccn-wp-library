<?php

/**
 * README
 * 
 * Here we défine useful functions to easily create HTML forms and REST backend at <?php admin_url('admin-ajax.php') ?>
 * responding to POST requests from the browser to create new posts
 * 
 */

// We require this for email/etc. validation
require_once('Ccn_Validator.php');

function create_POST_backend($cp_id, $prefix, $soft_action_name, $accepted_users = 'all', $fields, $options = array()) {
    /**
     * @param string $cp_id
     * @param string $prefix            Prefix of the custom post type that will be used as prefix for the action_name
     * @param string $soft_action_name  A name that will be part of the final action_name. The POST body should contain an attribute 'action'=$action_name
     * @param string $accepted_users    Can be 'all' or 'loggedin' to know who can POST on this interface
     * @param string $fields            The custom post fields that should come in the POST request
     */

    $validation = new Ccn_Validator();
    $action_name = $prefix.'_'.$soft_action_name;
    $html_email_models_dir = CCN_LIBRARY_PLUGIN_DIR . '/email_models';
    $final_response = ''; // le json final qui sera renvoyé

    $default_options = array(
        'send_email' => array(), // (no email sent by default) array of arrays with elements like array('addresses' => array('coco@example.com'), 'subject' => 'id_of_subject_field', 'model' => 'path_to_html_email_model', 'model_args' => array('title' => 'Merci de nous contacter'))
        'send_to_user' => '', // if the email should be sent to the email address of the user, write here the id of the user email field
        'create_post' => true, // créer ou non un nouveau post de type $cp_id (normalement c'est oui, sauf pour les formulaire de contact par ex)
    );
    $options = assign_default($default_options, $options);

    $backend_callback = function() use ($cp_id, $fields, $validation, $options) {
        // == 1. == sanitize the inputs
        $sanitized = array();
        $meta_keys = array();
        $i=0;
        foreach ($fields as $f) {
            if (isset($_POST[$f['id']])) {
                $res = $validation->isValidField($_POST[$f['id']], $f['type']);
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
                $args = array(
                    'post_type' => $cp_id,
                    'meta_input' => $sanitized
                );
                $args['post_title'] = (isset($_POST['post_title'])) ? $_POST['post_title'] : 'undefined';
                $args['post_status'] = (isset($_POST['post_status']) && $validation->isValidPostStatus($_POST['post_status'])['valid']) ? $_POST['post_status'] : 'publish';
                $res = wp_insert_post($args);

                if ($res == 0) {
                    echo json_encode(array('success' => false, 'errno' => 'POST_CREATION_FAILED', 'descr' => 'Impossible de créer un post de type '.$cp_id.' avec les paramètres fournis :('));
                    die();
                } else {
                    $final_response = array('success' => true, 'id' => $res, 'create_post' => true, 'email' => false);
                }
            }
        }
        
        if (count($options['send_email'] > 0)) {

            // == 4. == on envoie un email éventuellement
            foreach ($options['send_email'] as $email_obj) {
                
                // à qui on envoie l'email
                $email_addresses = $email_obj['addresses'];
                $to = array();
                for ($i = 0; $i < count($email_addresses); $i++) {
                    $curr_email = filter_var($email_addresses[$i], FILTER_VALIDATE_EMAIL);
                    if ($curr_email === false && isset($_POST[$email_addresses[$i]])) { 
                        // TODO refaire un filter_var email ?
                        array_push($to, $_POST[$email_addresses[$i]] );
                    } else if ($curr_email !== false) {
                        array_push($to, $curr_email);
                    } // TODO log if invalid email
                }
                
                // avec quel sujet
                $subject = ($email_obj['subject'] != '' && isset($_POST[$email_obj['subject']])) ? $_POST[$email_obj['subject']]  : 'Nouveau message de '.get_site_url() ;
                // si ce n'est pas un id, c'est un sujet fixe prédéfini avec éventuellement des {{...}} pour des parties dynamiques dans le sujet
                if ($email_obj['subject'] != '' && !isset($_POST[$email_obj['subject']])) $subject = parseTemplateString($email_obj['subject'], $_POST); 
                
                // et quel message
                $message = 'Lodate Dio !';
                if ($email_obj['model']) {
                    // soit c'est un chemin vers le template :
                    if (file_exists($email_obj['model'])) {
                        $message = file_get_contents($email_obj['model']);
                    // soit c'est le nom d'un template existant (comme simple_contact.html)
                    } else if (file_exists($html_email_models_dir . '/' . $email_obj['model'])) {
                        $message = file_get_contents($html_email_models_dir . '/' . $email_obj['model']);
                    // soit c'est lui-même le contenu du message
                    } else {
                        $message = $email_obj['model'];
                    }

                    // on parse le message/modèle au cas où il contient des {{...}}
                    $model_args = (isset($email_obj['model_args'])) ? assign_default($email_obj['model_args'], $_POST) : $_POST;
                    $message = parseTemplateString($message, $model_args);
                }

                // envoi du mail...
                $sent_successfully = wp_mail($to, $subject, $message); // https://developer.wordpress.org/reference/functions/wp_mail/

                if ($sent_successfully) {
                    if (isset($final_response['success'])) $final_response['email'] = true;
                    else $final_response = array('success' => true, 'id' => 'unknown', 'create_post' => 'unknown', 'email' => $to);
                } else {
                    if (isset($final_response['success'])) {
                        $final_response['success'] = false;
                        $final_response['errno'] = 'EMAIL_SEND_FAILED';
                        $final_response['descr'] = 'Impossible to send an email to '.json_encode($to);
                        echo json_encode($final_response);
                    } else {
                        echo json_encode(array('success' => false, 'errno' => 'EMAIL_SEND_FAILED', 'descr' => 'Impossible to send an email to '.json_encode($to)));
                    }
                    die();
                }
            }
        }

        echo json_encode($final_response);
        die();
    };

    add_action('wp_ajax_'.$action_name, $backend_callback ); // for logged-in users
    if ($accepted_users == 'all') add_action('wp_ajax_nopriv_'.$action_name, $backend_callback ); // for non-logged-in users
}

?>