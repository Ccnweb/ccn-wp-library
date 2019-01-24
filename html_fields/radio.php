<?php
namespace ccn\lib\html_fields;

require_once(CCN_LIBRARY_PLUGIN_DIR . '/lib.php'); use \ccn\lib as lib;

function render_HTML_radio($field, $options) {
    /**
     * Fabrique le code HTML pour créer un radio button
     * 
     * ## SOMMAIRE
     * 1. Gestion des options de rendu par défaut
     * 2. Calcul des paramètres HTML calculés
     * 3. Rendu HTML
     */

    // == 1. == Gestion des options de rendu
    $field_default = array(
        'id' => 'dummy_id',     // l'id du custom meta field correspondant (ou post_title etc...)
        //'init_val' => 'value1', // précise la valeur à checker par défaut à l'init
        'options' => array(
            'value1' => 'label1',
            'value2' => 'label2',
        ),
        'options_preciser' => array('value1'), // les id des options qui doivent avoir un champs 'input type="text"' en plus 
        'layout' => 'column', // 'row', 'column'
    );
    $field = lib\assign_default($field_default, $field);

    $options_default = array(
        'value' => '',
        'value_a_preciser' => array('a_preciser_key' => 'a_preciser_value'), // uniquement si le radio button a un ou des champs "à préciser"
    );
    $options = lib\assign_default($options_default, $options);


    // == 2. == PARAMS
    $ifinline = ($field['layout'] == 'row') ? ' form-check-inline' : ''; // display radio buttons in row (inline) or column


    // == 3. == HTML Bootstrap
    $html = '<div class="form-radio-container">';
    $compteur = 1;

    $custom_html_version = false; // est-ce que les $field['options'] sont du code HTML ou non

    foreach ($field['options'] as $value => $label) {
        $curr_id = $field['id'].'_field_'.$value;
        $ifchecked = ($value == $options['value']) ? 'checked': '';

        // options_preciser permet d'ajouter un champs texte pour préciser une option du radio button
        // l'id du champs à préciser est construit de la manière suivante : {$field['id']}_field_{$value}_preciser
        // TODO appeler plutôt une fonction create_HTML_input, ce sera plus propre que mettre en dur "<input ..."
        $ifvalue_a_preciser = (isset($options['value_a_preciser'][$curr_id.'_preciser'])) ? $options['value_a_preciser'][$curr_id.'_preciser'] : '' ;
        $if_a_preciser = (in_array($value, $field['options_preciser'])) ? '<input type="text" class="form-control" name="'.$curr_id.'_preciser" id="'.$curr_id.'_preciser" value="'.$ifvalue_a_preciser.'">' : '';

        $radio_option = '<input class="form-check-input" type="radio" name="'.$field['id'].'_field" id="'.$curr_id.'" value="'.$value.'" '.$ifchecked.'>
                        <label class="form-check-label" for="'.$curr_id.'">'.$label.'</label>';

        // si la radio option commence par '<', c'est du code HTML, on utilise le code HTML au lieu d'un label
        if (substr($label, 0, 1) == '<') {
            $custom_html_version = true;
            // $radio_option = $label;
            // TODO ajouter l'id qui va bien
        }

        $html .= '<div class="form-check'.$ifinline.'">
                    '.$radio_option.' 
                    '.$if_a_preciser.'
                </div>';
        $compteur++;
    }

    // on ajoute éventuellement un champs input caché et le code JS nécessaire
    if ($custom_html_version) {
        // TODO
    }

    $html .= '</div>';
    return $html;

}

function get_value_from_db_radio($post, $field) {
    /**
     * 
     */

    $curr_options = array();

    $value = get_post_meta($post->ID, $field["id"], true);

    $field_id_preciser = $field['id'].'_field_'.$value.'_preciser';
    if (metadata_exists('post', $post->ID, $field_id_preciser)) { // si mon post a bien un champs meta qui s'appelle $field_id_preciser
        $value_a_preciser = get_post_meta($post->ID, $field_id_preciser, true);
        if ($value_a_preciser) {
            $curr_options['value_a_preciser'] = array();
            $curr_options['value_a_preciser'][$field_id_preciser] = $value_a_preciser;
        }
    }

    return $curr_options;
}


function save_field_to_db_radio($field, $post_values) {
    /**
     * Sauve les champs envoyés par requête POST pour les fields de type radio
     * 
     * cette fonction est appelée dans create-custom-post-type.php > create_custom_post_savecbk()
     * 
     * elle doit renvoyer un array associatif de type array('meta_key_id' => 'meta_key_value')
     */

    $res = array();
    $field_id = $field['id'].'_field';

    // le champs radio en lui-même est sauvé par la fonction standard create_custom_post_savecbk()
    // mais si le radio button a un champs "à préciser", il faut renvoyer les identifiants et valeurs pour le sauver
    // son id est construit comme suit : {$field['id']}_field_{$value}_preciser)

    $field_a_preciser = $field_id.'_'.$post_values[$field_id].'_preciser';
    if (array_key_exists($field_a_preciser, $post_values)) {

        $key = $f['id'].'_field_'.$post_values[$field_id].'_preciser';
        $val = $post_values[$field_a_preciser];

        $res[$key] = $val;
    }

    return $res;
    
}

?>