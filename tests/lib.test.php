<?php

define( 'CCN_LIBRARY_PLUGIN_DIR', '..' );
require_once('test.php');


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
//echo $res."\n\n";

function test_parseTemplateString() {
    $sujet = 'Louez {{dieu}} vous {{qui}} 
        {{IF $truc_bidule == "machin"}}. OUI !{{/IF}} 
        {{IF $truc_bidule != "machin"}}. Bien sûr !{{/IF}}
        {{FOR $children as $k => $v}}
            <div>$k. $($k+1) $v.prenom $v.nom -- $($v.age + 1) ans</div>
        {{/FOR}}';
    $data = array(
        'dieu' => 'le Seigneur Dieu de l\'univers',
        'qui' => 'tous rassemblés',
        'truc_bidule' => 'machin',
        'children' => [
            ['prenom' => 'Carlo', 'nom' => 'Baugé', 'age' => 4],
            ['prenom' => 'Enrica', 'nom' => 'Baugé', 'age' => 25],
        ],
    );
    $res = lib\parseTemplateString($sujet, $data);
    print_out($res);
}
//test_parseTemplateString();

// ============================================
//  TEST get_tags
// ===========================================

function test_get_tags() {
    $str = "{{coco}}Un{{riri}} deux {{co}} truc {{rir}} machin {{coco}}Deux{{riri}}";
    $res = lib\get_tags($str, '{{coco}}', '{{riri}}');
    print_out($res);
}
//test_get_tags();

// ============================================
//  TEST array_swap_chaussette
// ===========================================

$arr = array('toto' => ['un', 'deux', 'trois'], 'riri' => ['quatre', 'cinq', 'six']);
//echo json_encode(lib\array_swap_chaussette($arr))."\n\n";


$arr = array(
    "ccnbtc_key_child_firstname_field" => "Pierre et",
    "ccnbtc_key_child_name_field" => "Haza\u00ebl-Massieux",
    "ccnbtc_child_birthdate_field" => ""
);
$field_names = ["ccnbtc_key_child_firstname_field","ccnbtc_key_child_name_field"];
//echo json_encode(lib\extract_fields($arr, $field_names))."\n\n";

// ============================================
//  TEST array_map_attr
// ===========================================

$arr = array(
    array(
        "meta_id" => ["ccnbtc_key_persontype"],
        "html_id" => ["ccnbtc_key_persontype_field"]
    ),
);

$res = lib\array_map_attr($arr, 'html_id');
//echo json_encode($res)."\n\n";


// ============================================
//  TEST lib.file - path_full_to_relative
// ============================================

function test_path_full_to_relative() {
    $m = 'C:\wamp64\www/';
    $fp = 'C:\wamp64/www\wordpress\wp-content\plugins';
    echo lib\path_full_to_relative($m, $fp)."\n\n";
}
//test_path_full_to_relative();


// ============================================
// TEST lib\array_add_field
// ============================================

function test_array_add_field() {
    $arr = gen_random_array();
    print_out($arr,1);
    $res = lib\array_add_field($arr, 'test', function($key, $el, $a) { return $el['a']*100;});
    print_out($res, 2);
}
//test_array_add_field();



// ============================================
// TEST lib\build_html
// ============================================

function test_build_html() {
    $elements = array(
        'titre' => '<h1>{{titre}}</h1>',
        'paragraphe' => '<p>{{paragraphe}}<p>',
        'table' => '<table>',
        'ligne' => '<tr><td>{{ligne}}{{truc}}</td></tr>',
        'table_fin' => '</table>',
    );
    $data = array(
        'paragraphe' => 'Le paragraphe',
        'ligne' => ['ligne1', 'ligne2', 'ligne3'],
        'truc' => [' Lodate Dio !', ' deux', ' benedetto sia il nome di Gesù'],
    );
    $res = lib\build_html($elements, $data);
    print_out($res);
}
//test_build_html();

// ============================================
// TEST lib\get_callable_name
// ============================================

function riri($a) {return $a+3;}

function test_get_callable_name() {
    $res = array();
    $coco = function($a) {return $a+1;};
    function truc($a) {return $a;};
    $res[] = lib\get_callable_name('coco');
    $res[] = lib\get_callable_name($coco);
    $res[] = lib\get_callable_name('in_array');
    $res[] = lib\get_callable_name('undefined_function');
    $res[] = lib\get_callable_name('riri');
    $res[] = lib\get_callable_name('truc');
    $res[] = lib\get_callable_name(function() {return $a+1;});
    print_out($res);
}
//test_get_callable_name();


// ============================================
// TEST lib\eval_condition
// ============================================

function test_eval_condition() {
    $condition = "'on_site' == 'on_site'";
    $res = array();
    $res[] = lib\eval_condition($condition);
    print_out($res);
}
test_eval_condition();

// ============================================
// TEST lib\eval_operation
// ============================================

function test_eval_operation() {
    $operation = '1+1';
    $res = array();
    $res[] = lib\eval_operation($operation);
    $res[] = lib\eval_operation('"lodate"." "."Dio !"');
    print_out($res);
}
//test_eval_operation();

?>