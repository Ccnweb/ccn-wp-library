<?php
namespace ccn\lib;

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

        // case of a simple {{...}} to be replaced
        if (isset($data[$match])) {
            if (gettype($data[$match]) == 'array') $data[$match] = '['.implode(', ', $data[$match]).']';
            $parsed_str = str_replace('{{'.$match.'}}', $data[$match], $parsed_str);

        // case of a conditional tag {{IF ...}} ... {{/IF}}
        } else if (substr($match, 0, 2) == 'IF') {
            $tag_start = '{{'.$match.'}}';
            $tag_end = '{{/IF}}';
            
            // we evaluate the condition string
            $condition_str = substr($match, 3);
            foreach ($data as $key => $val) 
                if (strpos($condition_str, '$'.$key) !== false) 
                    $condition_str = str_replace('$'.$key, $val, $condition_str);
            
            $condition_b = false;
            try {
                eval('$condition_b = ('.$condition_str.');');
            } catch (Exception $e) {
                log\warning('TEMPLATE_STRING_PARSE_FAILED', 'Invalid if condition in template string : '.$condition_str);
                continue;
            }

            $res = get_tags($parsed_str, $tag_start, $tag_end);

            // we delete all false conditions
            if ($condition_b !== false) foreach ($res as $el) $parsed_str = str_replace($tag_start.$el.$tag_end, $el, $parsed_str);
            // we keep all true conditions
            else foreach ($res as $el) $parsed_str = str_replace($tag_start.$el.$tag_end, '', $parsed_str);

        } else if (substr($match, 0, 1) != '/') {
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


?>