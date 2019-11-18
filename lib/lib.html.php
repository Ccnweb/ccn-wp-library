<?php
namespace ccn\lib;

require_once('lib.string.php');

/* ==================================== */
/*           HTML MANIPULATION          */
/* ==================================== */

function parse_html_snippet($str_snippet) {
    /**
     * transforms a snippet like "div#coco.riri.gio" 
     * in ['tag_name' => 'div', 'id' => 'coco', 'class' => ['riri', 'gio']]
     */

    $o = [];

    // get tag_name
    if (!preg_match("/^[^\.\#]+/", $str_snippet, $tag_name)) return false;
    $o['tag_name'] = $tag_name[0];

    // get id
    $o['id'] = '';
    if (preg_match("/\#([^\.\#]+)/", $str_snippet, $id)) $o['id'] = $id[1];

    // get the classes
    preg_match_all("/\.([^\.\#]+)/", $str_snippet, $classes);
    if ($classes && count($classes) > 1) $o['class'] = $classes[1];

    return $o;
}

function build_html_tag($tag_name, $attributes, $content = '') {
    /**
     * Builds an HTML tag like this :
     * $tag_name = "div"
     * $attributes = ['class' => 'truc', 'coco' => 1]
     * $content = "the content"
     * --> <div class"truc" coco="1">the content</div>
     * 
     * @param string tag_name
     * @param array $attributes array associative
     * @return string
     */

    $s_html = '<'.$tag_name;
    foreach ($attributes as $key => $val) {
        if ($key == 'tag_name' || empty($val)) continue;
        if (is_array($val)) $val = implode(' ', $val);
        $s_html .= ' '.$key.'="'.$val.'"';
    }
    $s_html .= '>'.$content.'</'.$tag_name.'>';
    return $s_html;
}

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