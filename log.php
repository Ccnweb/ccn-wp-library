<?php
namespace ccn\lib\log;


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

    // la date
    $curr_log_date = date('Y-m-d H:i:s');

    // le niveau et le titre
    $level = strtoupper($level);
    $title = strtoupper($title);

    // on génère le nom du fichier de log où il faudra écrire
    if (!$category && ($level == 'INFO' || $level == 'DEBUG')) $category = $level;
    if ($category) $category = '/'.$category;
    $log_dir = CCN_LIBRARY_PLUGIN_DIR . '/log'.$category;
    $log_path = $log_dir.'/log-'.date('Y-m').'.txt';

    // le message
    $body = (gettype($data) == 'string') ? $data : json_encode($data);

    // on écrit le message
    $msg = $curr_log_date.' ::'.$level.'::'.$title.':: '.$body."\n";

    return file_force_contents($log_path, $msg);
}

function error($title = "", $data = "") {
    return write('ERROR', $title, $data);
}

function warning($title = "", $data = "") {
    return write('WARNING', $title, $data);
}

function info($title = "", $data = "") {
    return write('INFO', $title, $data);
}

// crée tous les dossiers nécessaires pour que le chemin vers le dossier $dir existe
function file_force_contents($dir, $contents){
    $parts = explode('/', $dir);
    $file = array_pop($parts);
    $dir = '';
    foreach($parts as $part)
        if(!is_dir($dir .= "/$part")) mkdir($dir);
    return file_put_contents("$dir/$file", $contents, FILE_APPEND | LOCK_EX);
}
?>