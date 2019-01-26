<?php

define( 'CCN_LIBRARY_PLUGIN_DIR', '..' );

require_once(CCN_LIBRARY_PLUGIN_DIR . '/lib.php');
use \ccn\lib as lib;

// ============================================
//  TEST parseTemplateString
// ============================================

$sujet = 'Louez {{dieu}} vous {{qui}}';
$data = array(
    'dieu' => 'le Seigneur Dieu de l\'univers',
    'qui' => 'tous rassemblés',
);
$res = lib\parseTemplateString($sujet, $data);
echo $res."\n\n";

// ============================================
//  TEST array_swap_chaussette
// ===========================================

$arr = array('toto' => ['un', 'deux', 'trois'], 'riri' => ['quatre', 'cinq', 'six']);
echo json_encode(lib\array_swap_chaussette($arr))."\n\n";


$arr = array(
    "ccnbtc_key_child_firstname_field" => "Pierre et",
    "ccnbtc_key_child_name_field" => "Haza\u00ebl-Massieux",
    "ccnbtc_child_birthdate_field" => ""
);
$field_names = ["ccnbtc_key_child_firstname_field","ccnbtc_key_child_name_field"];
echo json_encode(lib\extract_fields($arr, $field_names))."\n\n";



$arr = array(
    array(
        "meta_id" => ["ccnbtc_key_persontype"],
        "html_id" => ["ccnbtc_key_persontype_field"]
    ),
);

$res = lib\array_map_attr($arr, 'html_id');
echo json_encode($res)."\n\n";
?>