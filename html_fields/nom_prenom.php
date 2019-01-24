<?php
namespace ccn\lib\html_fields;

require_once(CCN_LIBRARY_PLUGIN_DIR . '/lib.php'); use \ccn\lib as lib;
require_once(CCN_LIBRARY_PLUGIN_DIR . '/html_fields/input.php');

function render_HTML_nom_prenom($field, $options = array()) {
    /**
     * Construit un élément HTML avec 2 text input en ligne, pour renseigner le nom et le prénom
     * les ids pour nom et prenom seront $field['id'].'_firstname' et $field['id'].'_name'
     * 
     * ## SOMMAIRE
     * 1. Gestion des options
     * 2. Rendu HTML des input séparement
     * 3. Rendu HTML 'simple' et 'normal'
     */

    // == 1. == Gestion des options
    $field_default = array(
        'id' => 'dummy_id',     // l'id du custom meta field correspondant
        'html_label' => 'Nom et prénom',
        'regex_pattern' => "[A-z çàéèùñòìêâûîëöüïÉ\x27-]{2,}", // remettre à "" si on ne veut pas de regex de validation
    );
    $field = lib\assign_default($field_default, $field);

    $options_default = array(
        'style'     => 'normal', // 'normal', ou 'collé', 
        'label'     => 'label', // = 'label', 'placeholder', 'both'
        'required'  => true,
        'value'     => array(
            'prenom' => '',
            'nom' => ''
        ),
    );
    $options = lib\assign_default($options_default, $options);
   

    // == 2. Rendu HTML des input séparement ==
    $input_params = array(
        'id' => $field['id'].'_firstname',
        'required' => $options['required'],
        'regex_pattern' => $field['regex_pattern'],
    );
    
    $input_options = array(
        'style' => 'simple',
        'label' => $options['label'],
        'value' => (isset($options['value']['prenom'])) ? $options['value']['prenom'] : 'missing',
    );

    $input_prenom = render_HTML_input($input_params, $input_options);
    $input_params['id'] = $field['id'].'_name';
    $input_options['value'] = (isset($options['value']['nom'])) ? $options['value']['nom'] : 'missing';
    $input_nom = render_HTML_input($input_params, $input_options);


    // == 3.a == STYLE = 'normal' 
    // Rendu HTML Bootstrap où les champs sont espacés mais en ligne (source : https://getbootstrap.com/docs/4.0/components/forms/#auto-sizing)
    
    $iflabel_prenom = (in_array($options['label'], array('label', 'both'))) ? '<label class="sr-only" for="'.$field['id'].'_firstname">Prénom</label>' : '';
    $iflabel_nom = (in_array($options['label'], array('label', 'both'))) ? '<label class="sr-only" for="'.$field['id'].'_name">Nom</label>' : '';

    $html = '<div class="form-row align-items-center">
                <div class="col-auto">
                    '.$iflabel_prenom.'
                    '.$input_prenom.'
                </div>
                <div class="col-auto">
                    '.$iflabel_nom.'
                    '.$input_nom.'
                </div>
            </div>';
    if ($options['style'] == 'normal') return $html;


    // == 3.b == STYLE = 'collé' 
    // Rendu HTML Bootstrap où les champs sont collés (source : https://getbootstrap.com/docs/4.0/components/input-group/#multiple-inputs)

    $iflabel = (in_array($options['label'], array('label', 'both'))) ? '<div class="input-group-prepend">
        <span class="input-group-text" id="'.$field['id'].'_label">'.$field['html_label'].'</span>
    </div>' : '';

    $html = '<div class="input-group">
        '.$iflabel.'
        '.$input_prenom.'
        '.$input_nom.'
    </div>';
    
    return $html;


}


function get_value_from_db_nom_prenom($post, $field) {
    /**
     * Fonction qui récupère les données du champs dans la BDD de Wordpress
     * et renvoie la valeur dans un format compréhensible par render_HTML_nom_prenom()
     * 
     * cette fonction est appelée dans create-custom-post-type.php > create_custom_post_metabox()
     */

    $value_prenom = get_post_meta($post->ID, $field["id"].'_firstname', true);
    $value_nom = get_post_meta($post->ID, $field["id"].'_name', true);

    return array(
        'value' => array(
            'prenom' => $value_prenom,
            "nom" => $value_nom
        )
    );
}

function save_field_to_db_nom_prenom($field, $post_values) {
    /**
     * Sauve les champs envoyés par requête POST pour les fields de type nom_prenom
     * 
     * cette fonction est appelée dans create-custom-post-type.php > create_custom_post_savecbk()
     * 
     * elle doit renvoyer un array associatif de type array('meta_key_id' => 'meta_key_value')
     */

    $res = array();

    if (array_key_exists($field['id'].'_firstname_field', $post_values)) {
        $res[$field['id'].'_firstname'] = $post_values[$field['id'].'_firstname_field'];
    }
    if (array_key_exists($field['id'].'_name_field', $post_values)) {
        $res[$field['id'].'_name'] = $post_values[$field['id'].'_name_field'];
    }

    return $res;
    
}

?>