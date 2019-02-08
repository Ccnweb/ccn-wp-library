<?php

if (!defined('CCN_LIBRARY_PLUGIN_DIR')) define('CCN_LIBRARY_PLUGIN_DIR', realpath('..'));

require_once(CCN_LIBRARY_PLUGIN_DIR . '/forms/lib.forms.php');
use \ccn\lib\html_fields as fields;

// ============================================
//  SOME HELPFUL FUNCTIONS TO WRITE TESTS
// ============================================

function print_out($o, $end = 1) {
    echo json_encode($o);
    for($i = 0; $i < $end; $i++) echo "\n";
}

// ============================================
//          GENERATE MOCKUP DATA
// ============================================

function get_mockup_params() {
    return [
        "alphabet" => explode(',', 'a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z'),
        "prenoms" => ['jonas', 'bruce', 'galadriel', 'aragorn'],
        "noms" => ['baugé', 'parker', 'lepetit', 'bergstein', 'skywalker'],
        "prenoms_noms" => ['Peter Parker', 'Bruce Banner', 'Tony Stark', 'Bruce Wayne', 'Clark Kent', 'Luke Skywalker'],
        "domaines" => ['gmail.com', 'chemin-neuf.org', 'yahoo.fr', 'wanadoo.fr', 'mail.com'],
        "adjectifs" => ['great', ''],
        "streets" => ['Chemin de traverse', 'Montée du Chemin Neuf', 'Pentagon street'],
        "cities" => ['Mountain View', 'Palo Alto', 'Beijing'],
    ];
}

function gen_mockup_post_data($fields) {
    /**
     * generates mockup data from array of fields
     * 
     * TODO finish this function
     */

    $mockup_data = [];
    $memory = [];

    foreach ($fields as $field) {
        // repeat-fields
        if ($field['type'] == 'REPEAT-GROUP') {

        // simple fields
        } else {
            if ($field['type'] == 'email') {
                $prenom = ''; $nom = '';
                if ($memory['prenom']) $prenom = $memory['prenom'];
                if ($memory['nom']) $nom = $memory['nom'];
                $mockup_data[$field['id']] = gen_email($prenom, $nom);
                
            } else if ($field['type'] == 'nom_prenom') {
                $ids = fields\get_field_ids($field);
                $values = explode(' ', gen_prenom_nom());
                $mockup_data[$ids[0]] = $values[0];
                $mockup_data[$ids[1]] = $values[1];

            } else if ($field['type'] == 'date') {
                $mockup_data[$field['id']] = gen_date_min_max();
            }
        }
    }

    return $mockup_data;
}

function gen_prenom_nom() {
    $_ = get_mockup_params();
    return (random_int(0,100) < 50) ? array_pick($_['prenoms_noms']) : array_pick($_['prenoms']).' '.array_pick($_['noms']);
}

function gen_email($prenom = '', $nom = '') {
    $_ = get_mockup_params();
    $prenom_nom = $prenom.'.'.$nom;
    if ($prenom == '' && $nom == '') $prenom_nom = implode('.', explode(' ', strtolower(gen_prenom_nom())));
    else if ($prenom == '') $prenom_nom = array_pick($_['prenoms']).'.'.$nom;
    else if ($nom == '') $prenom_nom = $prenom.'.'.array_pick($_['noms']);

    return $prenom_nom.'@'.array_pick($_['domaines']);
}

function gen_date_min_max($min_date = '1900-01-01', $max_date = 'now', $format = 'd-m-Y') {
    /**
     * returns the string in $format
     */
    if (gettype($min_date) == 'string') $min_date = date_create($min_date);
    if (gettype($max_date) == 'string') $max_date = date_create($max_date);
    $min_date_timestamp = date_timestamp_get($min_date);
    $max_date_timestamp = date_timestamp_get($max_date);

    $int = mt_rand($min_date_timestamp, $max_date_timestamp);

    return date($format, $int);
}

function array_pick($arr) {
    return $arr[array_rand($arr, 1)];
}

function gen_random_array($options = array()) {
    /**
     * Returns a random array like [{a:1, b:2}, {a:3, b:5}, {a: 6, b:9}]
     */

    $default_options = array(
        'nb_keys_per_object' => 2,
        'nb_elements' => 3,
        'value_type' => 'any', // any || int || string
    );
    $options = array_merge($default_options, $options);

    // useful params
    $alphabet = explode(',', 'a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z');

    // generate keys
    $keys_list = array_splice($alphabet, 0, $options['nb_keys_per_object']);

    // generate element
    $arr = [];
    for ($i = 0; $i < $options['nb_elements']; $i++) {
        $new_obj = [];
        foreach ($keys_list as $key) $new_obj[$key] = random_int(0,100);
        $arr[] = $new_obj;
    }
    return $arr;
}

?>