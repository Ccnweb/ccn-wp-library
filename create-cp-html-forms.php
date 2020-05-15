<?php

require_once('log.php'); use \ccn\lib\log as log;
require_once('lib.php'); use \ccn\lib as lib;
require_once(CCN_LIBRARY_PLUGIN_DIR . '/forms/lib.forms.php'); use \ccn\lib\html_fields as fields;

use function ccn\lib\php_console_log;

require_once('create-cp-html-fields.php');

function create_HTML_form_shortcode($cp_id, $action_name, $options, $fields, $steps = array()) {
    /**
     * Creates an HTML form and registers it as a shortcode
     * 
     * TODO: argument $cp_id is not used !
     * TODO: check that fields in each step or switch is unique !!!!!!
     * 
     * @param string $action_name   le nom d'action inclut souvent le prefix, e.g. "ccnbtc_inscrire"
     * @param array $steps          les steps du formulaire, un peu comme des metabox
     * 
     */

    $fields = fields\prepare_fields($fields);

    $default_options = array(
        'title' => '',
        'text_btn_submit' => 'Ok',  // The text of the final submit button
        'text_btn_previous' => 'Précédent',  // The text of the "previous" button
        'text_btn_next' => 'Suivant',  // The text of the "next" button
        'custom_classes' => array(), 
            /**
             * associative array that adds custom css classes to elements in the form.
             * e.g. array('step' => 'w-100') will add the class 'w-100' to each <fieldset> (each html <fieldset> element corresponds to a step in the form)
             * authorized keys are : 'step', 'switch', 'step_title', 'step_subtitle', 'button_submit', 'button_previous', 'button_next'
             */

        'fields' => array(), // éventuellement des options par défaut pour les fields, envoyées à create_HTML_field (TODO)
        'custom_logic_path' => '', // TODO chemin ABSOLU vers un fichier .js qui contient la liste des règles JS spécifiques pour les formulaires complexes
    );
    $options = lib\assign_default($default_options, $options);

    // If $step is empty, we create a single step
    if (empty($steps)) {
        $steps = array(
            array(
                'id' => sanitize_title($options['title'], str_replace(' ', '-', $options['title'])),
                'title' => $options['title'],
                'fields' => array_map(function($f) {return $f['id'];}, $fields),
            ),
        );
    }

    // On initialise le conteneur form
    $final_html = '<form id="'.$action_name.'_form" class="form-container">
                        {{step_points}}
                        {{html}}
                    </form>';

    // On ajoute la barre des steps
    $steps_ui_list = lib\array_add_field($steps, 'label', function($k, $s) {return (isset($s['label'])) ? $s['label'] : $s['title'] ;});
    $steps_ui_list[0]['active'] = 'active'; // this says "the first step should be the active step when you first load the form"
    $steps_ui_html = lib\array_map_template($steps_ui_list, '<li class="{{active}}">{{label}}</li>');
    $steps_points_html = (count($steps) > 1) ? '<ul class="ccnlib_progressbar">'.implode("\n", $steps_ui_html).'</ul>' : '';

    // Crée l'HTML de chaque step
    $steps_html = array();
    $rules = array(); $field_rules = array(); // les règles/conditions JS à construire (globales à un switch ou locales à un field)
    $compteur = 0;

    foreach ($steps as $step) {

        $if_step_custom_classes = lib\getif($options, 'custom_classes/step');
        $step_html = '<fieldset class="'.((count($steps) > 1) ? 'step' : '').' '.$if_step_custom_classes.'">{{fieldset_html}}</fieldset>';

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
            $fields_html .= '<div id="'.$switch_el_html_id.'" class="form-step-switch '.lib\getif($options, 'custom_classes/switch').'">';
            foreach ($step_fields as $field) {
                if (fields\is_showable_in($field, 'front_create')) {
                    // we use a unique field id
                    // $field['id'] .= '-_-' . $switch_el_html_id;
                    $fields_html .= create_HTML_field($field, $options['fields']);
                }
            }
            $fields_html .= '</div>';
        }

        $previous_next_buttons = '';
        if ($compteur > 0) $previous_next_buttons .= '<input type="button" name="previous" class="previous action-button-previous '.lib\getif($options, 'custom_classes/button_previous').'" value="'.$options['text_btn_previous'].'"/>';
        if ($compteur < count($steps)-1) $previous_next_buttons .= '<input type="button" name="next" class="next action-button '.lib\getif($options, 'custom_classes/button_next').'" value="'.$options['text_btn_next'].'"/>';
        if ($compteur == count($steps)-1) $previous_next_buttons .= '<div class="submit-btn-container"><button id="'.$action_name.'_submit" class="btn btn-primary ccnlib_submit_btn '.lib\getif($options, 'custom_classes/button_submit').'" type="button">'.$options['text_btn_submit'].'</button></div>';

        $elements = array(
            'title'     => '<h2 id="'.$step_title_id.'" class="fs-title '.lib\getif($options, 'custom_classes/step_title').'">{{title}}</h2>',
            'subtitle'  => '<h3 class="fs-subtitle '.lib\getif($options, 'custom_classes/step_subtitle').'">{{subtitle}}</h3>',
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
                //console.log("RULES", typeof rules, rules);
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

?>