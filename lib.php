<?php
namespace ccn\lib;

/* ==================================== */
/* CRÉE UN TAG <script> pour injecter du JS qqe part */
/* ==================================== */

function get_js_script($js_template_path, $data) {
    $js_tpl_raw = file_get_contents($js_template_path);
    $js_parsed = parseTemplateString($js_tpl_raw, $data);
    return '<script type="text/javascript">'.$js_parsed.'</script>';
}

/* ==================================== */
/*           STRING PARSING             */
/* ==================================== */

function parseTemplateString($raw_str, $data) {
    /**
     * replace containers like {{coco}} in $raw_str by the value of $data['coco']
     * 
     * @param string $raw_str     The raw string containing containers like {{coco}}
     * @param string $data              The assoc. array containing the data to be inserted in $raw_str
     * 
     * @return string                   The string $raw_str parsed with data from $data
     */

    $parsed_str = $raw_str;
    
    $res = preg_match_all("/{{([^}]+)}}/", $raw_str, $matches);
    if ($res === false || $res == 0) return $raw_str;
    foreach ($matches[1] as $match) {
        if (isset($data[$match])) $parsed_str = str_replace('{{'.$match.'}}', $data[$match], $parsed_str);
        else $parsed_str = str_replace('{{'.$match.'}}', '', $parsed_str);
    }

    return $parsed_str;
}

/* ==================================== */
/*     CHARGEMENT DE FICHIERS PHP       */
/* ==================================== */

if (!function_exists('require_once_all_regex')):
function require_once_all_regex($dir_path, $regex = "") {
    /**
     * Require once all files in $dir_path that have a filename matching $regex
     * 
     * @param string $dir_path
     * @param string $regex
     */

    if ($regex == "") $regex = "//";

    foreach (scandir($dir_path) as $filename) {
        $path = $dir_path . '/' . $filename;
        if ($filename[0] != '.' && is_file($path) && preg_match("/\.php$/i", $path) == 1 && preg_match($regex, $filename) == 1) {
            require_once $path;
        } else if ($filename[0] != '.' && is_dir($path)) {
            require_once_all_regex($path, $regex);
        }
    }
}
endif;


/* ==================================== */
/*     LOW-LEVEL USEFUL FUNCTIONS       */
/* ==================================== */

if (!function_exists('array_map_assoc')):
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
endif;

// It assigns values of $el2 to $el1.
// $el1 and $el2 are assoc arrays
if (!function_exists('assign_default')):
function assign_default($el1, $el2) {
    foreach ($el2 as $k2 => $v2) {
        $el1[$k2] = $v2;
    }
    return $el1;
}
endif;

// extracts the fields in $fields from the assoc array $arr
if (!function_exists('extract_fields')):
function extract_fields($arr, $fields) {
    $new_arr = array();
    foreach ($fields as $field) {
        $new_arr[$field] = $arr[$field];
    }
    return $new_arr;
}
endif;

?>