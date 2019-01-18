<?php

require_once 'create-cp-html-forms.php';

/**
 * Here we define a function that creates a contact form :
 * - a form shortcode to display the HTML form
 * - a backend to get the contact form data and send the email to the proper addresses
 * 
 */

function ccnlib_register_contact_form($options = array()) {
    $prefix = 'ccnlib_contactform';

    $default_options = array(
        'shortcode_name' => 'contact', // le nom du shortcode final sera {shortcode_name}-show-form
        'textarea_rows' => 5, // nombre de lignes dans la textarea du message
        'fields' => array('nom', 'prenom', 'email', 'message'), // les champs qui appraitront dans le formulaire de contact
    );
    $options = assign_default($default_options, $options);

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
        'send_email' => array(
            'addresses' => array('carlo.bauge@gmail.com'),
            'subject' => 'Louez le Seigneur en tous temps !',
        ),
        'send_to_user' => '',
        'create_post' => false
    );
    create_POST_backend('', $prefix, $action_name, $accepted_users = 'all', $fields, $options);
}
?>