<?php
namespace ccn\lib\html_fields;

require_once(CCN_LIBRARY_PLUGIN_DIR . '/log.php'); use \ccn\lib\log as log;
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
        'required' => true,
        'options_preciser' => array('value1*'), // les id des options qui doivent avoir un champs 'input type="text"' en plus. 
                                                // Lorsque le nom est suivi d'une "*" c'est qu'ils sont requis si l'option est sélecitonnée
        'layout' => 'column', // 'row', 'column'
    );
    $field = lib\assign_default($field_default, $field);

    $options_default = array(
        'value' => array(
            'option' => '',
            'a_preciser' => array('a_preciser_key' => 'a_preciser_value'), // uniquement si le radio button a un ou des champs "à préciser"
        ),
        'multiple'  => '', // indice qui indique de la combien-ième instance il s'agit
    );
    $options = lib\assign_default($options_default, $options);

    // on va plus loin pour vérifier que $options['value'] a le bon format
    if (isset($options['value'])) {
        $options['value'] = lib\assign_default($options_default['value'], $options['value']);
        if ($options['value'] === false) {
            log\error("INVALID_RADIO_OPTION_VALUE", 'in radio.php : $options["value"] is invalid, fallback to default. Details : $options='.json_encode($options));
            $options['value'] = $options_default['value'];
        }
    }


    // == 2. == PARAMS
    $ifinline = ($field['layout'] == 'row') ? ' form-check-inline' : ''; // display radio buttons in row (inline) or column

    // options preciser
    $required_preciser = array();
    for ($i = 0; $i < count($field['options_preciser']); $i++) {
        $v = $field['options_preciser'][$i];
        if (substr($v, -1) == '*') {
            $field['options_preciser'][$i] = substr($v, 0, -1);
            $required_preciser[$field['options_preciser'][$i]] = true;
        } else $required_preciser[$v] = false;
    }

    // == 3. == HTML Bootstrap
    $html = '<div class="form-radio-container ccnlib_post" id="'.$field['id'].'">';
    $compteur = 1;

    $custom_html_version = false; // est-ce que les $field['options'] sont du code HTML ou non

    $field_name_html = $field['id'];
    if ($options['multiple'] != '') $field_name_html .= '[]';

    foreach ($field['options'] as $value => $label) {
        // l'id HTML du field
        $curr_id = $field['id'].'_field_'.$value;
        if ($options['multiple'] != '') $curr_id .= '_'.$options['multiple'];

        $ifchecked = ($value == $options['value']['option']) ? 'checked': '';
        $ifrequired = ($field['required']) ? 'required': '';

        // options_preciser permet d'ajouter un champs texte pour préciser une option du radio button
        // l'id du champs à préciser est construit de la manière suivante : {$field['id']}_field_{$value}_preciser
        // TODO appeler plutôt une fonction create_HTML_input, ce sera plus propre que mettre en dur "<input ..."
        $ifvalue_a_preciser = (isset($options['value']['a_preciser'][$curr_id.'_preciser'])) ? $options['value']['a_preciser'][$curr_id.'_preciser'] : '' ;
        if ($ifvalue_a_preciser != '' && $options['multiple'] != '') $ifvalue_a_preciser .=  '_'.$options['multiple'];
        
        // id html preciser
        $field_id_preciser = $curr_id.'_preciser';
        if ($options['multiple'] != '') $field_id_preciser .= '_'.$options['multiple'];
        // name html preciser
        $field_name_preciser = $curr_id.'_preciser';
        if ($options['multiple'] != '') $field_name_preciser .= '[]';

        $if_a_preciser = (in_array($value, $field['options_preciser'])) ? '<input type="text" class="form-control ccnlib_post '.( ($required_preciser[$value]) ? 'preciser_required' : '' ).'" name="'.$field_name_preciser.'" id="'.$field_id_preciser.'" value="'.$ifvalue_a_preciser.'">' : '';

        $radio_option = '<input class="form-check-input" type="radio" name="'.$field_name_html.'" id="'.$curr_id.'" value="'.$value.'" '.$ifchecked.' '.$ifrequired.'>
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







function get_field_ids_radio($field, $html = false) {
    /**
     * Fonction qui renvoie les ids des meta_key de ce field ou des ids des field HTML
     */

    $ids = [$field['id']];
    if ($html) $ids = [$field['id']];
    if (!isset($field['options_preciser'])) return $ids;

    foreach ($field['options_preciser'] as $id_preciser) {
        $curr_id = $field['id'].'_field_'.$id_preciser.'_preciser';
        array_push($ids, $curr_id);
    }

    return $ids;
}

function get_field_names_radio($field) {
    /**
     * Fonction qui renvoie les ids des meta_key de ce field ou des ids des field HTML
     */

    return ['option', 'a_preciser'];
}


function get_value_from_db_radio($post, $field, $single = true) {
    /**
     * 
     */

    $value = get_post_meta($post->ID, $field["id"], $single);
    $curr_options = array('value' => 
        array('option' => $value)
    );

    $field_id_preciser = $field['id'].'_field_'.$value.'_preciser';
    if (metadata_exists('post', $post->ID, $field_id_preciser)) { // si mon post a bien un champs meta qui s'appelle $field_id_preciser
        $value_a_preciser = get_post_meta($post->ID, $field_id_preciser, $single);
        if ($value_a_preciser) {
            $curr_options['value']['a_preciser'] = array();
            $curr_options['value']['a_preciser'][$field_id_preciser] = $value_a_preciser;
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
    $field_html_id = $field['id'];//.'_field';
    $field_html_name = $field['id'];

    // le champs radio en lui-même est sauvé par la fonction standard create_custom_post_savecbk()
    // mais si le radio button a un champs "à préciser", il faut renvoyer les identifiants et valeurs pour le sauver
    // son id est construit comme suit : {$field['id']}_field_{$value}_preciser)

    if (!isset($post_values[$field_html_name])) {
        //log\error('MISSING_ARRAY_KEY', 'In radio.php > save_field_to_db_radio : cannot find key '.$field_html_name. ' in array '.json_encode($post_values));
        return $res;
    }

    $field_a_preciser = $field['id'].'_'.$post_values[$field_html_name].'_preciser';
    if (array_key_exists($field_a_preciser, $post_values)) {

        $key = $field['id'].'_field_'.$post_values[$field_id].'_preciser';
        $val = $post_values[$field_a_preciser];

        $res[$key] = $val;
    }

    return $res;
    
}

?>