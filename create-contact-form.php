<?php

require_once 'create-cp-html-forms.php';

/**
 * Here we define a function that creates a contact form :
 * - a form shortcode to display the HTML form
 * - a backend to get the contact form data and send the email to the proper addresses
 * 
 */

function ccnlib_register_contact_form($options = array()) {
    $prefix = 'ccnlib';

    // this function will create automatically an HTML parsed version of the field 'ccnlib_key_message'
    $create_HTML_message = function($post_data) {
        return (isset($post_data['ccnlib_key_message'])) ? str_replace("\n", "<br>", $post_data['ccnlib_key_message']) : 'unknown key ccnlib_key_message';
    };

    $default_options = array(
        'shortcode_name' => 'contact', // le nom du shortcode final sera {shortcode_name}-show-form
        'label' => 'placeholder', // 'placeholder' || 'label' || 'both'
        'textarea_rows' => 5, // nombre de lignes dans la textarea du message
        'fields' => array('nom', 'prenom', 'email', 'message'), // les champs qui appraitront dans le formulaire de contact
        'send_email' => array(
            array(
                'addresses'     => array('web@chemin-neuf.org'),
                'subject'       => 'Nouvelle demande de contact de {{'.$prefix.'_key_firstname}} {{'.$prefix.'_key_name}}',
                'model'         => 'simple_contact.html',
                'model_args'    => array(
                        'title'     => 'Que le Seigneur vous donne sa paix !',
                        'subtitle'  => 'Louez Dieu en tous temps !',
                        'body'      => 'Voici la demande de contact :<br>
                                            <b>Prénom: </b>{{ccnlib_key_firstname}}<br>
                                            <b>Nom: </b>{{ccnlib_key_name}}<br>
                                            <b>Email: </b>{{ccnlib_key_email}}<br>
                                            {{message_HTML}}<br>',
                ),
                'computed_data' => array(
                    'message_HTML' => $create_HTML_message,
                ),
            )
        ),
    );
    $options = assign_default($default_options, $options);

    // pour chaque élément de $options['send_email'], on ajoute la computed_data 'message_HTML' si elle n'existe pas
    foreach ($options['send_email'] as &$element) {
        if (!isset($element['computed_data'])) $element['computed_data'] = array();
        if (!isset($element['computed_data']['message_HTML'])) $element['computed_data']['message_HTML'] = $create_HTML_message;
    }

    $all_fields = array(
        'prenom' => array( // Prénom
            'id' => $prefix.'_key_firstname',
            'description'  => "Person first name for contact form",
            'html_label' => 'Prénom',
            'type' => "text"
        ),
        'nom' => array( // Nom
            'id' => $prefix.'_key_name',
            'description'  => "Person name for contact form",
            'html_label' => 'Nom',
            'type' => "text"
        ),
        'email' => array( // Email
            'id' => $prefix.'_key_email',
            'description'  => "Email address for contact form",
            'html_label' => 'Email',
            'type' => "email"
        ),
        'message' => array( // Message
            'id' => $prefix.'_key_message',
            'type' => 'textarea',
            'html_label' => 'Votre message',
            'rows' => $options['textarea_rows'],
        )
    );

    $fields = array();
    foreach ($all_fields as $field_name => $field_options) {
        if (in_array($field_name, $options['fields'])) array_push($fields, $field_options);
    }

    // on enregistre le shortcode
    $action_name = $options['shortcode_name'];
    create_HTML_form_shortcode('', $prefix.'_'.$action_name, $options, $fields);

    // on crée le backend pour recevoir le POST du formulaire et envoyer le mail
    $options = array(
        'send_email' => $options['send_email'],
        'send_to_user' => $prefix.'_key_email',
        'create_post' => false
    );
    create_POST_backend('', $prefix, $action_name, $accepted_users = 'all', $fields, $options);
}
?>