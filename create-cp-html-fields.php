<?php

/**
 * README
 * 
 * Here are some helper functions related to HTML fields rendering, <input>, <date>, ...
 * used in create-custom-post-type.php for example
 * 
 */

function create_HTML_field($field, $options) {
    $default_options = array(
        'value' => '',
        'label' => 'label', // 'label', 'placeholder', 'both'
        'required' => false, // si le champs est requis ou non
    );
    $options = assign_default($default_options, $options);

    // case of simple HTML input elements
    if (is_convertible_in_HTML_input($field['type'])) {
        // regex
        $regex_pattern = (isset($field['regex_pattern'])) ? $field['regex_pattern'] : get_mytype_HTML_pattern($field['type']);
        $ifregex = ($regex_pattern != '') ? ' pattern="'.$regex_pattern.'" ' : '';
        
        // placeholder
        $ifplaceholder = (in_array($options['label'], array('placeholder', 'both'))) ? ' placeholder="'.$field['html_label'].'" ' : "";
        
        // date
        $ifdate = ($field['type'] == 'date') ? ' data-date-format="dd-mm-yyyy" data-language="fr"' : '';
        $ifdateclass = ($field['type'] == 'date') ? ' datepicker-here' : '';
        
        // specific html attributes
        $html_attributes = (isset($field['html_attributes'])) ? implode(' ', array_map_assoc($field['html_attributes'], function($k, $v) {return $k.'="'.str_replace('"','\"', $v).'"';})) : '';

        return '
        <input type="'.get_HTML_field_input_type($field['type']).'" 
            name="'.$field['id'].'_field" 
            id="'.$field['id'].'_field" 
            class="postbox'.$ifdateclass.'" 
            '.$ifdate.' 
            '.$ifplaceholder.'
            '.$ifregex.'
            '.(($options['required']) ? ' required ' : '' ).'
            value="'.$options['value'].'" 
            '.$html_attributes.' />
        ';

    // other cases for more complex inputs todo...
    } else {
        die("Cannot render type ".$field['type'].' in HTML');
    }
}

function is_convertible_in_HTML_input($mytype) {
    return in_array($mytype, array('text', 'password', 'email', 'postal_code', 'date', 'number'));
}

function get_mytype_HTML_pattern($mytype) {
    /**
     * returns a regex pattern as string to be used in HTML corresponding to the mytype 
     * (e.g. "postal_code" returns "[0-9]{5}")
     */
    if      ($mytype == "postal_code")  {return "[0-9]{5}";}
    else if ($mytype == "date")         {return "[0-9]{2}-[0-9]{2}-[0-9]{4}";}
    else {return "";}
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
        'number' => 'integer',
    );
    if (isset($corresp[$mytype])) return $corresp[$mytype];
    else return $mytype;
}

function get_HTML_field_input_type($mytype) {
    /**
     * Converts the "mytype" into an HTML input type 
     * (works only for mytypes that have are rendered in HTML input elements)
     */
    if (in_array($mytype, array('string', 'date', 'postal_code'))) {
        return 'text';
    } else { // email => email, 'number' => 'number'
        return $mytype;
    }
}

?>