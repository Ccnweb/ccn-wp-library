<?php
namespace ccn\lib\html_fields;

require_once(CCN_LIBRARY_PLUGIN_DIR . '/lib.php'); use \ccn\lib as lib;

function render_HTML_textarea($field, $options = array()) {
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
        'rows' => 5,
        'html_label' => 'Textarea',
    );
    $field = lib\assign_default($field_default, $field);

    $options_default = array(
        'value' => '',
        'label' => 'placeholder', // = 'label', 'placeholder', ou 'both'
    );
    $options = lib\assign_default($options_default, $options);


    // == 2. == Paramètres HTML calculés
    // placeholder and label
    $ifplaceholder = (in_array($options['label'], array('placeholder', 'both'))) ? ' placeholder="'.$field['html_label'].'" ' : "";
    $iflabel = (in_array($options['label'], array('label', 'both'))) ? '<label for="'.$field['id'].'_field">'.$field['html_label'].'</label>' : '';


    // == 3. == Rendu HTML

    return '<div class="form-group">
        '.$iflabel.'
        <textarea   class="form-control ccnlib_post" 
                    id="'.$field['id'].'_field" 
                    rows="'.$field['rows'].'" 
                    '.$ifplaceholder.'>'
            .$options['value'].
        '</textarea>
      </div>';
}

?>