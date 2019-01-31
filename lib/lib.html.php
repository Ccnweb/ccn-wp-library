<?php
namespace ccn\lib;

require_once('lib.string.php');

/* ==================================== */
/*           HTML MANIPULATION          */
/* ==================================== */


function build_html($elements, $data) {
    /**
     * Creates an HTML string from elements and data
     * in the following way
     * 
     * @param array $elements   e.g. array('titre' => '<h1>{{titre}}</h1>", 'contenu' => '<p>{{contenu}}<p>')
     * @param array $data       e.g. array('contenu' => 'The content')
     * 
     * Returns :
     * "<p>The content</p>"
     * (it skips the h1 title, because it is not defined in $data)
     * 
     */

    $html = '';
    foreach ($elements as $key => $element) {
        if (preg_match("/{{([^}]+)}}/", $element)) {
            if (isset($data[$key])) {
                if (is_array($data[$key])) {
                    $i = 0;
                    foreach ($data[$key] as $el) {
                        $curr_data = $data;
                        foreach ($curr_data as $k => $v) if (is_array($v) && array_key_exists($i, $v)) $curr_data[$k] = $v[$i];
                        $curr_data[$key] = $el;
                        $html .= parseTemplateString($element, $curr_data) . "\n";
                        $i++;
                    }
                } else {
                    $html .= parseTemplateString($element, $data) . "\n";
                }
            }
        } else {
            $html .= $element . "\n";
        }
    }
    return $html;
}

function array_map_template($data_arr, $template) {
    /**
     * e.g.
     * $data_arr = [[a: 1, b: 2], [a: 3, b: 4]];
     * $template = '<p class="{{a}}">{{b}}</p>'
     * RETURNS --> ['<p class="1">2</p>', '<p class="3">4</p>']
     */

    $html_list = array();
    foreach ($data_arr as $data) {
        $html_list[] = parseTemplateString($template, $data);
    }
    return $html_list;
}

/* ==================================== */
/* CRÉE UN TAG <script> pour injecter du JS qqe part */
/* ==================================== */

function get_js_script($js_template_path, $data) {
    $js_tpl_raw = file_get_contents($js_template_path);
    $js_parsed = parseTemplateString($js_tpl_raw, $data);
    return '<script type="text/javascript">'.$js_parsed.'</script>';
}

?>