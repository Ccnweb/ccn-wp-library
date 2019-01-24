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
    );
    $field = lib\assign_default($field_default, $field);

    $options_default = array(
        'value' => '',
        'label' => 'label', // = 'label', 'placeholder' (mais dans ce cas, placeholder ne fait rien, ça sert juste à ne pas mettre de label)
    );
    $options = lib\assign_default($options_default, $options);

    // == 2. == Paramètres HTML calculés
    $iflabel = ($options['label'] == 'label') ? '<div class="input-group-prepend">
                    <label class="input-group-text" for="'.$field['id'].'_field">'.$field['html_label'].'</label>
                </div>' : '';


    // == 3. == Rendu HTML Bootstrap

    $html = '<div class="input-group">
                '.$iflabel.'
                <select class="custom-select" name="'.$field['id'].'_field" id="'.$field['id'].'_field">';

    foreach ($field['options'] as $value => $label) {
        $ifselected = ($value == $options['value']) ? 'selected' : '';

        $html .= '<option value="'.$value.'" '.$ifselected.'>'.$label.'</option>';
    }
    
    $html .= '</select>
    </div>';

    return $html;
}

?>