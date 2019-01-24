<?php

/**
 * README
 * 
 * Here are some helper functions related to HTML fields rendering, <input>, <date>, ...
 * used in create-custom-post-type.php for example
 * 
 * accepted field types = number, text, password, email, postal_code, date, tel, radio
 * 
 */

require_once('lib.php'); use \ccn\lib as lib;

// require all html field partial renderers from "html_fields" folder
lib\require_once_all_regex(CCN_LIBRARY_PLUGIN_DIR . '/html_fields/');
use \ccn\lib\html_fields as fields;

function create_HTML_field($field, $options) {
    $default_options = array(
        'value' => '', // default initial value
        'label' => 'label', // = 'label', 'placeholder', ou 'both'
        'required' => false, // si le champs est requis ou non
    );
    $options = lib\assign_default($default_options, $options);

    // case of simple HTML input elements
    if (in_array($field['type'], array('text', 'password', 'email', 'postal_code', 'date', 'number', 'tel'))) {
        return fields\render_HTML_input($field, $options);
    // case of complex HTML elements
    } else if (function_exists('\ccn\lib\html_fields\render_HTML_'.$field['type'])) {
        $res = call_user_func('\ccn\lib\html_fields\render_HTML_'.$field['type'], $field, $options);
        if ($res === false) return '<div class="html_rendering_error">Impossible de faire le rendu d\'un champs '.$field['type'].' (id = "'.$field['id'].'")</div>';
        return $res;
    } else {
        die("Cannot render type ".$field['type'].' in HTML');
    }
}


function get_wordpress_custom_field_type($mytype) {
    /**
     * This returns the wordpress type from the mytype (e.g. 'email' => 'string')
     * Valid values are 'string', 'boolean', 'integer', and 'number'.
     */
    $corresp = array(
        'date' => 'string',
        'email' => 'string',
        'postal_code' => 'string',
        'text' => 'string',
        'tel' => 'string',
        'number' => 'integer',
        'radio' => 'string',
        'dropdown' => 'string',
    );
    if (isset($corresp[$mytype])) return $corresp[$mytype];
    else return 'string';
}

function get_HTML_field_input_type_old($mytype) { // TODO delete this
    /**
     * Converts the "mytype" into an HTML input type 
     * (works only for mytypes that have are rendered in HTML input elements)
     */
    if (in_array($mytype, array('string', 'date', 'postal_code'))) {
        return 'text';
    } else { // email => email, 'number' => 'number', 'tel' => 'tel', 'radio' => 'radio'
        return $mytype;
    }
}

?>