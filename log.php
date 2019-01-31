<?php
namespace ccn\lib\log;


function get_log_dir() {
    /**
     * Returns the log directory
     */
    return CCN_LIBRARY_PLUGIN_DIR . '/log';
}

function get_log_params() {
    /**
     * Returns the log parameters
     */

    return [
        'dir' => get_log_dir(),
        'max_file_size' => 'todo', // starts a new file if file gets bigger than this
        'max_file_age' => '6', // in months. deleted all files older than max_file_age days
    ];
}

function write($level, $title, $data, $category = "") {
    /**
     * Writes a log in the log file
     * 
     * @param string level      Le niveau de log (INFO, WARNING, ERROR). N.B: le niveau INFO est loggé dans un sous-dossier INFO
     * @param string title      un titre pour cette entrée (ça peut être par ex un identifiant de l'erreur)
     * @param string data       soit une string soit un élément qui sera écrit comme json_encode(data)
     * @param string category   cela va créer un sous-dossier avec le nom de la catégorie dans le dossier de logs
     * 
     */

    // on clean les logs
    clean_old_logs();

    // la date
    $curr_log_date = date('Y-m-d H:i:s');

    // le niveau et le titre
    $level = strtoupper($level);
    $title = strtoupper($title);

    // on génère le nom du fichier de log où il faudra écrire
    if (!$category && ($level == 'INFO' || $level == 'DEBUG')) $category = $level;
    if ($category) $category = '/'.$category;
    $log_dir = get_log_dir().$category;
    $log_path = $log_dir.'/log-'.date('Y-m').'.txt';

    // le message
    $body = (gettype($data) == 'string') ? $data : json_encode($data);

    // on écrit le message
    $msg = $curr_log_date.' ::'.$level.'::'.$title.':: '.$body."\n";

    // TODO créer les dossiers inexistants éventuellement
    return file_put_contents($log_path, $msg, FILE_APPEND | LOCK_EX);//file_force_contents($log_path, $msg);
}

function error($title = "", $data = "", $return_value = false) {
    // $return_value permet de renvoyer $return_value :)
    write('ERROR', $title, $data);
    return $return_value;
}

function warning($title = "", $data = "", $return_value = false) {
    write('WARNING', $title, $data);
    return $return_value;
}

function info($title = "", $data = "", $return_value = false) {
    write('INFO', $title, $data);
    return $return_value;
}

// ================================================================
//                  LOG CLEANING
// ================================================================

function clean_old_logs() {
    /**
     * Deletes all logs that are too old
     */

    $log_dir = get_log_dir();
    $max_file_age = get_log_params()['max_file_age']; // in months
    $interval = date_interval_create_from_date_string(-$max_file_age.' months');
    $oldest_date = date_add(date_create('now'), $interval);
    $oldest_date = date_format($oldest_date, 'Y-m');
    
    $delete_old_fun = function($full_path, $file_name, $meta_info) use ($oldest_date) {
        if ($meta_info['is_dir']) return false;
        $curr_date = date('Y-m', $meta_info['last_modification_date']);
        if ($curr_date <  $oldest_date) {
            unlink($full_path);
            return true;
        }
        return $curr_date;
    };
    return dir_map_fun($log_dir, $delete_old_fun, true);
}


// ================================================================
//                  HELPER FUNCTIONS
// ================================================================

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

// crée tous les dossiers nécessaires pour que le chemin vers le dossier $dir existe
function file_force_contents($dir, $contents){
    /**
     * NE MARCHE PAS !!!!
     */
    $parts = explode('/', $dir);
    $file = array_pop($parts);
    $dir = '';
    foreach ($parts as $part)
        $a = $dir."/$part";
        if (!is_dir($dir .= "/$part")) mkdir($dir);
    return file_put_contents("$dir/$file", $contents, FILE_APPEND | LOCK_EX);
}
?>