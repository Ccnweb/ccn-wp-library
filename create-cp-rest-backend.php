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
// forms library
require_once(CCN_LIBRARY_PLUGIN_DIR . '/forms/lib.forms.php');
use \ccn\lib\html_fields as fields;

require_once('create-cp-html-fields.php');
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
     * 
     * ## SUMMARY
     * 1.a  Sanitize the inputs from $_POST (sanitized data is then stored in var $saniized)
     * 1.b  Add computed fields to the data (in var $sanitized)
     * 2.a  Check that unique fields are unique 
     * 2.b  Check that custom_validation functions are ok
     * 3.   Create a new post of type $cp_id
     * 4.   Send emails
     */

    $validation = new Ccn_Validator();
    $action_name = $prefix.'_'.$soft_action_name;
    $html_email_models_dir = CCN_LIBRARY_PLUGIN_DIR . '/email_models'; // TODO delete this (moved in send_email.php)

    $default_options = array(
        'post_status'           => 'private',   // any valid post_status is ok but useful values are 'publish' to make the post available to any one and 'private' to make it hidden (for example for subscriptions)
        'send_email'            => array(),     // (no email sent by default) array of arrays with elements like array('addresses' => array('coco@example.com'), 'subject' => 'id_of_subject_field', 'model' => 'path_to_html_email_model', 'model_args' => array('title' => 'Merci de nous contacter'))
        'send_to_user'          => '',          // if the email should be sent to the email address of the user, write here the id of the user email field
        'create_post'           => true,        // créer ou non un nouveau post de type $cp_id (normalement c'est oui, sauf pour les formulaire de contact par ex)
        'computed_fields'       => array(),     // associative array(meta_key => function($_POST)) that creates new fields for the new post
        'custom_validations'    => array(),     // associative array(meta_key => function($fields, $sanitized, $existing_posts)) that does additional checks on step 2., before posting anything
		'on_before_save_post'   => '',          // fonction custom, or list of custom functions executed just before saving a post
		'on_finish'             => '',          // fonction custom, exécutée juste avant de renvoyer la réponse du serveur
    );
    $options = array_merge($default_options, $options);
    
    $fields = fields\prepare_fields($fields);

    $backend_callback = function() use ($cp_id, $fields, $validation, $html_email_models_dir, $options) {
        $log_stack_location = 'create-cp-rest-backend.php > create_POST_backend > $backend_callback'; // this is the string included in the logs to indicate where the error came from
        $final_response = array('success' => true); // le json final qui sera renvoyé

        //log\info('POST DATA', $_POST); // uncomment this to see in info logs what data comes from the client

        // == 1.a == sanitize the inputs
        $sanitized = array();
        $meta_keys = array();
        $i=0;
        foreach ($fields as $f) {
            // case of REPEAT-GROUP
            if ($f['type'] == 'REPEAT-GROUP') {

                // TODO : add field validation also for repeat groups

                $group_id = $f['id'];
                $new = array();
                $field_ids_html = lib\array_flatten(array_map(function($el_field) {return fields\get_field_ids($el_field, true);}, $f['fields']));

                $group_post_values = lib\extract_fields($_POST, $field_ids_html);
                $new = lib\array_swap_chaussette($group_post_values);

                // on enlève les éléments de $new qui ont un champs requis qui est vide
                $mandatory_fields = get_required_fields($f);
                $new = array_filter($new, function($el) use ($mandatory_fields) {
                    $el_required = lib\extract_fields($el, $mandatory_fields);
                    return count(array_filter($el_required, function($v) {return $v == '';})) == 0;
                });

                $sanitized[$f['id']] = json_encode($new);

            // all other cases
            } else {
                $f_ids = fields\get_field_ids($f);

                foreach($f_ids as $f_id) {
                    if (isset($_POST[$f_id])) {
                        $res = $validation->isValidField($_POST[$f_id], $f); // TODO compléter la validation avec le field regex_pattern etc
                        $res['valid'] = $res['valid'] || empty($_POST[$f_id]);
                        if (!$res['valid']) {echo json_encode(array("success" => false, "errno" => $res['reason'], 'descr' => 'Invalid field '.$f_id.' : '. $res['descr'])); die();}
                        $sanitized[$f_id] = $_POST[$f_id];
                    }
                }
                $i++;
            }
        }
        // == 1.b == add computed fields
        if (!empty($options['computed_fields'])) {
            foreach ($options['computed_fields'] as $key => $fun) {
                if (is_callable($fun)) {
                    try {
                        $sanitized[$key] = $fun($sanitized);
                    } catch(Exception $e) {
                        log\error('CUSTOM_FUNCTION_FAILED', 'In '.$log_stack_location.' computed_field custom function failed for key='.$key.' and post_values='.json_encode($sanitized));
                        echo json_encode(array('success' => false, 'errno' => 'CUSTOM_FUNCTION_FAILED', 'descr' => 'computed_field custom function failed for key='.$key));
                        die();
                    }
                } else {
                    log\error('INVALID_CUSTOM_FUNCTION', 'In '.$log_stack_location.' custom function for key '.$key.' is not callable');
                }
            }
        }

        //log\info('sanitized', $sanitized); // uncomment this to log the $sanitized $_POST values sent by the client
        
        if (post_type_exists($cp_id)) {
            
            // == 2. == we verify that the unique fields are indeed unique
            // TODO check "nonces" too !!!
            // on récupère tous les posts de type $cp_id
            $liste_cposts = query_posts(array('post_type' => $cp_id));
            // we load all custom fields for each cpost
            $liste_cposts_customfields = array_map(function($post) {return get_post_meta($post->ID, '', true);}, $liste_cposts);
            
            // == 2.a == we check that unique fields are indeed unique
            foreach($fields as $f) {
                // TODO filter first $posts where isset($post[$f['id]])
                $customfields_vals = array_map(function($post) use ($f) {return $post[$f['id']][0];}, $liste_cposts_customfields);
                if (isset($f['unique']) && $f['unique']) {
                    if (in_array($sanitized[$f['id']], $customfields_vals)) {
                        log\error('DUPLICATE_POST_KEY', 'In '.$log_stack_location.' Une ressource avec l\'attribut '.$f['id'].'='.$sanitized[$f['id']].' existe déjà');
                        echo json_encode(array('success' => false, 'errno' => 'DUPLICATE_POST_KEY', 'descr' => 'Une ressource avec l\'attribut '.$f['id'].'='.$sanitized[$f['id']].' existe déjà'));
                        die();
                    }
                }
            }

            // == 2.b == we check additional custom validations
            foreach ($options['custom_validations'] as $fun_name => $custom_validation_fun) {
                if (is_callable($custom_validation_fun)) {
                    
                    // we try to execute the custom validation function
                    $res = false;
                    try {
                        $res = $custom_validation_fun($fields, $sanitized, $existing_posts);
                    } catch (Exception $e) {
                        log\error('CUSTOM_VALIDATION_FUNCTION_FAILED', 'In '.$log_stack_location.' for custom_validation_function "'.$fun_name.'"');
                        echo json_encode(array('success' => false, 'errno' => 'CUSTOM_VALIDATION_FUNCTION_FAILED', 'descr' => 'custom_validation_function failed name='.$fun_name));
                        die();
                    }

                    // we analyze the custom_validation result
                    if ($res !== true && (!isset($res['success']) || $res['success'] !== true)) {
                        $o = ['success' => false, 'errno' => 'CUSTOM_VALIDATION_ERROR', 'descr' => 'an error happened during custom validaton of post data'];
                        echo json_encode(array_merge($o, $res));
                        die();
                    }
                } else {
                    log\error('INVALID_CUSTOM_FUNCTION', 'In '.$log_stack_location.' : function "'.$fun_name.'" (type='.gettype($custom_validation_fun).') in custom_validations functions is not callable');
                }
            }

            // == 3. == on crée un post
            if (options['create_post']) {
				
                // we execute all the on_before_save_post functions
                if (!empty($options['on_before_save_post'])) {
                    if (!is_array($options['on_before_save_post'])) $options['on_before_save_post'] = array($options['on_before_save_post']);
                    foreach ($options['on_before_save_post'] as $fun) {

                        $final_response['on_before_save_post'] = array();
                        
                        if (function_exists($fun)){
                            $res = $fun($sanitized, $liste_cposts_customfields);
                            $final_response['on_before_save_post'][] = $res;
                            if (!isset($res) || !isset($res['success']) || $res['success'] !== true) {
                                $final_response['success'] = false;
                                echo json_encode($final_response);
                                die();
                            }
                        }

                    }
                }
				
                $args = array(
                    'post_type' => $cp_id,
                    'meta_input' => $sanitized
                );
                $args['post_title'] = (isset($sanitized['post_title'])) ? $sanitized['post_title'] : 'undefined';
                $args['post_status'] = (isset($sanitized['post_status']) && $validation->isValidPostStatus($sanitized['post_status'])['valid']) ? $sanitized['post_status'] : $options['post_status'];
                $res = 0;
                try {
                    $res = wp_insert_post($args);
                } catch(Exception $e) {
                    log\error('WP_INSERTION_FAILED_BRUTALLY', 'In '.$log_stack_location.' function wp_insert_post failed brutally. Message = '.$e->getMessage().'. With following argument : '.json_encode($args));
                    echo json_encode(array('success' => false, 'error' => 'POST_INSERTION_FAILED', 'descr' => 'Post insertion failed brutally (wp_insert_post), returned message : '.$e->getMessage()));
                    die();
                }
                
                if ($res == 0) {
                    log\error('WP_POST_INSERTION_FAILED', 'in '.$log_stack_location.' : post insertion failed '.$json_encode($res));
                    echo json_encode(
                        array_merge($final_reponse,
                        array('success' => false, 'errno' => 'POST_CREATION_FAILED', 'descr' => 'Impossible de créer un post de type '.$cp_id.' avec les paramètres fournis :(')
						)
					);
                    die();
                } else {
                    $final_response = array_merge($final_response, array('success' => true, 'id' => $res, 'create_post' => true, 'email' => false));
                }
            }
        } else {
            log\error('UNKNOWN_POST_TYPE', 'in '.$log_stack_location.' : post type '.$cp_id.' does not exist');
            $final_reponse = array_merge($final_reponse, array('success' => false, 'errno' => 'UNKNOWN_POST_TYPE', 'descr' => 'post type '.$cp_id.' does not exist'));
            echo json_encode($final_response); die();
        }

        if (count($options['send_email']) > 0) {

            // == 4. == on envoie un email
            $final_response['email'] = array();

            // we add additional {...}__pretty attribtues to $sanitized for dropdown and radio elements
            $pretty_mapper = array_map(function($f) {
                if (($f['type'] == 'radio' || $f['type'] == 'dropdown') && isset($f['options'])) {
                    return $f['options'];
                }
                return array();
            }, $fields);
            $pretty_mapper = lib\array_flatten($pretty_mapper);
            foreach ($sanitized as $key => $val) if (gettype($val) == 'string' && isset($pretty_mapper[$val])) $sanitized[$key."__pretty"] = $pretty_mapper[$val];

            foreach ($options['send_email'] as $email_obj) {
                // we send the email
                $send_result = email\send( 
                            $data           = $sanitized, 
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
		if (isset($options["on_finish"]) && function_exists($options["on_finish"])) {
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