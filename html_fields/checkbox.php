<?php
namespace ccn\lib\html_fields;

require_once(CCN_LIBRARY_PLUGIN_DIR . '/lib.php'); use \ccn\lib as lib;
require_once(CCN_LIBRARY_PLUGIN_DIR . '/log.php'); use \ccn\lib\log as log;

function render_HTML_checkbox($field, $options = []) {

    $field_default = [
        'id' => 'dummy_id',
        'label' => '<u>My CheckBox</u>',
        'required' => true,
        'value_true' => 'true',
    ];
    $field = array_merge($field_default, $field);

    $options_default = [
        'value'     => '',
    ];
    $options = array_merge($options_default, $options);

    // required ?
    $ifrequired = ($field['required']) ? ' required ' : '' ;

    // checked ?
    $ifchecked = ($options["value"] == $field['value_true']) ? 'checked' : '';

    return '<div class="form-check">

                <input class="ccnlib_post form-check-input" 
                    type="checkbox" 
                    value="'.$field['value_true'].'" 
                    id="'.$field['id'].'" 
                    name="'.$field['id'].'" 
                    '.$ifrequired.'
                    '.$ifchecked.'
                >

                <label class="form-check-label" for="'.$field['id'].'">
                    '.$field['label'].'
                </label>
            </div>';
}

?>