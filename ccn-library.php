<?php
/**
* Plugin Name: CCN Librairie
* Description: Librairie de fonctions pour aider à créer des plugins Wordpress pour les sites de la Communauté du Chemin Neuf
* Version: 1.0.1
* Author: Communauté du Chemin Neuf
* GitHub Plugin URI: https://github.com/Ccnweb/ccn-wp-library.git
*/

define( 'CCN_LIBRARY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );


// charge le fichier subscribe.js qui enverra les infos du formulaire via AJAX
function ccnlib_scripts() {
    //wp_enqueue_script( 'ccnlib-script', plugin_dir_url( __FILE__ ) . 'js/subscribe.js', array('jquery'), '20190105', true );
    // pass Ajax Url to subscribe.js
    // source : http://www.geekpress.fr/tuto-ajax-wordpress-methode-simple/
    //wp_localize_script('ccnlib-script', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
    
    // notification system (used to notify of POST request success or failure in HTML ajax forms)
    wp_enqueue_script('ccnlib-notifications-script', 'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js', array('jquery'), '20190105', true);
    wp_enqueue_style ('ccnlib-notifications-style', 'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css', array(), '20190105', 'all');

    // datepicker
    // source: http://t1m0n.name/air-datepicker/docs/
    wp_enqueue_script('ccnlib-datepicker-script', 'https://cdnjs.cloudflare.com/ajax/libs/air-datepicker/2.2.3/js/datepicker.min.js', array(), '20190107', true);
    wp_enqueue_script('ccnlib-datepicker-lang-script', 'https://cdnjs.cloudflare.com/ajax/libs/air-datepicker/2.2.3/js/i18n/datepicker.fr.min.js', array(), '20190107', true);
    wp_enqueue_style('ccnlib-datepicker-style', 'https://cdnjs.cloudflare.com/ajax/libs/air-datepicker/2.2.3/css/datepicker.min.css', array(), '20190107', 'all');

}
add_action( 'wp_enqueue_scripts', 'ccnlib_scripts');


?>