<?php

define( 'CCN_LIBRARY_PLUGIN_DIR', '..' );

require_once(CCN_LIBRARY_PLUGIN_DIR . '/lib.php'); use \ccn\lib as lib;
require_once(CCN_LIBRARY_PLUGIN_DIR . '/create-cp-html-fields.php');

$prefix = "ccnbtc";

$field_1 = array( // Repeat group children
    'type' => 'REPEAT-GROUP',
    'id' => $prefix.'_childrenGR',
    'fields' => array(
        array( // Nom et Prénom
            'id' => $prefix.'_key_child', // le nom de la meta key (sera complété par _firstname et _name)
            'description'  => "Child first name and name for inscription",
            'html_label' => array(
                'prenom' => 'Prénom',
                'nom' => 'Nom (si différent)'
            ),
            'type' => "nom_prenom",
            'required' => [true, false],
        ),
        array( // Date de naissance
            'id' => $prefix.'_child_birthdate',
            'description'  => "Child birth date",
            'html_label' => 'Date de naissance',
            'type' => "date",
            'label' => 'placeholder',
        ),
        array( // Homme/Femme
            'id' => $prefix.'_child_genre',
            'description'  => "Child gender for inscription",
            'html_label' => 'Genre',
            'type' => "dropdown",
            'options' => array(
                'homme' => 'Homme',
                'femme' => 'Femme',
            ),
            'layout' => 'row',
        ),
    ),
);

$mandatory_fields = get_required_fields($field_1, true);
echo json_encode($mandatory_fields)."\n\n";


$new = [
    array("ccnbtc_key_child_firstname_field"=>"Giovanni","ccnbtc_key_child_name_field"=>"","ccnbtc_child_birthdate_field"=>"03-01-2019","ccnbtc_child_genre_field"=>"homme"),
    array("ccnbtc_key_child_firstname_field"=>"Pierre","ccnbtc_key_child_name_field"=>"AZE","ccnbtc_child_birthdate_field"=>"06-12-2018","ccnbtc_child_genre_field"=>"femme"),
    array("ccnbtc_key_child_firstname_field"=>"","ccnbtc_key_child_name_field"=>"","ccnbtc_child_birthdate_field"=>"","ccnbtc_child_genre_field"=>"homme"),
    array("ccnbtc_key_child_firstname_field"=>"","ccnbtc_key_child_name_field"=>"","ccnbtc_child_birthdate_field"=>"","ccnbtc_child_genre_field"=>"homme"),
    array("ccnbtc_key_child_firstname_field"=>"","ccnbtc_key_child_name_field"=>"","ccnbtc_child_birthdate_field"=>"","ccnbtc_child_genre_field"=>"homme")
];
$res = array_filter($new, function($el) use ($mandatory_fields) {
    $el_required = lib\extract_fields($el, $mandatory_fields);
    return count(array_filter($el_required, function($v) {return $v == '';})) == 0;
});
echo json_encode($res)."\n\n".count($res); // should be 2

?>