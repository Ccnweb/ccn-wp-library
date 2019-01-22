<?php
/**
 * ============ README ============
 * 
 * Pour créer un custom post type simple :
 * Il faut utiliser la fonction "create_custom_post_info" qui renvoie $args à insérer dans register_post_type( $cp_name, $args );
 * 
 * Pour créer des custom fields avec une metabox :
 * Il faut utiliser la fonction "create_custom_post_fields"
 * 
 * helpful sources : https://premium.wpmudev.org/blog/creating-meta-boxes/
 * 
 */


// we require here some low-level functions to help build this library
require_once('lib.php'); use \ccn\lib as lib;
// we require some helpers to create HTML elements
require_once('create-cp-html-fields.php');

/* ================================================ */
/* HIGH-LEVEL FUNCTIONS TO CREATE CUSTOM POST TYPES */
/* ================================================ */

// LA fonction qui fait tout pour créer des custom fields
// C'est peut-être la seule fonction à utiliser pour construire des custom fields,
// car les autres fonctions plus bas sont appelées à partir de celle-ci
function create_custom_post_fields($cp_name, $cp_slug, $metabox_opt, $prefix, $fields) {
    /**
     * @param string $cp_name       The name of the custom post (pour l'instant je ne garantis rien si c'est en plusieurs mots)
     * @param string $cp_slug       The slug of the custom post
     * @param string $metabox_opt   Array containing some options for the metabox in admin ui (title, ...)
     * @param string $prefix        The prefix used to namespace all variables, css classes etc. (e.g. "moncustompost")
     * @param array  $fields        The array of fields that will be added as custom fields to your custom post type
     * 
     * @TODO Vérifier qu'il n'y a pas de doublons dans les fields['id']
     * @TODO Ajouter possibilité de faire plusiuers metaboxes au lieu d'une seule contenant tout
     */

    $default_metabox_opt = array(
        'title' => 'Données '.$cp_name
    );
    $metabox_opt = lib\assign_default($default_metabox_opt, $metabox_opt);

    // 1. on crée les metakeys
    foreach ($fields as $f) {
        create_custom_post_key($cp_name, $f);
    }

    // 2. on crée la metabox avec tous les fields
    create_custom_post_metabox($cp_name, $metabox_opt['title'], $prefix, $fields);

    // 3. on crée la callback de sauvegarde des données de la metabox
    create_custom_post_savecbk($cp_name, $fields);

    // 4. on ajoute éventuellement certains fields comme colonne dans la vue "liste"
    create_custom_post_column_fields($cp_name, $fields);
}


// 1. Creates a custom post meta key
function create_custom_post_key($cp_name, $f) {
    // we deal with default values that may have been omitted
    $default_f = array(
        'type'         => 'string',
        'description'  => "Field description TBD",
        'single'       => true, // Return a single value of the type.
        'show_in_rest' => true,
    );
    $attributes = lib\assign_default($default_f, $f);
    
    // we register the meta field
    $post_meta_args = array("type", "description", "single", "show_in_rest");
    $attributes['type'] = get_wordpress_custom_field_type($attributes['type']);
    $args = lib\extract_fields($attributes, $post_meta_args);
    register_post_meta( $cp_name, $f['id'], $args );
}


// 2. Creates a meta box for a custom post
function create_custom_post_metabox($cp_name, $metabox_title, $prefix, $fields) {
    $metabox = function() use ($cp_name, $prefix, $fields, $metabox_title) {
        
        // make sure the form request comes from WordPress
	    //wp_nonce_field( basename( __FILE__ ), $metabox_title );

        $metabox_html = function($post) use ($prefix, $fields) {
            ?>
            <div class="<?php echo $prefix; ?>_custom_metabox" style="display:flex;flex-direction:column">

                <?php /* on insère chaque field */
                foreach ($fields as $field):

                    $value = get_post_meta($post->ID, $field["id"], true); // le nom de la metakey
                    $label = (array_key_exists('html_label', $field)) ? $field['html_label'] : $field['id']; // le label du champs html
                    ?>

                    <div class="metabox_field_container">
                        <label for="<?php echo $field['id']; ?>_field"><?php echo $label; ?></label>
                        <?php echo create_HTML_field($field, array('value' => $value)); ?>
                    </div>

                <?php endforeach; ?>

            </div>
            <?php
        };

        add_meta_box(
            $cp_name.'_custom_metabox', // Unique ID
            $metabox_title,             // Box title
            $metabox_html,              // Content callback, must be of type callable
            $cp_name,                   // Post type
            'advanced',                 // $context (default = 'advanced')
            'high'                      // priorité d'apparition dans l'interface
        );
    };

    add_action('add_meta_boxes_'.$cp_name, $metabox);
}

// 3. Creates all the necessary cbks to save data from metaboxes
function create_custom_post_savecbk($cp_name, $fields) {
    $save_data = function($post_id) use ($fields) {

        // Check the user's permissions.
        if ( !current_user_can('edit_post', $post_id) ) return;

        foreach ($fields as $f) {
            $field_id = $f['id'].'_field';

            if (array_key_exists($field_id, $_POST)) {
                update_post_meta(
                    $post_id,
                    $f['id'],
                    sanitize_text_field($_POST[$field_id])
                );
            }
        }
    };

    add_action('save_post_'.$cp_name, $save_data);
}

// 4. Adds some fields as columns in the "list" view in admin panel
// source : https://catapultthemes.com/add-acf-fields-to-admin-columns/
function create_custom_post_column_fields($cp_name, $fields) {
    // A. We first add the columns in the interface
    $fields_as_columns = array();
    foreach ($fields as $f) {
        if (array_key_exists('show_as_column', $f)) $fields_as_columns[$f['id']] = __($f['show_as_column']);
    }

    $add_acf_columns = function( $columns ) use ($fields_as_columns) {
        return array_merge ( $columns, $fields_as_columns);
    };

    add_filter ( 'manage_'.$cp_name.'_posts_columns', $add_acf_columns );

    // B. Then we show the values of the meta fields
    $custom_column = function ( $column, $post_id ) use ($fields_as_columns) {
        if (array_key_exists($column, $fields_as_columns)) {
            echo get_post_meta ( $post_id, $column, true );
        }
    };
    add_action ( 'manage_'.$cp_name.'_posts_custom_column', $custom_column, 10, 2 );
}


// Génère les $args pour créer un custom post avec register_post_type( $nom_post_type, $args)
// - nom du custom post sans accents !
// - slug généré = nom du post avec des tirets à la place des espaces
function create_custom_post_info(
        $name_singular_lowercase, 
        $genre = "m", 
        $post_icon = 'dashicons-info', 
        $supports = array( 'title', 'editor', 'thumbnail')
    ) {

    // variantes du nom du post (singulier, pluriel, slug, ...)
    $name_singular_camelcase = ucwords($name_singular_lowercase);
    $name_plural_lowercase = implode(" ", array_map(function($el){return $el."s";}, explode(" ", $name_singular_lowercase)));
    $name_plural_camelcase = implode(" ", array_map(function($el){return $el."s";}, explode(" ", $name_singular_camelcase)));
    $slug = str_replace(" ", "-", $name_plural_lowercase);


    $tous = ($genre == 'm') ? "Tous" : "Toutes";
    $e = ($genre == "m") ? "" : "e";
    $nouveau = ($genre == "m") ? "nouveau" : "nouvelle";
    $le = ($genre == "m") ? "le " : "la ";
    $first_letter_lower = substr($name_singular_lowercase,0,1);
    if (in_array($first_letter_lower, array("a", "e", "i", "o", "u", "y"))) $le = "l'";

    // On rentre les différentes dénominations de notre custom post type qui seront affichées dans l'administration
	$labels = array(
		// Le nom au pluriel
		'name'                => _x( $name_plural_camelcase, $name_plural_camelcase),
		// Le nom au singulier
		'singular_name'       => _x( $name_singular_camelcase, $name_singular_camelcase),
		// Le libellé affiché dans le menu
		'menu_name'           => __( $name_plural_camelcase),
		// Les différents libellés de l'administration
		'all_items'           => __( $tous.' les '.$name_plural_lowercase),
		'view_item'           => __( 'Voir les '.$name_plural_lowercase),
		'add_new_item'        => __( 'Ajouter un'.$e.' '.$nouveau.' '.$name_singular_lowercase),
		'add_new'             => __( 'Ajouter'),
		'edit_item'           => __( 'Éditer '.$le.$name_singular_lowercase),
		'update_item'         => __( 'Modifier '.$le.$name_singular_lowercase),
		'search_items'        => __( 'Rechercher un'.$e.' '.$name_singular_lowercase),
		'not_found'           => __( 'Non trouvé'.$e),
		'not_found_in_trash'  => __( 'Non trouvé'.$e.' dans la corbeille'),
	);
	
	// On peut définir ici d'autres options pour notre custom post type
	
	$args = array(
		'label'               => __( $name_plural_camelcase ),
		'description'         => __( 'Tout sur les '.$name_plural_lowercase),
		'labels'              => $labels,
        'menu_icon'           => $post_icon,
		// On définit les options disponibles dans l'éditeur de notre custom post type ( un titre, un auteur...)
		'supports'            => $supports,
		/* 
		* Différentes options supplémentaires
		*/	
		'hierarchical'        => false,
		'public'              => true,
		'has_archive'         => true,
        'rewrite'			  => array( 'slug' => $slug),
        'show_in_rest'        => true,

    );
    
    return $args;
}

?>