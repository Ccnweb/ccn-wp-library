<?php
namespace ccn\lib;

require_once('log.php'); use \ccn\lib\log as log;

require_once_all_regex(CCN_LIBRARY_PLUGIN_DIR . '/lib', ""); use \ccn\lib as lib;





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


?>