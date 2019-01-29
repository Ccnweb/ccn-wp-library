<?php

namespace ccn\lib\html_fields;

require_once(CCN_LIBRARY_PLUGIN_DIR . '/lib.php'); use \ccn\lib as lib;
require_once(CCN_LIBRARY_PLUGIN_DIR . '/html_fields/input.php');

function render_HTML_address($field, $options = array()) {
    /**
     * Construit un élément HTML avec Nom de la rue, code postal et ville
     * les ids pour nom et prenom seront $field['id'] avec un suffixe _street, _postalcode, _city
     * 
     * ## SOMMAIRE
     * TODO
     */

    // == 1. == Gestion des options
    $field_default = array(
        'id' => 'dummy_id',     // l'id du custom meta field correspondant
        'html_label' => array(
            'street' => 'Rue', 
            'postalcode' => 'Code postal',
            'city' => 'Ville'
        ),
        'required'  => true,
    );
    $field = lib\assign_default($field_default, $field);

    $options_default = array(
        'style'     => 'normal', // 'normal', ou 'collé', 
        'label'     => 'placeholder', // = 'label', 'placeholder', 'both'
        'value'     => array(
            'street' => '',
            'postalcode' => '',
            'city'
        ),
    );
    $options = lib\assign_default($options_default, $options);

    // == 2.a Rendu HTML de STREET ==
    $input_params = array(
        'id' => $field['id'].'_street',
        'required' => $field['required'],
        'html_label' => 'Rue',
    );
    $input_options = array(
        'style' => 'simple',
        'label' => $options['label'],
        'value' => (isset($options['value']['street'])) ? $options['value']['street'] : '',
    );
    $input_street = render_HTML_input($input_params, $input_options);

    // == 2.b Rendu HTML de POSTAL CODE ==
    $input_params['id'] = $field['id'].'_postalcode';
    $input_params['type'] = 'postal_code';
    $input_params['html_label'] = 'Code postal';
    $input_options['value'] = (isset($options['value']['postalcode'])) ? $options['value']['postalcode'] : '';
    $input_postalcode = render_HTML_input($input_params, $input_options);

    // == 2.c Rendu HTML de STREET ==
    $input_params['id'] = $field['id'].'_city';
    $input_params['type'] = 'text';
    $input_params['html_label'] = 'Ville';
    $input_options['value'] = (isset($options['value']['city'])) ? $options['value']['city'] : '';
    $input_city = render_HTML_input($input_params, $input_options);

    // == 3.a == STYLE = 'normal' 
    // Rendu HTML Bootstrap où les champs sont espacés mais en ligne (source : https://getbootstrap.com/docs/4.0/components/forms/#auto-sizing)
    
    $iflabel_street = (in_array($options['label'], array('label', 'both'))) ? '<label class="sr-only" for="'.$field['id'].'_street">'.$field['html_label']['street'].'</label>' : '';
    $iflabel_postalcode = (in_array($options['label'], array('label', 'both'))) ? '<label class="sr-only" for="'.$field['id'].'_postalcode">'.$field['html_label']['postalcode'].'</label>' : '';
    $iflabel_city = (in_array($options['label'], array('label', 'both'))) ? '<label class="sr-only" for="'.$field['id'].'_city">'.$field['html_label']['city'].'</label>' : '';

    $html = '<div class="form-row align-items-center">
                <div class="col-auto">
                    '.$iflabel_street.'
                    '.$input_street.'
                </div>
                <div class="col-auto">
                    '.$iflabel_postalcode.'
                    '.$input_postalcode.'
                </div>
                <div class="col-auto">
                    '.$iflabel_city.'
                    '.$input_city.'
                </div>
            </div>';
    if ($options['style'] == 'normal') return $html;
    
    return $html;
}


function get_value_from_db_address($post, $field, $single = true) {
    /**
     * Fonction qui récupère les données du champs dans la BDD de Wordpress
     * et renvoie la valeur dans un format compréhensible par render_HTML_nom_prenom()
     * 
     * cette fonction est appelée dans create-custom-post-type.php > create_custom_post_metabox()
     */

    $res = array('value' => array());
    foreach (['street', 'postalcode', 'city'] as $partie) {
        $res['value'][$partie] = get_post_meta($post->ID, $field["id"].'_'.$partie, $single);
    }
    return $res;
}

function save_field_to_db_address($field, $post_values) {
    /**
     * Sauve les champs envoyés par requête POST pour les fields de type nom_prenom
     * 
     * cette fonction est appelée dans create-custom-post-type.php > create_custom_post_savecbk()
     * 
     * elle doit renvoyer un array associatif de type array('meta_key_id' => 'meta_key_value')
     */

    $res = array();
    foreach (['street', 'postalcode', 'city'] as $partie) {
        if (array_key_exists($field['id'].'_'.$partie.'_field', $post_values)) {
            $res[$field['id'].'_'.$partie] = $post_values[$field['id'].'_'.$partie.'_field'];
        }
    }
    return $res;
    
}


?>