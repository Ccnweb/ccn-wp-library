<?php

define( 'CCN_LIBRARY_PLUGIN_DIR', '..' );
require_once('test.php');


require_once(CCN_LIBRARY_PLUGIN_DIR . '/lib.php');
use \ccn\lib as lib;

$all = ['test_parse_html_snippet', 'test_build_html_tag'];
run_suite($all);

function test_build_html_tag() {
    $res = lib\build_html_tag('div', ['tag_name' => 'div', 'id' => '', 'class' => ['riri', 'coco']], 'the content');
    expect($res, '<div class="riri coco">the content</div>');
}

function test_parse_html_snippet() {
    $res = lib\parse_html_snippet("div#coco.riri.gio");
    expect($res, ['tag_name' => 'div', 'id' => 'coco', 'class' => ['riri', 'gio']]);
    $res = lib\parse_html_snippet("div#coco");
    expect($res, ['tag_name' => 'div', 'id' => 'coco', 'class' => []]);
    $res = lib\parse_html_snippet("div.truc-bidule");
    expect($res, ['tag_name' => 'div', 'id' => '', 'class' => ['truc-bidule']]);
    $res = lib\parse_html_snippet("div");
    expect($res, ['tag_name' => 'div', 'id' => '', 'class' => []]);
    $res = lib\parse_html_snippet("");
    expect($res, false);
}


// ====================================================
//	        HELPER FUNCTIONS FOR TESTS
// ====================================================

function run_suite($fn_list) {
	foreach($fn_list as $fn) {
		echo "\n\n========================\n";
		echo "$fn\n";
		echo "========================\n\n";
		call_user_func($fn);
	}
}

function check1($fn, $data) {
	foreach ($data as $k => $v) {
		$res = call_user_func($fn, $k);
		if ($res !== $v) {
			$fn_name = (gettype($fn) == 'string') ? $fn: 'anonymous';
			if ($res != $v) echo "ERROR ".$fn_name."(".$k.")\nExpected\t".json_encode($v)."\nGot\t\t".json_encode($res)."\n\n";
			else echo "TYPE ERROR ".$fn_name."(".$k.")\nExpected\t".json_encode($v)." as ".gettype($v)."\nGot\t\t".gettype($res)."\n\n";
		}
	}
}

function expect($res, $v, $label = '') {
	if ($res !== $v) {
		if ($res != $v) echo "ERROR in ".$label." \nExpected\t".json_encode($v)."\nGot\t\t".json_encode($res)."\n\n";
		else echo "TYPE ERROR \nExpected\t".json_encode($v)." as ".gettype($v)."\nGot\t\t".gettype($res)."\n\n";
	}
}

function expect_fn($fn, $res, $label = '') {
	$v = $fn($res);
	if (!$v) {
		echo "ERROR in ".$label."\nGot\t\t".json_encode($res)."\n\n";
	}
}

function expect_has_path($res, $path, $label = '', $cumul = '') {
	if (gettype($path) == 'string') $path = explode('/', trim($path, '/'));
	if (count($path) > 0) {
		if (!isset($res[$path[0]])) {
			echo "ERROR in ".$label." \nExpected to have path \t".$cumul.'/'.$path[0]."\nGot\t\t".json_encode($res)."\n\n";
		} else {
			expect_has_path($res[$path[0]], array_slice($path, 1), $label, $cumul.'/'.$path[0]);
		}
	}
}

function expect_greater_than($res, $limit, $label = '') {
    if ($res <= $limit) {
		echo "ERROR in ".$label." \nExpected result greater than \t".$limit."\nGot\t\t".json_encode($res)."\n\n";
	}
}

function expect_contains($res, $list) {
	if (!is_array($res)) {
		echo "ERROR\nExpected value of type array\n";
		echo "Got\t\t".json_encode($res)." of type ".gettype($res)."\n\n";
		return;
	}
	if (!is_array($list)) $list = [$list];
	$ok = [];
	foreach ($list as $v) {
		if (!in_array($v, $res)) $ok[] = $v;
	}

	if (count($ok) > 0) {
		echo "ERROR\nExpected to contain following values : ".json_encode($list)."\n";
		echo "Got : ".json_encode($res)."\n";
		echo "Which does not contain following values : ".json_encode($ok)."\n\n";
	}
}

function show($data) {
	echo json_encode($data)."\n";
}
?>