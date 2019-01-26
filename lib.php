<?php
namespace ccn\lib;

require_once('log.php'); use \ccn\lib\log as log;

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

/* ==================================== */
/*          DEBUG FUNCTIONS             */
/* ==================================== */

function fix_if_wrong($sujet, $default_if_wrong, $check_fun, $details = '') {
    /**
     * Checks if $sujet is ok by using $check_fun($sujet) (should return true)
     * if $sujet is ok, it returns $sujet
     * if $sujet is broken/wrong, returns $default_if_wrong
     * 
     * $details includes details in the log if object is wrong
     * 
     */

    if ($check_fun($sujet)) return $sujet;
    log\error('FIX_IF_WRONG', $details.' ==> $sujet='.json_encode($sujet));
    return $default_if_wrong;
}

/* ==================================== */
/*     LOW-LEVEL USEFUL FUNCTIONS       */
/* ==================================== */

function array_swap_chaussette($arr) {
    /**
     * retourne un array comme une chaussette
     * par exemple :
     * array('toto' => ['un', 'deux', 'trois'], 'riri' => ['quatre', 'cinq', 'six'])
     * devient [{toto => 'un', riri => 'quatre'}, {toto => 'deux', riri => 'cinq'}, {toto => 'trois', riri => 'six'}]
     */

    if (!is_array($arr)) {
        log\error('INVALID_ARGUMENT', 'in lib\array_swap_chaussette, $arr is not an array : $arr='.json_encode($arr));
        return false;
    }

    $new_arr = array();
    $keys = array_keys($arr);
    if (count($keys) < 1) return $arr;
    for ($i = 0; $i < count($arr[$keys[0]]); $i++) {
        $new_val = array();
        foreach ($keys as $key) {
            if (!is_countable($arr[$key])) {
                log\error('INVALID_ARGUMENT', "in function lib\array_swap_chaussette : $arr[$key] n'est pas un array : $key=".json_encode($key).' et $arr='.json_encode($arr));
            } else {
                if ($i < count($arr[$key])) $new_val[$key] = $arr[$key][$i];
                else {
                    log\warning('INVALID_ARGUMENT', "in function lib\array_swap_chaussette : tous les éléments de arr n'ont pas la même longueur, fallback en faisant un padding avec des ''. Détails : ".json_encode($arr));
                    $new_val[$key] = '';
                }
            }
        }
        array_push($new_arr, $new_val);
    }
    return $new_arr;
}

function array_transform_mapper($arr, $attr_key, $attr_val) {
    /**
     * Transforme un array d'objets de la manière suivante :
     * 
     * ### Cas de base :
     * $arr = [{a:1, b:2}, {a:3, b:4}, {a:5, b:6}]
     * $attr_key = 'a'
     * $attr_val = 'b'
     * 
     * RETURN --> {1 => 2, 3 => 4, 5 => 6}
     * 
     * ### Autre cas (array) :
     * $arr = [{a:[1, 'a'], b:[2, 'b']}, {a:3, b:4}]
     * $attr_key = 'a'
     * $attr_val = 'b'
     * 
     * RETURN --> {1 => 2, 'a' => 'b'}
     * 
     */

    $mapper = array();
    foreach ($arr as $el) {
        if (array_key_exists($attr_key, $el) && array_key_exists($attr_val, $el)) { 
            // cas "array"
            if (is_array($el[$attr_key]) && is_array($el[$attr_val])) {
                $mapper = assign_default($mapper, array_build($el[$attr_key], $el[$attr_val]));
            // cas de base
            } else if (gettype($el[$attr_key]) == 'string' || gettype($el[$attr_key]) == 'integer') {
                $mapper[$el[$attr_key]] = $el[$attr_val];
            } else {
                log\warning('INVALID_ARGUMENT', 'in lib\array_transform_mapper : element is neither a string nor an array : el='.json_encode($el[$attr_key]));
            }
        }
    }
    return $mapper;
}

function array_map_attr($arr, $attr) {
    /**
     * $attr = 'a'
     * $arr = [{a:1, b:2}, {a:3, b:4}, {a:5, b:6}]
     * RETURNS ---> [1, 3, 5]
     */

    return array_map(function($el) use ($attr) {
        if (is_array($el) && array_key_exists($attr, $el)) return $el[$attr];
        else return false;
    }, $arr);
}

function array_filter_nonempty($arr) {
    /**
     * Renvoie un sous-array de $arr avec uniquement les éléments non-vides (fonction empty())
     */

    return array_filter($arr, function($el) {
        return !empty($el);
    });
}

function array_find_by_key($arr, $attr, $val) {
    /**
     * Renvoie l'élément $el dans l'array $arr
     * où $el[$attr] == $val
     * Renvoie false si $el n'est pas trouvé
     */

    foreach ($arr as $el) {
        if (isset($el[$attr]) && $el[$attr] == $val) return $el;
    }
    return false;
}


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

function array_mask($arr, $mask) {
    /**
     * Cette fonction renvoie un sous-array de $arr qui contient les élément $arr[i] où $mask[i] est évalué à true
     * 
     * @param array $arr    un array d'éléments quelconque
     * @param array $mask   un array de booleans ou d'éléments qui ont un valeur true/false de même longueur que $arr
     * 
     */
    // TODO faire un check sur count($mask) = count($arr)

    $new_arr = array();
    for ($i = 0; $i < count($arr) && $i < count($mask); $i++) {
        if ($mask[$i]) array_push($new_arr, $arr[$i]);
    }
    return $new_arr;
}


function assign_default($el1, $el2) {
    // It assigns values of $el2 to $el1.
    // $el1 and $el2 are assoc arrays

    // preliminary checks
    if (!is_array($el1)) {
        log\error('INVALID_ARGUMENT', 'in function lib\assign_default : $el1 is not an array : $el1='.json_encode($el1));
        return false;
    }
    if (!is_array($el2)) {
        log\error('INVALID_ARGUMENT', 'in function lib\assign_default : $el2 is not an array : $el2='.json_encode($el2));
        return false;
    }

    foreach ($el2 as $k2 => $v2) {
        $el1[$k2] = $v2;
    }
    return $el1;
}

// extracts the fields in $fields from the assoc array $arr
function extract_fields($arr, $fields) {
    $new_arr = array();
    foreach ($fields as $field) {
        if (gettype($field) == 'string') {
            if (isset($arr[$field])) $new_arr[$field] = $arr[$field];
        } else log\error('INVALID_ARGUMENT', 'in lib\extract_fields : Wrong type for $field, which has type '.gettype($field). ' (string expected). Details : $fields='.json_encode($fields));
    }
    return $new_arr;
}

function array_flatten($arr) {
    /**
     * Flattens completely an array of arrays
     */

    $new_arr = array();
    foreach ($arr as $el) {
        if (gettype($el) == 'array') {
            $new_arr = array_merge($new_arr, array_flatten($el));
        } else {
            array_push($new_arr, $el);
        }
    }
    return $new_arr;
}

function array_build($keys, $values) {
    /**
     * construit un array associatif à partir des clés et valeurs
     */

    $arr = array();
    for ($i = 0; $i < count($keys) && $i < count($values); $i++) {
        $arr[$keys[$i]] = $values[$i];
    }
    return $arr;
}

function array_has_string_key($arr) {
    /**
     * tells is array has at least one string key
     */

    if (!is_array($arr)) {
        log\error('INVALID_ARGUMENT', 'in lib\array_has_string_key : argument is not an array : $arr='.json_encode($arr));
        return false;
    }
    return count(array_filter(array_keys($arr), 'is_string')) > 0;
}

// ======================================================
//      LOW-LEVEL FUNCTIONS FOR MAPPERS
// ======================================================
/**
 * "mapper" is just a word to call a PHP array that has the only functional purpose to map keys to values
 * 
 * Therefore, it's an associative array like {key1: val1, key2: val2, key3: val3, ...}
 * and you should not care about what is inside val1, val2, ... what is important is just the fact that it has to map keys to values
 * If the functional purpose changes, you can't call it a mapper any more.
 * 
 * Why is it useful :
 * - it is more clear what the following functions do just by reading the title
 * 
 */

function mapper_reverse($m) { // presque équivalent à array_flip
    /**
     * Reverse keys and values from the mapper
     * 
     * Special case :
     * $m = {a: [1, 2], b: [3, 4], c: 5}
     * RETURNS --> {1: 'a', 2: 'a', 3: 'b', 4: 'b', 5: 'c'}
     */

    $rev_m = array();
    foreach ($m as $key => $val) {
        if (is_array($val)) {
            $rev_m = assign_default($rev_m, array_build($val, $key));
        } else if (gettype($val) == 'string' || gettype($val) == 'integer') {
            $rev_m[$val] = $key;
        }
    }
    return $rev_m;
}
?>