<?php

// ============================================
//  SOME HELPFUL FUNCTIONS TO WRITE TESTS
// ============================================

function print_out($o, $end = 1) {
    echo json_encode($o);
    for($i = 0; $i < $end; $i++) echo "\n";
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