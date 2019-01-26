<?php

/**
 * README
 * 
 * Here are some helper functions related to HTML fields rendering, <input>, <date>, ...
 * used in create-custom-post-type.php for example
 * 
 * accepted field types = number, text, password, email, postal_code, date, tel and all fields in html_fields/
 * 
 */

require_once('log.php'); use \ccn\lib\log as log;
require_once('lib.php'); use \ccn\lib as lib;

// require all html field partial renderers from "html_fields" folder
lib\require_once_all_regex(CCN_LIBRARY_PLUGIN_DIR . '/html_fields/');
use \ccn\lib\html_fields as fields;


function create_HTML_repeat_group($group_repeat, $post) {
    /**
     * Génération du code HTML pour un GROUP-REPEAT de fields
     * Cette fonction est appelée lors de la création de la metabox par exemple
     * 
     * ## SOMMAIRE
     * 1. On récupère les données de la DB
     * 2. On crée le code HTML pour les éléments enregsitrés
     * 3. On crée le code HTML pour la partie dynamique
     * 4. On crée le code JS pour la partie dynamique
     */

    $group_id = $group_repeat['id'];

    // ================================
    // == 1. == on récupère toutes les valeurs des repeatable_fields de ce groupe depuis la DB
    // ================================

    $groups_values = get_post_meta($post->ID, $group_id, true);

    // ================================
    // == 2. == on crée le code HTML avec tous les fields
    // ================================

    $button_delete = '<button class="ccnlib_delete_repeat_element">Supprimer</button>';

    $html = '';
    $i = 0;
    if (!$groups_values) $groups_values = array();

    foreach ($groups_values as $group) {
        $html .= '<div class="repeat-element">';

        foreach ($group_repeat['fields'] as $field) {
            $ids_meta_keys = get_field_ids($field, true); // faut savoir, les ids des meta keys pour les repeat-groups, sont les mêmes que les ids HTML, càd avec le _field à la fin (sorry, c'est pas propre)
            $field_names = get_field_names($field);
            $curr_values = (empty($field_names)) ? $group[$ids_meta_keys[0]] : lib\array_build($field_names, array_values(lib\extract_fields($group, $ids_meta_keys)));
            $curr_options = array(
                'value' => $curr_values,
                'multiple' => strval($i),
            );
            $html .= create_HTML_field($field, $curr_options);
        }
        $i++;

        // on ajoute le bouton poubelle pour pouvoir l'enlever
        $html .= $button_delete;
        $html .= '</div>';
    }

    // ================================
    // == 3. == on ajoute le HTML de la partie dynamique (bouton +Ajouter et modèle des champs vides)
    // ================================

    // on ajoute un bouton +Ajouter pour pouvoir ajouter un nouvel élément
    $html .= '<button id="'.$group_id.'_button_add_element" class="add_repeat_element">Ajouter</button>';

    // on ajoute le modèle à copier quand on clique sur +Ajouter (le jQuery reprendra alors ce code pour l'insérer)
    $html .= '<div id="'.$group_id.'_hidden_group_model" style="display:none">
                <div class="repeat-element">';
                    foreach ($group_repeat['fields'] as $field) {
                        $html .= create_HTML_field($field, array('multiple' => 'hidden'));;
                    }
    $html .= $button_delete;
    $html .= '  </div>
            </div>';
    
    // ================================
    // == 4. == on ajoute le code js/jQuery pour gérer les aspects dynamiques de l'ajout/suppression d'éléments
    // ================================

    $js_data = array('group_id' => $group_id);
    $html .= lib\get_js_script(CCN_LIBRARY_PLUGIN_DIR . 'js/groupe-repeat-template.js.tpl', $js_data);

    return $html;
}


function create_HTML_field($field, $options) {
    /**
     * Génération du code HTML pour un field défini dans html_fields/
     */

    // case of simple HTML input elements
    if (in_array($field['type'], array('text', 'password', 'email', 'postal_code', 'date', 'number', 'tel'))) {
        return fields\render_HTML_input($field, $options);
    // case of complex HTML elements
    } else if (function_exists('\ccn\lib\html_fields\render_HTML_'.$field['type'])) {
        $res = call_user_func('\ccn\lib\html_fields\render_HTML_'.$field['type'], $field, $options);
        if ($res === false) return '<div class="html_rendering_error">Impossible de faire le rendu du champs '.$field['type'].' (id = "'.$field['id'].'")</div>';
        return $res;
    } else {
        die("Cannot render type ".$field['type']." in HTML");
        log\error("INVALID_HTML_FIELD_TYPE", "Cannot render type ".$field['type'].' in HTML');
        return "<div class=\"html_rendering_error\">Cannot render type ".$field['type']." in HTML</div>";
    }
}


function get_wordpress_custom_field_type($mytype) {
    /**
     * This returns the wordpress type from the mytype (e.g. 'email' => 'string')
     * Valid values are 'string', 'boolean', 'integer', and 'number'.
     */
    $corresp = array(
        'date' => 'string',
        'email' => 'string',
        'postal_code' => 'string',
        'text' => 'string',
        'tel' => 'string',
        'number' => 'integer',
        'radio' => 'string',
        'dropdown' => 'string',
    );
    if (isset($corresp[$mytype])) return $corresp[$mytype];
    else return 'string';
}

function get_HTML_field_input_type_old($mytype) { // TODO delete this
    /**
     * Converts the "mytype" into an HTML input type 
     * (works only for mytypes that have are rendered in HTML input elements)
     */
    if (in_array($mytype, array('string', 'date', 'postal_code'))) {
        return 'text';
    } else { // email => email, 'number' => 'number', 'tel' => 'tel', 'radio' => 'radio'
        return $mytype;
    }
}

function get_field_ids($field, $html = false) {
    /**
     * Fait appel aux fonctions de type get_field_ids_{nom_du_field} qui sont stockées dans le dossier html_fields/
     * Elle renvoie la liste des ID des meta_keys de ce field
     * 
     * @param bool $html    indique s'il faut les IDs des meta keys ou des fields HTML ('ccnlib_my_key' ou 'ccnlib_my_key_field')
     * 
     */

    
    if (function_exists('\ccn\lib\html_fields\get_field_ids_'.$field['type'])) {
        $res = call_user_func('\ccn\lib\html_fields\get_field_ids_'.$field['type'], $field, $html);
        if ($res === false) log\error('HTML_FIELD_RETRIEVE_IDS_FAILED', 'Failed to retrieve field meta key ids for field with id='.$field['id'].' of type '.$field['type']);
        else return $res;
    } else {
        if (!$html) return [$field['id']];
        return [$field['id'].'_field'];
    }
}

function get_field_names($field) {
    /**
     * Fait appel aux fonctions de type get_field_names_{type_du_field} qui sont stockées dans le dossier html_fields/
     * Elle renvoie la liste des noms des keys pour $options['value'] de ce field
     * 
     */
    
    if (function_exists('\ccn\lib\html_fields\get_field_names_'.$field['type'])) {
        $res = call_user_func('\ccn\lib\html_fields\get_field_names_'.$field['type'], $field);
        if ($res === false) log\error('HTML_FIELD_RETRIEVE_NAMES_FAILED', 'Failed to retrieve field names for field with id='.$field['id'].' of type '.$field['type']);
        else return $res;
    } else {
        return [];
    }
}

function get_required_fields($field, $html = false) {
    /**
     * renvoie les meta keys requises par $field
     */

    $meta_keys = get_field_ids($field, $html);

    if (isset($field['required'])) {
        if (gettype($field['required']) == 'boolean' && $field['required']) return $meta_keys;
        if (is_array($field['required'])) {
            if (count($field['required']) == count($meta_keys)) {
                $meta_keys = lib\array_mask($meta_keys, $field['required']);
            } else {
                log\error('INVALID_FIELD', 'The field is invalid because the length of the required array != number of meta keys ids for this type of field. Details '.json_encode($field));
            }
        } 
    } else if ($field['type'] == 'REPEAT-GROUP') {
        $res = array_map(function($f) { return get_required_fields($f, true);}, $field['fields']);
        $meta_keys = lib\array_flatten($res);
    }

    return $meta_keys;
}

function get_value_from_db($post, $field) {
    /**
     * Fait appel aux fonction de type get_value_from_db_{type_du_field} définies dans html_fields/
     * Renvoie un array('value' => ...)
     */

    if (function_exists('\ccn\lib\html_fields\get_value_from_db_'.$field['type'])) {
        $res = call_user_func('\ccn\lib\html_fields\get_value_from_db_'.$field['type'], $post, $field);
        if ($res === false) {
            log\error('HTML_FIELD_RETRIEVE_DATA_FAILED', 'Failed to retrieve post data for field with id='.$field['id'].' of type '.$field['type'].' in post with id='.$post->ID);
            return array('value' => '');
        }
        return $res;
    } else {
        $list_names = get_field_names($field);

        if (!empty($list_names)) {
            $res = array('value' => array());
            foreach ($list_names as $partie) {
                $res['value'][$partie] = get_post_meta($post->ID, $field["id"].'_'.$partie, true);
            }
            return $res;

        } else {
            $value = get_post_meta($post->ID, $field["id"], true);
            return array('value' => $value);
        }
    }
}

function prepare_fields($fields) {
    /**
     * Prépare les champs $fields en parsant les champs 'copy'
     * et en enlevant les champs invalides
     */

    $b = fields_structure_is_valid($fields);

    $new_fields = array();

    foreach ($fields as $field) {
    
        // on gère les champs "copy", ce sont les champs qui sont copiés d'autres champs existants
        if (isset($field['copy'])) {
            $el_to_copy_from = array_filter($fields, function($el) use ($field) {return $el['id'] == $field['copy'];});
            if (empty($el_to_copy_from)) {
                log\error('INVALID_COPY_FIELD', 'in create-custom-post-type.php > create_custom_post_fields : Invalid id specified in copy key, no matching field found. Details : copy_id = '.$f['copy']);
                continue; // saute cet élément de la boucle courante
            }
            $new_id = $field['id'];
            $field = array_values($el_to_copy_from)[0];
            $field['id'] = $new_id;
        }
        if (field_structure_is_valid($field)) array_push($new_fields, $field);
    
    }
    
    return $new_fields;
}

function fields_structure_is_valid($fields) {
    /**
     * Vérifie que les fields en paramètre sont tous bien valides
     */

    // CHECK 1 = Tests unitaires
    $restants = array_filter($fields, 'field_structure_is_valid');
    if (count($restants) < count($fields)) return false;

    // CHECK 2 = Tous les id sont uniques
    // TODO

    // CHECK 3 = Tous les id dans les champs copy existent
    // TODO


    return true;
    
}

function field_structure_is_valid($field) {
    /**
     * Vérifie que le field en paramètre est bien valide
     */

    if (!isset($field['id'])) {log\error('INVALID_FIELD_STRUCTURE', 'field has no "id" attribute : json='.json_encode($field)); return false;}
    if (!isset($field['type']) && !isset($field['copy'])) {log\error('INVALID_FIELD_STRUCTURE', 'field has no "type" or "copy" attribute : json='.json_encode($field)); return false;}

    // TODO regarder dans les champs 'REPEAT-GROUP' aussi...

    // TODO vérifier que le champs required est valide ou omis
    // doit etre un bool ou un array de même longueur que get_field_ids($field)

    return true;
}


// ==================================================================

function parse_js_condition($metabox_id, $metabox, $fields) {
    $rules = array();

    // =====================================================
    // on parse la condition de la metabox elle-même si besoin
    // =====================================================
    if (isset($metabox['condition'])) {
        $new_rule = build_js_rule($metabox_id, $metabox['condition'], $fields);
        if (empty($new_rule['source_ids'])) $new_rule['source_selector'] = '#'.$metabox_id.' [name]'; // by default it's any element in the metabox that has a "name" attribute
        array_push($rules, $new_rule);
    }

    // =====================================================
    // on parse les conditions relatives aux fields si besoin
    // =====================================================
    if (isset($metabox['field_conditions'])) {
        
        foreach ($metabox['field_conditions'] as $field_id => $condition) {

            $new_rule = build_js_rule($field_id, $condition, $fields);
            if (empty($new_rule['source_ids'])) $new_rule['source_selector'] = '#'.$metabox_id.' [name]'; // by default it's any element in the metabox that has a "name" attribute
            array_push($rules, $new_rule);

        }

    }

    return json_encode($rules);
}

function build_js_rule($target_id, $condition, $fields) {
    /**
     * Construit une rule comme voulu par la fonction JS load_custom_logic dans js/metabox_template.tpl.js
     */

    $new_rule = array(
        'target_selector' => '#'.$target_id,
        'source_ids' => [],
        'condition' => $condition,
    );

    // on cherche les clés à remplacer par des valeurs
    preg_match_all("/\{\{([^\}]+)\}\}/", $condition, $matches);

    if (count($matches) > 1) {

        // on prépare : ================================
        // créer le mapper 'meta_key' => 'html_id'
        // =============================================
        $all_field_ids = array_map(function($f) {
            return array(
                'meta_id' => get_field_ids($my_field, false), // ids des meta keys
                'html_id' => get_field_ids($my_field, true), // ids HTML
            );
        }, $fields);
        $id_mapper = lib\array_transform_mapper($all_field_ids, 'meta_id', 'html_id');

        // we detect the source_ids
        $match_unique = array_unique($matches[1]);
        $source_ids = array_map(function($id) use ($id_mapper) {
            if (isset($id_mapper[$id])) return $id_mapper[$id];
            return '';
        }, $match_unique);
        $new_rule['source_ids'] = lib\array_filter_nonempty($source_ids);
        unset($new_rule['source_selector']); // on supprime le source_selector par défaut, pour ne pas qu'il écrase le comportement de 'source_ids'

        // in the condition string, we replace the meta key ids by the html ids
        $mapping_meta_html = array_map(function($el) { 
            $res = lib\array_swap_chaussette($el);
            return array_map(function ($el2) {
                array($el2['meta_id'] => '{{'.$el2['html_id'].'}}');
            }, $res);
        }, $source_ids); // TODO
        $mapping_meta_html = lib\array_flatten($mapping_meta_html);
        $new_rule['condition'] = lib\parseTemplateString($condition, $mapping_meta_html);

    }

    return $new_rule;
}

function parse_js_condition_old($metabox_id, $condition_str) {
    /**
     * transforme une condition de type "{{my_key}} == 'val1' || {{my_key2}} == 'val2'"
     * en du code js qui affiche ou non la $metabox_id selon si la condition est vraie ou non
     * 
     * Ce code sera intégré dans les templates js dans les balises {{condition_logic}} normalement
     * 
     */

    // on cherche les clés à remplacer par des valeurs
    preg_match_all("/\{\{([^\}]+)\}\}/", $condition_str, $matches);

    if (count($matches) < 2) return $condition_str; 
    
    // selector for jQuery .change()
    $match_unique = array_unique($matches[1]);
    $html_ids = array_map(function($id) {return $id.'_field';}, $match_unique);
    $list_selectors = '#'.implode(', #', $html_ids);
    $array_ids = implode("', '", $html_ids);

    // js condition string
    $js_condition_str = $condition_str;
    for ($i = 0; $i < count($match_unique); $i++) {
        $js_condition_str = str_replace('{{'.$match_unique[$i].'}}', '"${list_values['.$i.']}"', $js_condition_str);
    }

    return "let custom_logic_fun = function() {
        let list_values = ['".$array_ids."'].map(id => getVal(id));
        let condition_str = `".$js_condition_str."`;
        if (eval(condition_str)) $('#".$metabox_id."').show();
        else $('#".$metabox_id."').hide();
    }
    custom_logic_fun();
    $('".$list_selectors."').change(custom_logic_fun);";
}

?>