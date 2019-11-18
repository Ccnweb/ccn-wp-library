<?php
namespace ccn\lib;

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
        if ($type == 'style') wp_enqueue_style($options['prefix'].'-'.$i, join_paths([$options['plugin_url'], $el['value']]), $options['dependencies'], '20190107', 'all');
        else wp_enqueue_script($options['prefix'].'-'.$i, join_paths([$options['plugin_url'], $el['value']]), $options['dependencies'], '20190107', 'all');
        $i++;
    }

    return $element_to_be_enqueued;
}

?>