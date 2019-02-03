<?php
namespace ccn\lib\html_fields;

require_once(CCN_LIBRARY_PLUGIN_DIR . '/lib.php'); use \ccn\lib as lib;

/**
 * It is not necessary to use this type of field in configuration
 * This field type appears only in the admin edit post area and cannot be modified
 * It is added automatically to all new custom post types to uniquely identify them
 * The value of this field is automatically generated
 * Such field can be instanciated with :
 *          ['id' => {myid}, 'type' => 'reference']
 */

function render_HTML_reference($field, $options = array()) {

    $value = (isset($options['value'])) ? $options['value'] : '';

    return '<div class="ccnlib_field_reference">
                <label for="'.$field['id'].'">REFERENCE</label>
                <div id="'.$field['id'].'">'.$value.'</div>
            </div>';
}


?>