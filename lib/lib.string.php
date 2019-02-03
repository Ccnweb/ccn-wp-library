<?php
namespace ccn\lib;

require_once('lib.misc.php');

/* ==================================== */
/*           STRING PARSING             */
/* ==================================== */

function parseTemplateString($raw_str, $data) {
    /**
     * replace containers like {{coco}} in $raw_str by the value of $data['coco']
     * 
     * @param string $raw_str     The raw string containing containers like {{coco}}
     * @param string $data        The assoc. array containing the data to be inserted in $raw_str
     * 
     * @return string/boolean     The string $raw_str parsed with data from $data. Returns false if something went wrong
     */

    $parsed_str = $raw_str;
    
    $res = preg_match_all("/{{([^}]+)}}/", $raw_str, $matches);
    if ($res === false || $res == 0) return $raw_str;
    foreach ($matches[1] as $match) {

        // ===================================
        // case of a simple {{...}} to be replaced
        // ===================================
        if (isset($data[$match])) {
            if (gettype($data[$match]) == 'array') $data[$match] = '['.implode(', ', $data[$match]).']';
            $parsed_str = str_replace('{{'.$match.'}}', $data[$match], $parsed_str);

        // ===================================
        // case of a conditional tag {{IF ...}} ... {{/IF}}
        // ===================================
        } else if (substr($match, 0, 2) == 'IF') {
            $tag_start = '{{'.$match.'}}';
            $tag_end = '{{/IF}}';
            
            // we evaluate the condition string
            $condition_str = substr($match, 3);
            preg_match_all('/\$([A-z0-9_-]+)/i', $condition_str, $condition_matches);
            if (count($condition_matches) > 1) {
                foreach ($condition_matches[1] as $var_name) {
                    $replace_val = '""';
                    if (isset($data[$var_name])) {
                        //if (gettype($data[$var_name]) == 'string') $replace_val = '"'.$data[$var_name].'"';
                        $replace_val = $data[$var_name];
                        if (!in_array(gettype($replace_val), array('integer', 'double'))) $replace_val = json_encode($replace_val);
                    }
                    $condition_str = str_replace('$'.$var_name, $replace_val, $condition_str);
                }
            }
            
            $condition_b = eval_condition($condition_str);
            if ($condition_b == -1) { // something is wrong with the condition syntax
                log\error('TEMPLATE_STRING_PARSE_FAILED', 'Invalid if condition in template string : '.$condition_str);
                return false;
            }

            // we get the content in the IF condition
            $res = get_tags($parsed_str, $tag_start, $tag_end);

            // we delete all true conditions
            if ($condition_b == 1) foreach ($res as $el) $parsed_str = str_replace($tag_start.$el.$tag_end, $el, $parsed_str);
            // we keep all false or broken conditions
            else foreach ($res as $el) $parsed_str = str_replace($tag_start.$el.$tag_end, '', $parsed_str);

        // ===================================
        // case of a for loop 
        // {{FOR $arr as $key => $value}} ... {{/FOR}}
        // ===================================
        } else if (substr($match, 0, 3) == 'FOR') {

            // we get the content in the FOR condition
            $tag_start = '{{'.$match.'}}';
            $tag_end = '{{/FOR}}';
            $res = get_tags($parsed_str, $tag_start, $tag_end);
            if (!$res || empty($res)) {
                //log\warning('TEMPLATE_HTML_PARSE_ERROR', 'in lib.string.php > parseTemplateString : failed to parse FOR loop "'.$match.'". parsed_str='.$parsed_str);
                continue;
            }
            $template_str = $res[0];

            // we parse the FOR loop variables, e.g. "FOR $children as $key => $value" --> ['children', 'key', 'value']
            preg_match('/^FOR\s+\$([^\s]+)\s+as\s+\$([^\s]+)\s=>\s\$([^\s]+)/i', $match, $iter_match);
            if (count($iter_match) < 4) {
                log\warning('TEMPLATE_HTML_PARSE_ERROR', 'in '.basename(__FILE__).' > parseTemplateString : failed to parse FOR loop variables '.$match);
                return false;
            }
            $iter_name = $iter_match[1];
            $key_name = $iter_match[2];
            $val_name = $iter_match[3];

            // we parse the iterated element in $data
            if (!isset($data[$iter_name])) {
                log\warning('TEMPLATE_HTML_PARSE_ERROR', 'in '.basename(__FILE__).' > parseTemplateString : FOR loop iterator not found in data iterator='.$match.' data='.json_encode($data));
                continue;
            }
            $iter_element = $data[$iter_name];
            // if it's JSON array disguised in string, we parse it
            if (gettype($iter_element) == 'string') $iter_element = json_decode($iter_element, true);
            if (!$iter_element) {
                log\warning('TEMPLATE_HTML_PARSE_ERROR', 'in '.basename(__FILE__).' > parseTemplateString : the data to be iterated in for loop is not iterable : type='.gettype($iter_element).', data='.json_encode($data));
                continue;
            }

            // for each element in $iter_element, we parse the template string
            $final_str = '';
            foreach ($iter_element as $i => $myobj) {
                $curr_str = $template_str;

                // $val_name related parsing
                preg_match_all('/\$'.$val_name.'\.([A-z0-9_-]+)/i', $curr_str, $val_matches);
                // example : $val_matches = [["$v.prenom","$v.nom","$v.age"],["prenom","nom","age"]]
                if (count($val_matches) > 1) {
                    for ($k = 0; $k < count($val_matches[0]) && $k < count($val_matches[1]); $k++) {
                        // special case where we have an attribute = $key_name
                        if ($val_matches[1][$k] == $key_name) $val_matches[1][$k] = $i;
                        $replace_val = (isset($myobj[$val_matches[1][$k]])) ? $myobj[$val_matches[1][$k]] : '';
                        if (!in_array(gettype($replace_val), array('string', 'integer', 'double'))) $replace_val = json_encode($replace_val);
                        $curr_str = str_replace($val_matches[0][$k], $replace_val, $curr_str);
                    }
                }

                // $key_name related parsing
                $curr_str = str_replace('$'.$key_name, $i, $curr_str);

                // Complex operations parsing
                preg_match_all('/\$\(([^\)]+)\)/', $curr_str, $key_name_matches);
                if (count($key_name_matches) > 1) {
                    foreach ($key_name_matches[1] as $operation) {
                        $replace_val = '';
                        $replace_val = eval_operation($operation);
                        if ($replace_val === false) {
                            log\error('TEMPLATE_HTML_PARSE_INVALID_OPERATION', 'in '.basename(__FILE__).' > parseTemplateString : invalid operation '.$operation);
                            return false;
                        }
                        $curr_str = str_replace('$('.$operation.')', $replace_val, $curr_str);
                    }
                }

                // TODO $iter_element related parsing

                $final_str .= $curr_str."\n";
            }

            // we replace FOR loop content with $final_str
            $parsed_str = str_replace($tag_start . $template_str . $tag_end, $final_str, $parsed_str);

        // ===================================
        } else if (substr($match, 0, 1) != '/' && substr($match, 0, 2) != 'IF' && substr($match, 0, 3) != 'FOR') {
            $parsed_str = str_replace('{{'.$match.'}}', '', $parsed_str);
        }
    }

    return $parsed_str;
}


function get_tags($str, $tag_start, $tag_end) {
    /**
     * Retrieve elements in $str between the tags $tag_start and $tag_end
     */

    // look for the 1st starting tag
    $ind_start = strpos($str, $tag_start);
    if ($ind_start === false) return [];

    // look for the 1st ending tag
    $str_rest = substr($str, $ind_start + strlen($tag_start));
    $ind_end = strpos($str_rest, $tag_end);
    if ($ind_end === false) return [];

    // retrieve the text between tags
    $texte = substr($str_rest, 0, $ind_end);

    // do the same for the remaining string
    $str_rest = substr($str_rest, $ind_end);
    $the_rest = get_tags($str_rest, $tag_start, $tag_end);

    return array_merge(array($texte), $the_rest);
}


// =======================================================================
//              LOW-LEVEL STRING MANIPULATION
// =======================================================================

function get_max_prefix($arr_str) {
    /**
     * Returns the longest prefix of strings in an array of strings
     * example :
     * $arr_str = ['coco_sdf', 'coco_azeatreituort', 'coco_sdfflgkh']
     * RETURNS --> 'coco_'
     * 
     * @param array<string> $arr_str
     * 
     */

    if (count($arr_str) < 1) return '';
    $prefix = '';
    foreach ($arr_str as $str) {
        if ($prefix == '') $prefix = $str;
        else $prefix = string_common_prefix($prefix, $str);
    }
    return $prefix;
}

function string_common_prefix($str1, $str2) {
    /**
     * Returns the common prefix between strings $str1 and $str2
     */

    $n = min(strlen($str1), strlen($str2));
    $common_prefix = '';
    for ($i = 0; $i < $n; $i++) {
        if ($str1[$i] != $str2[$i]) return $common_prefix;
        $common_prefix .= $str1[$i];
    }
    return $common_prefix;
}


?>