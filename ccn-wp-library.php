<?php
/**
* Plugin Name: CCN Librairie
* Description: Librairie de fonctions pour aider à créer des plugins Wordpress pour les sites de la Communauté du Chemin Neuf
* Version: 1.7.0
* Author: Communauté du Chemin Neuf
* GitHub Plugin URI: https://github.com/Ccnweb/ccn-wp-library.git
*/

define( 'CCN_LIBRARY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CCN_LIBRARY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once('log.php'); use \ccn\lib\log as log;
require_once('lib.php'); use \ccn\lib as lib;

// on utilise des emails au format HTML
function ccnlib_set_content_type(){
    return "text/html";
}
add_filter( 'wp_mail_content_type','ccnlib_set_content_type' );

// charge le fichier subscribe.js qui enverra les infos du formulaire via AJAX
function ccnlib_scripts() {
    //wp_enqueue_script( 'ccnlib-script', plugin_dir_url( __FILE__ ) . 'js/subscribe.js', array('jquery'), '20190105', true );
    // pass Ajax Url to subscribe.js
    // source : http://www.geekpress.fr/tuto-ajax-wordpress-methode-simple/
    //wp_localize_script('ccnlib-script', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
    wp_enqueue_script('ccnlib-jqueryui-easing', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js', array('jquery'), '1.3', 'all');
    
    // notification system (used to notify of POST request success or failure in HTML ajax forms)
    wp_enqueue_script('ccnlib-notifications-script', 'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js', array('jquery'), '20190105', true);
    wp_enqueue_style ('ccnlib-notifications-style', 'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css', array(), '20190105', 'all');

    // datepicker
    // source: http://t1m0n.name/air-datepicker/docs/
    wp_enqueue_script('ccnlib-datepicker-script', 'https://cdnjs.cloudflare.com/ajax/libs/air-datepicker/2.2.3/js/datepicker.min.js', array(), '20190107', true);
    wp_enqueue_script('ccnlib-datepicker-lang-script', 'https://cdnjs.cloudflare.com/ajax/libs/air-datepicker/2.2.3/js/i18n/datepicker.fr.min.js', array(), '20190107', true);
    wp_enqueue_style('ccnlib-datepicker-style', 'https://cdnjs.cloudflare.com/ajax/libs/air-datepicker/2.2.3/css/datepicker.min.css', array(), '20190107', 'all');

    // load css in /forms
    lib\enqueue_styles_regex(
                    CCN_LIBRARY_PLUGIN_DIR . '/forms', 
                    "/^load_front_.+\.css$/i", 
                    array(
                        'plugin_dir' => CCN_LIBRARY_PLUGIN_DIR, 
                        'plugin_url' => CCN_LIBRARY_PLUGIN_URL
                    )
    );

    // load js in /forms
    $res = lib\enqueue_scripts_regex(
        CCN_LIBRARY_PLUGIN_DIR . '/forms', 
        "/^load_front_.+\.js$/i", 
        array(
            'plugin_dir' => CCN_LIBRARY_PLUGIN_DIR, 
            'plugin_url' => CCN_LIBRARY_PLUGIN_URL
        )
    );

}
add_action( 'wp_enqueue_scripts', 'ccnlib_scripts');

function ccnlib_admin_scripts() {
    wp_enqueue_script('ccnlib-datepicker-script', 'https://cdnjs.cloudflare.com/ajax/libs/air-datepicker/2.2.3/js/datepicker.min.js', array(), '20190107', true);
    wp_enqueue_script('ccnlib-datepicker-lang-script', 'https://cdnjs.cloudflare.com/ajax/libs/air-datepicker/2.2.3/js/i18n/datepicker.fr.min.js', array(), '20190107', true);
    wp_enqueue_style('ccnlib-datepicker-style', 'https://cdnjs.cloudflare.com/ajax/libs/air-datepicker/2.2.3/css/datepicker.min.css', array(), '20190107', 'all');

    wp_enqueue_script('ccnlib-admin-script', CCN_LIBRARY_PLUGIN_URL . '/js/admin.js', array(), '20190125', true);
}
add_action( 'admin_enqueue_scripts', 'ccnlib_admin_scripts' );

require_once 'create-contact-form.php';

?>