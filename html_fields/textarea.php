<?php

require_once(CCN_LIBRARY_PLUGIN_DIR . '/lib.php');

function render_HTML_textarea($field, $options = array()) {
    // == 1. == 
    $field_default = array(
        'id' => 'dummy_id',     // l'id du custom meta field correspondant (ou post_title etc...)
        'rows' => 5
    );
    $field = assign_default($field_default, $field);

    $options_default = array(
        'value' => '',
    );
    $options = assign_default($options_default, $options);

    return '<div class="form-group">
        <label for="'.$field['id'].'_field">Example textarea</label>
        <textarea class="form-control" id="'.$field['id'].'_field" rows="'.$field['rows'].'">'.$options['value'].'</textarea>
      </div>';
}

?>