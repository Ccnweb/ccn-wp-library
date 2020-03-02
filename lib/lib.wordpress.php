<?php
namespace ccn\lib;

require_once('lib.html.php');

function get_image_url_by_title($title) {
    /**
     * returns the image url from the given image title
     */
    return wp_get_attachment_url(get_page_by_title($title, 'OBJECT', 'attachment')->ID);
}

function tags_to_wrap_content($content, $posttags = null) {
    /**
     * Outputs the $content but wrapped in the elements defined by the wrap-* tags
     * 
     * e.g. if the current post has a tag "wrap-div.coco.riri", 
     * this will return the $content wrapped in a <div class="coco riri"></div> element
     */

    if($posttags == null) $posttags = get_the_tags();
	if (!is_array($posttags)) return $content;

	$arr_posttags = array_map(function($tag) {return (property_exists($tag, 'name')) ? $tag->name : $tag;}, $posttags);
	$s_posttags = '@@'.implode('@@', $arr_posttags).'@@';

	// get the tags of the form "wrap-..."
	preg_match_all('/@@wrap-([^#]+)@@/', $s_posttags, $result);
	if (!$result) return $content;
	
	foreach($result[1] as $wrapper) {
        $html_obj = parse_html_snippet($wrapper);
        if ($html_obj === false) continue;
		$content = build_html_tag($html_obj['tag_name'], $html_obj, $content);
	}

	return $content;
}

function tags_to_css_classes($posttags = null) {
    /**
     * transforms tags like class-* in a string representing space-delimited CSS classes
     * 
     * @param $posttags     either an array of tag items or null (if null, post tags will be retrieved with "get_the_tags()")
     */

    if ($posttags == null) $posttags = get_the_tags();
    if (!is_array($posttags)) return '';

    $arr_posttags = array_map(function($tag) {return $tag->name;}, $posttags);
    $s_posttags = '##'.implode('##', $arr_posttags).'##';

    // get the CSS classes defined as tags (in the form "class-...")
    preg_match_all('/#class-([^#]+)#/', $s_posttags, $result);
    return ($result) ? ' '.implode(' ', $result[1]): '';
}

function enqueue_styles_regex($dir, $regex_pattern, $options = array()) {
    return enqueue_elements_regex('style', $dir, $regex_pattern, $options);
}

function enqueue_scripts_regex($dir, $regex_pattern, $options = array()) {
    return enqueue_elements_regex('script', $dir, $regex_pattern, $options);
}

function enqueue_elements_regex($type, $dir, $regex_pattern = '', $options = array()) {
    /**
     * This functions helps to automatically enqueue scripts and styles based on a regex applied to the filename
     * 
     * For example, you can call enqueue_element('style', './components', '/_to_be_loaded\.css$/i');
     * this will call wp_enqueue_style function 
     * on all files in the ./components directory (recursively) 
     * whose name match the regex (here it's all files that finish with "to_be_loaded.css")
     * 
     * @param string $type          the type of element to be loaded, it can be "script" or "style"
     * @param string $dir           the path to the root directory in which you want to look
     * @param string $regex_pattern the regex pattern to apply on file names
     */

    $default_options = array(
        'prefix' => 'ccnlib-forms', // prefixes of the files to enqueue
        'dependencies' => array(), // array of dependencies for the elements to enqueue
        'plugin_dir' => '',
        'plugin_url' => '',
    );
    $options = array_merge($default_options, $options);

    // preliminary checks
    if (empty($options['plugin_dir']) && !function_exists('get_template_directory')) return log\error('WORDPRESS_ENQUEUE_FAILED', 'option "plugin_dir" is not defined and no function get_template_directory is available to guess its value :(');
    if (empty($options['plugin_url']) && !function_exists('get_template_directory_uri')) return log\error('WORDPRESS_ENQUEUE_FAILED', 'option "plugin_url" is not defined and no function get_template_directory_uri is available to guess its value :(');
    if ($type != 'style' && $type != 'script') return log\error('WORDPRESS_ENQUEUE_FAILED', 'Impossible to enqueue element of unknown type "'.$type.'" (should be script or style)');

    // we prepare some variables
    $extension = ($type == 'script') ? 'js' : 'css';
    if (empty($options['plugin_dir'])) $options['plugin_dir'] = get_template_directory();
    if (empty($options['plugin_url'])) $options['plugin_url'] = get_template_directory_uri() . '/';
    if ($regex_pattern == '') $regex_pattern = ($type == 'script') ? '/\.js$/' : '/\.css$/';

    // we search for all files matching the regex
    $element_to_be_enqueued = dir_filter_fun($dir, function($fpath, $fname, $finfo) use ($extension, $regex_pattern, $options) {
        if (preg_match($regex_pattern, $fname) && preg_match("/\.".$extension."$/i", $fname) && !$finfo['is_dir']) {
            return str_replace("\\", "/", path_full_to_relative($options['plugin_dir'], $fpath));
        }
        return false;
    }, true);

    // we enqueue the found files
    $i = 0;
    foreach ($element_to_be_enqueued as $el) {
        //echo 'ENQUEUE '.$type.' '.join_paths([$options['plugin_url'], $el['value']]).'<br>';
        if ($type == 'style') wp_enqueue_style($options['prefix'].'-'.$i, join_paths([$options['plugin_url'], $el['value']]), $options['dependencies'], '20200302', 'all');
        else wp_enqueue_script($options['prefix'].'-'.$i, join_paths([$options['plugin_url'], $el['value']]), $options['dependencies'], '20200302', 'all');
        $i++;
    }

    return $element_to_be_enqueued;
}

function php_console_log($msg, $type = 'log', $style = '') {
    /**
     * Log something in the javascript console from php code
     */

    $type = strtolower($type);
    if ($type == 'err') $type = 'error';
    if ($style != '' && !preg_match("/^\%c\s+/", $msg)) $msg = "%c ".$msg;

    $msg = str_replace('"', '\\"', $msg);
    $str = 'console.'.$type.'("'.$msg.'"';
    $str .= ($style != '') ? ', "'.$style.'")' : ')';
    echo '<script class="php_log">'.$str.'</script>';
}

?>