<?php
namespace ccn\lib;

function dir_filter_fun($dir, $fun, $recursive = true){
    /**
     * Comme array_filter mais sur les fichiers/dossiers contenus dans $dir
     */

    $res = dir_map_fun($dir, $fun, $recursive);
    return array_filter($res, function($el) {
        return $el['value'];
    });
}

function dir_map_fun($dir, $fun, $recursive = true){
    /**
     * Applies function $fun to all files and dirs in $dir (recusively or not)
     * Returns the list of {path => [value returned by $fun(path)]}
     * 
     * @param string $dir       the directory path
     * @param callable $fun     fonction($full_path, $file_name, $meta_info) to apply to all files and dirs
     *                          $meta_info is an array with info on the file returned by get_file_meta_info()
     * 
     */

    $results = array();
    $files = scandir($dir);
    if ($files === false) return false;

    foreach ($files as $key => $value){
        $path = realpath($dir.DIRECTORY_SEPARATOR.$value);

        if(!is_dir($path)){
            $info = get_file_meta_info($path);
            $results[] = array(
                'path' => $path,
                'name' => $value,
                'value' => $fun($path, $value, $info)
            );

        } else if($value != "." && $value != "..") {
            $info = get_file_meta_info($path);
            $results[] = array(
                'path' => $path,
                'name' => $value,
                'value' => $fun($path, $value, $info),
            );
            if ($recursive) $results = array_merge($results, dir_map_fun($path, $fun, true));
        }
    }

    return $results;
}

function get_file_meta_info($path) {
    return array(
        'is_dir' => is_dir($path),
        'last_modification_date' => filemtime($path), // use date('Y-m', $meta_info['last_modification_date']); to format the way you want
        'type' => filetype($path),
        'size' => filesize($path), // in bytes/octets
        'owner' => fileowner($path),
        'perms' => fileperms($path), // returns an int - http://php.net/manual/fr/function.fileperms.php
    );
}

function path_full_to_relative($mask_path, $full_path) {
    /**
     * Transforms a full path into a relative one, relative to $mask_path
     */

    $dir_sep = "/";
    $other_dir_sep = "\\";
    if (strpos($full_path, "\\") !== false) {
        $dir_sep = "\\";
        $other_dir_sep = "/";
    }
    
    $mask_path = str_replace($other_dir_sep, $dir_sep, $mask_path);
    if (substr($mask_path, -1) != $dir_sep) $mask_path .= $dir_sep;
    $full_path = str_replace($other_dir_sep, $dir_sep, $full_path);

    $ind = strpos($full_path, $mask_path);
    if ($ind === 0) return substr($full_path, strlen($mask_path));
    return $full_path;
}

?>