<?php

require_once('log.php'); use \ccn\lib\log as log;
require_once('lib.php'); use \ccn\lib as lib;
require_once('create-cp-html-fields.php');

function create_HTML_form_shortcode($cp_id, $action_name, $options, $fields) {
    /**
     * Creates an HTML form and registers it as a shortcode
     * 
     * TODO: argument $cp_id is not used !
     */

    $fields = prepare_fields($fields);

    $default_options = array(
        'title' => '',
        'submit_btn_text' => 'Ok',
        'label' => 'both', // 'label' || 'placeholder' indique si le label des champs et insérer en tant que <label> ou dans le placeholder
        'required' => array(), // list des id des champs requis
        'computed_fields' => array(), // ici on définit les champs calculé, par ex 'post_title' => "() => getVal('wpsubs_key_name')"
        'custom_logic_path' => '', // chemin ABSOLU vers un fichier .js qui contient la liste des règles JS spécifiques pour les formulaires complexes
    );
    $options = assign_default($default_options, $options);

    // on ajoute le shortcode avec l'HTML du formulaire

    // s'il y a un titre de défini dans les options, on donne un titre au formulaire
    $iftitle = ($options['title'] != '') ? '<h3 class="form-title">'.$options['title'].'</h3>' : '' ;
    // s'il y a des champs requis, on les récupère
    $required_fields = $options['required'];
    if (in_array('@ALL', $required_fields)) $required_fields = array_map(function($f) {return $f['id'];}, $fields);

    $html = $iftitle.'
        <form id="'.$action_name.'_form" class="form-container">';

    // pour chaque champs, on crée l'élément HTML correspondant
    foreach($fields as $f) {

        // ====================================
        // Cas des REPEAT-GROUP dynamiques 
        // (par exemple pour les infos 'enfants' où on peut ajouter un nb variable d'enfants)
        // ====================================
        if ($f['type'] == 'REPEAT-GROUP') {

            // TODO

        // ====================================
        //      CAS DES CHAMPS NORMAUX
        //  définis dans html_fields/
        // ====================================
        } else {

            $label = (array_key_exists('html_label', $f)) ? $f['html_label'] : $f['id']; // le label du champs html
            $iflabel = (in_array($options['label'], array('label', 'both'))) ? '<label for="'.$f['id'].'_field"></label>' : ''; // élément <label> affiché uniquement si $options['label'] = 'label'
            $iferror = '<div class="invalid-feedback">Le champs est invalide !</div>';

            $curr_options = array('label' => $options['label'], 'required' => in_array($f['id'], $required_fields));

            $html .= 
                '<div class="field-container">
                    '.$iflabel.' 
                    '.create_HTML_field($f, $curr_options).'
                    '.$iferror.'
                </div>';

        }
    }

    $html .= '
        <div class="submit-btn-container">
            <button id="'.$action_name.'_submit" class="btn btn-primary" type="button">'.$options['submit_btn_text'].'</button>
        </div>
    ';
    $html .= '</form>';


    // on injecte le javascript qu'il faut
    $js_script = '<script type="text/javascript">';
    $js_tpl_raw = file_get_contents(CCN_LIBRARY_PLUGIN_DIR . 'js/forms-template.js.tpl');

    // préparation des données à injecter
    $custom_data_attributes = lib\array_map_assoc($options['computed_fields'], function ($key, $value) {
        return "'{$key}_field': $value";
    });
    $fields_array = array_map(function($f) {return $f['id'];}, $fields);
    $fields_array = array_merge($fields_array, array_keys($options['computed_fields']));
    // import de la logique métier pour les formulaires complexes
    $custom_logic = ($options['custom_logic_path'] && file_exists($options['custom_logic_path'])) ? file_get_contents($options['custom_logic_path']) : '[];';

    // les données à injecter dans le js
    $data = array(
        'action_name'               => $action_name,
        'fields_array'              => '["'.implode('", "', $fields_array).'"]',
        'ajax_url'                  => admin_url( 'admin-ajax.php' ),
        'custom_data_attributes'    => "{".implode(',\n', $custom_data_attributes)."}",
        'logic_rules'               => $custom_logic,
    );

    $js_parsed = lib\parseTemplateString($js_tpl_raw, $data);
    $js_script .= $js_parsed.'</script>'; // TODO réécrire avec la fonction lib\get_js_script()

    $html .= $js_script;


    $render_html = function() use ($html) {
        return $html;
    };
    add_shortcode( $action_name.'-show-form', $render_html);


    // TODO create backend to send email on contact submit in frontend

}

?>