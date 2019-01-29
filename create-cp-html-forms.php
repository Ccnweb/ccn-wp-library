<?php

require_once('log.php'); use \ccn\lib\log as log;
require_once('lib.php'); use \ccn\lib as lib;
require_once('create-cp-html-fields.php');

function create_HTML_form_shortcode($cp_id, $action_name, $options, $fields, $steps = array()) {
    /**
     * Creates an HTML form and registers it as a shortcode
     * 
     * TODO: argument $cp_id is not used !
     * 
     * @param string $action_name   le nom d'action inclut souvent le prefix, e.g. "ccnbtc_inscrire"
     * @param array $steps          les steps du formulaire, un peu comme des metabox
     * 
     */

    $fields = prepare_fields($fields);

    $default_options = array(
        'title' => '',
        'submit_btn_text' => 'Ok',
        'fields' => array(), // éventuellement des options par défaut pour les fields, envoyées à create_HTML_field (TODO)
        'computed_fields' => array(), // ici on définit les champs calculés, par ex 'post_title' => "() => getVal('wpsubs_key_name')"
        'custom_logic_path' => '', // chemin ABSOLU vers un fichier .js qui contient la liste des règles JS spécifiques pour les formulaires complexes
    );
    $options = lib\assign_default($default_options, $options);

    // On initialise le conteneur form
    $final_html = '<form id="msform">
                        {{step_points}}
                        {{html}}
                    </form>';

    // On ajoute la barre des steps
    $steps_ui_list = lib\array_add_field($steps, 'label', function($k, $s) {return (isset($s['label'])) ? $s['label'] : $s['title'] ;});
    $steps_ui_list[0]['active'] = 'active'; // this says "the first step should be the active step when you first load the form"
    $steps_ui_html = lib\array_map_template($steps_ui_list, '<li class="{{active}}">{{label}}</li>');
    $steps_points_html =   '<ul id="ccnlib_progressbar">'.implode("\n", $steps_ui_html).'</ul>';

    // Crée l'HTML de chaque step
    $steps_html = array();
    $rules = array(); $field_rules = array(); // les règles/conditions JS à construire (globales à un switch ou locales à un field)
    $compteur = 0;
    foreach ($steps as $step) {
        $step_html = '<fieldset>{{fieldset_html}}</fieldset>';

        $step_title_id = 'step-title-'.$step['id']; // l'id html de la balise de titre

        // si on n'a pas de switch, on se débrouille pour en avoir un
        if (!isset($step['switch'])) $step['switch'] = array($step);

        $fields_html = '';
        foreach ($step['switch'] as $switch_el) {
            
            $switch_el_html_id = 'switch-'.$switch_el['id']; // l'id html du block de switch au sein de se step

            // on récupère la liste des fields de ce switch
            //$step_fields = array_filter($fields, function($f) use ($switch_el) { return in_array($f['id'], $switch_el['fields']);});
            $step_fields = lib\array_choose($fields, 'id', $switch_el['fields']);

            // on récupère éventuellement les conditions JS à ajouter à la fin
            if (isset($switch_el['condition'])) {
                $new_rule = build_js_rule($switch_el_html_id, $switch_el['condition'], $fields);
                if (!empty($new_rule)) $rules[] = $new_rule;
            }
            if (isset($switch_el['field_conditions'])) $field_rules = array_merge($field_rules, $switch_el['field_conditions']);

            // on crée le HTML des fields
            $fields_html .= '<div id="'.$switch_el_html_id.'" class="form-step-switch">';
            foreach ($step_fields as $field) {
                $fields_html .= create_HTML_field($field, $options['fields']);
            }
            $fields_html .= '</div>';
        }

        $previous_next_buttons = '';
        if ($compteur > 0) $previous_next_buttons .= '<input type="button" name="previous" class="previous action-button-previous" value="Précédent"/>';
        if ($compteur < count($steps)-1) $previous_next_buttons .= '<input type="button" name="next" class="next action-button" value="Suivant"/>';

        $elements = array(
            'title'     => '<h2 id="'.$step_title_id.'" class="fs-title">{{title}}</h2>',
            'subtitle'  => '<h3 class="fs-subtitle">{{subtitle}}</h3>',
            'content'   => $fields_html.$previous_next_buttons,
        );

        $html = lib\build_html($elements, $step);

        $steps_html[] = lib\parseTemplateString($step_html, array('fieldset_html' => $html));
        $compteur++;
    }

    // on ajoute les règles JS comme si c'était un dernier step
    $rules = array_merge($rules, parse_js_condition('', array('field_conditions' => $field_rules), $fields));
    $steps_html[] = '<script type="text/javascript">
            jQuery(document).ready(function($) {

                let rules = '.json_encode($rules).';
                console.log("RULES", typeof rules, rules);
                load_custom_logic(rules);

            });
        </script>';

    $final_html = lib\parseTemplateString($final_html, array(
        "html" => implode("\n", $steps_html),
        "step_points" => $steps_points_html,
    ));


    // on enregistre le shortcode
    $render_html = function() use ($final_html) {
        return $final_html;
    };
    add_shortcode( $action_name.'-show-form', $render_html);
}

function create_HTML_form_shortcode_old($cp_id, $action_name, $options, $fields, $steps = array()) {
    /**
     * Creates an HTML form and registers it as a shortcode
     * 
     * TODO: argument $cp_id is not used !
     * 
     * @param string $action_name   le nom d'action inclut souvent le prefix, e.g. "ccnbtc_inscrire"
     * @param array $steps          
     * 
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
    $options = lib\assign_default($default_options, $options);

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