<?php
namespace ccn\lib\html_fields;

require_once(CCN_LIBRARY_PLUGIN_DIR . '/lib.php'); use \ccn\lib as lib;

function render_HTML_dropdown($field, $options = array()) {
    /**
     * Construit un élément HTML de type textarea
     * 
     * ## SOMMAIRE
     * 1. Gestion des options
     * 2. Calcul des paramètres HTML poru le rendu
     * 3. Rendu HTML
     */

    // == 1. == Gestion des options
    $field_default = array(
        'id' => 'dummy_id',     // l'id du custom meta field correspondant (ou post_title etc...)
        'html_label' => 'Dropdown',
        'options' => array(
            'value1' => 'label1',
            'value2' => 'label2',
        ),
        'required' => true,
    );
    $field = lib\assign_default($field_default, $field);

    $options_default = array(
        'value' => '',
        'label' => 'label', // = 'label', 'placeholder' (mais dans ce cas, placeholder ne fait rien, ça sert juste à ne pas mettre de label)
        'multiple' => ''
    );
    $options = lib\assign_default($options_default, $options);

    // == 2. == Paramètres HTML calculés

    // id HTML
    $field_id_html = $field['id'];//.'_field';
    if ($options['multiple'] != '') $field_id_html .= '_'.$options['multiple'];

    // name HTML
    $field_name_html = $field['id'];//.'_field';
    if ($options['multiple'] != '') $field_name_html .= '[]';

    $iflabel = ($options['label'] == 'label') ? '<div class="input-group-prepend">
                    <label class="input-group-text" for="'.$field_id_html.'">'.$field['html_label'].'</label>
                </div>' : '';

    $ifrequired = ($field['required']) ? 'required': '';


    // == 3. == Rendu HTML Bootstrap

    $html = '<div class="input-group">
                '.$iflabel.'
                <select class="ccnlib_post custom-select" name="'.$field_name_html.'" id="'.$field_id_html.'" '.$ifrequired.'>';

    foreach ($field['options'] as $value => $label) {
        $ifselected = ($value == $options['value']) ? 'selected' : '';

        $html .= '<option value="'.$value.'" '.$ifselected.'>'.$label.'</option>';
    }
    
    $html .= '</select>
    </div>';

    return $html;
}

?>