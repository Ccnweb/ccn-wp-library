<?php

require_once(CCN_LIBRARY_PLUGIN_DIR . '/lib.php');

function render_HTML_textarea($field, $options = array()) {
    // == 1. == 
    $field_default = array(
        'id' => 'dummy_id',     // l'id du custom meta field correspondant (ou post_title etc...)
        'rows' => 5,
        'html_label' => 'Textarea',
    );
    $field = assign_default($field_default, $field);

    $options_default = array(
        'value' => '',
        'label' => 'placeholder', // = 'label', 'placeholder', ou 'both'
    );
    $options = assign_default($options_default, $options);


    // placeholder
    $ifplaceholder = (in_array($options['label'], array('placeholder', 'both'))) ? ' placeholder="'.$field['html_label'].'" ' : "";
    // label
    $iflabel = (in_array($options['label'], array('label', 'both'))) ? '<label for="'.$field['id'].'_field">Example textarea</label>' : '';

    return '<div class="form-group">
        '.$iflabel.'
        <textarea   class="form-control" 
                    id="'.$field['id'].'_field" 
                    rows="'.$field['rows'].'" 
                    '.$ifplaceholder.'>'
            .$options['value'].
        '</textarea>
      </div>';
}

?>