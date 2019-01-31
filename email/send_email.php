<?php
namespace ccn\lib\email;

require_once(CCN_LIBRARY_PLUGIN_DIR . '/lib.php'); use\ccn\lib as lib;
require_once(CCN_LIBRARY_PLUGIN_DIR . '/log.php'); use \ccn\lib\log as log;

function send($data, $to_addresses, $subject, $model, $model_args = array(), $options = array()) {
    /**
     * Sends emails based on data provided
     * 
     * @param array $data           associative array representing the data that will be sent per email
     * @param array $to_addresses   email addresses to whom we should send the email. This can also be the name of a key in $data
     * @param string $subject       the subject of the email address. This can also be the name of a key in $data
     * @param string $model         either the path to the email template file OR the name of the template in the default directory OR the template itself
     * @param array $model_args     associative array that populates the email template/model defined in $model. 
     *                              (e.g. $model_args = array('title' => 'The Title') will replace all occurences of "{{title}}" by "The Title" in the email template)
     * ## SOMMAIRE
     * 0. On calcule les attributs calculés de data
     * 1. On parse les adresses email auxquelles il faut envoyer le mail
     * 2. On parse le sujet du mail
     * 3. On parse le contenu du message
     */

    $html_email_models_dir = CCN_LIBRARY_PLUGIN_DIR . '/email/email_models'; // default directory to find email templates/models

    $default_options = array(
        'computed_data' => array(), // associative array like array('field1' => function($input_data) {return $input_data['title'] . '!';})
                                    // this computes additional fields in $data. In the example above, 'field1' will be added to $data;
    );
    $options = lib\assign_default($default_options, $options);
    
    // == 0. == On calcule les computed data
    foreach ($options['computed_data'] as $new_field => $fonction) {
        $data[$new_field] = $fonction($data);
    }
                

    // == 1. == à qui on envoie l'email
    $to = array();
    for ($i = 0; $i < count($to_addresses); $i++) {
        $curr_email = filter_var($to_addresses[$i], FILTER_VALIDATE_EMAIL);
        if ($curr_email === false && isset($data[$to_addresses[$i]])) { 
            $curr_email = filter_var($data[$to_addresses[$i]], FILTER_VALIDATE_EMAIL);
            if ($curr_email === false) log\warning('INVALID_EMAIL_ADDRESS', $data[$to_addresses[$i]]);
            else array_push($to, $curr_email );
        } else if ($curr_email !== false) {
            array_push($to, $curr_email);
        } else {
            log\warning('INVALID_EMAIL_ADDRESS', 'Invalid email address : '.$to_addresses[$i]);
        }
    }
    

    // == 2. == avec quel sujet
    $subject = (isset($data[$subject])) ? $data[$subject]  : 'Nouveau message de '.get_site_url() ;
    // si ce n'est pas un id, c'est un sujet fixe prédéfini avec éventuellement des {{...}} pour des parties dynamiques dans le sujet
    $subject = lib\parseTemplateString($subject, $data); 
    

    // == 3. == et quel message
    // soit c'est $model elle-même le contenu du message
    $message = $model;
    // soit c'est un chemin vers le template :
    if (file_exists($model)) {
        $message = file_get_contents($model);
    // soit c'est le nom d'un template existant (comme simple_contact.html)
    } else if (file_exists($html_email_models_dir . '/' . $model)) {
        $message = file_get_contents($html_email_models_dir . '/' . $model);
    }

    // on parse le message/modèle au cas où il contient des {{...}}
    $model_args = lib\array_map_assoc($model_args, function($key, $val) use ($data) {
        if (is_callable($val)) return lib\parseTemplateString($val($data), $data);
        else if (gettype($val) == 'string') return lib\parseTemplateString($val, $data);
        else return log\warning('INVALID_EMAIL_MODEL_ARGUMENT', 'In send_email.php > send : invalid model_arg value for $key='.json_encode($key).' Value for this key is neither a function nor a string, it is a '.gettype($val), '');
    });
    $model_args = lib\assign_default($model_args, $data);
    $message = lib\parseTemplateString($message, $model_args);


    // == 4. == envoi du mail...
    $sent_successfully = false;
    if (count($to) > 0) $sent_successfully = wp_mail($to, $subject, $message); // https://developer.wordpress.org/reference/functions/wp_mail/

    // on renvoie soit le succès de l'envoi de mail soit on logge l'erreur
    if ($sent_successfully) {
        if (isset($final_response['success'])) $final_response['email'] = true;
        else $final_response = array('success' => true, 'to' => $to);
        return $final_response;
    } else {
        if (isset($final_response['success'])) {
            $final_response['success'] = false;
            $final_response['errno'] = 'EMAIL_SEND_FAILED';
            if (count($to) < 1) $final_response['errno'] = 'EMAIL_RECIPIENTS_EMPTY_OR_INVALID';
            $final_response['descr'] = 'Impossible to send an email to '.json_encode($to);
            log\error('in create-cp-rest-backend, sending email', 'Impossible to send an email to '.json_encode($to).' parsed from '.json_encode($to_addresses));
            return $final_response;
        } else {
            $error_msg = array('success' => false, 'errno' => 'EMAIL_SEND_FAILED', 'descr' => 'Impossible to send an email to '.json_encode($to));
            log\error('in create-cp-rest-backend, send email failed', $error_msg);
            return $error_msg;
        }
    }
}


?>