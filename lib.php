<?php

/* ==================================== */
/*           STRING PARSING             */
/* ==================================== */

function parseTemplateString($raw_str, $data) {
    /**
     * replace containers like {{coco}} in $raw_str by the value of $data['coco']
     * 
     * @param string $raw_str   The raw string containing containers like {{coco}}
     * @param string $data      The assoc. array containing the data to be inserted in $raw_str
     * 
     * @return string The string $raw_str parsed with data from $data
     */

    $parsed_str = $raw_str;
    foreach ($data as $key => $value) {
        $parsed_str = str_replace('{{'.$key.'}}', $value, $parsed_str);
    }
    return $parsed_str;
}


/* ==================================== */
/*     LOW-LEVEL USEFUL FUNCTIONS       */
/* ==================================== */

function array_map_assoc($arr, $cbk) {
    /**
     * Comme array_map mais pour les tableaux associatifs
     * 
     * @param array $arr    The associative array that will be tranformed
     * @param function $cbk The function to be applied to each element of $arr. cbk($key, $value)
     */

    $new_arr = array();
    foreach ($arr as $key => $value) {
        $new_arr[$key] = $cbk($key, $value);
    }
    return $new_arr;
}

// It assigns values of $el2 to $el1.
// $el1 and $el2 are assoc arrays
function assign_default($el1, $el2) {
    foreach ($el2 as $k2 => $v2) {
        $el1[$k2] = $v2;
    }
    return $el1;
}

// extracts the fields in $fields from the assoc array $arr
function extract_fields($arr, $fields) {
    $new_arr = array();
    foreach ($fields as $field) {
        $new_arr[$field] = $arr[$field];
    }
    return $new_arr;
}

?>