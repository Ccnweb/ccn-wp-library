<?php 
namespace ccn\lib\html_fields;

require_once(CCN_LIBRARY_PLUGIN_DIR . '/log.php'); use \ccn\lib\log as log;
require_once(CCN_LIBRARY_PLUGIN_DIR . '/lib.php'); use \ccn\lib as lib;

// require all html field partial renderers from "html_fields" folder
lib\require_once_all_regex(CCN_LIBRARY_PLUGIN_DIR . '/html_fields/');

function build_html_from_form_data($form_data, $fields, $steps = array()) {
    /**
     * Builds an HTML representation of $form_data, according to $fields and $steps
     * Useful for easy email sending of form data
     */

    // ===========================================
    // == 1. == preparation
    // ===========================================
    $fields = prepare_fields($fields);

    if (empty($steps)) {
        $steps[] = array(
            'id' => "__only_step",
            'fields' => lib\array_map_attr($fields, 'id'),
        );
    }

     // ===========================================
    // == 2. == PARAMETERS
    // ===========================================

    $table_names = lib\array_transform_mapper($fields, 'id', 'html_label');
    
    // final HTML
    $html = "<table style=\"border-collapse:collapse;\">\n";
    $td_attrs_name = 'style="padding: 4px 12px;border:1px solid #447;background-color:#dedede;"';
    $td_attrs_val = 'style="padding: 4px 12px;border:1px solid #447;"';
    $td_attrs_title = 'style="background: gray;
                            color: white;
                            padding: 4px 12px;
                            border: 1px solid black;"';


    // ===========================================
    // == 3. == HTML build
    // ===========================================
    foreach ($steps as $step) {

        // == 3.a == preparation
        // case of a switch step
        if (isset($step['switch'])) {
            $step['fields'] = lib\array_flatten(array_map(function($sw) {return $sw['fields'];}, $step['switch']));
        }

        // == 3.b == build the step title
        // step title
        // the step title
        $titre = '<tr><td '.$td_attrs_title.' colspan="2">'.(isset($step['title']) ? $step['title'] : $step['id']).'</td></tr>';
        if (!isset($step['title']) && substr($step['id'], 0, 2) == '__') $titre = '';
        $html .= $titre;

        // for each field, with put line in the table
        foreach ($step['fields'] as $fid) {

            $field = lib\array_find_by_key($fields, 'id', $fid);
            if ($field === false) continue;

            $field_values = extract_field_post_data($field, $form_data);
            $field_names = get_field_html_labels($field);
            
            if (lib\array_has_string_key($field_values)) {

                foreach ($field_values as $key => $val) {
                    if (empty($val)) continue;
                    $html .= '<tr>';
                    $html .= '<td '.$td_attrs_name.'>'.$field_names[$key].'</td>';
                    $html .= '<td '.$td_attrs_val.'>'.$val.'</td>';
                    $html .= "</tr>\n";
                }

            } else { // repeat-group
                
                $field_lines = array_map(function($el) {
                    return '<td>' . implode($el, '</td><td>') . '</td>';
                }, $field_values);

                $field_header = '<td>' . implode('</td><td>', array_keys($field_values[0])) . '</td>';

                $val = '<table>'.$field_header.'<tr>' . implode('</tr><tr>', $field_lines) . '</tr></table>';

                $html .= '<tr>';
                $html .= '<td '.$td_attrs_name.'>'.$field['id'].'</td>';
                $html .= '<td '.$td_attrs_val.'>'.$val.'</td>';
                $html .= "</tr>\n";
            }
        }

    }

    return $html.'</table>';

}

// =======================================================================
//              FIELDS AND POST DATA
// =======================================================================

function extract_field_post_data($field, $post_data) {
    /**
     * retrieves
     */

    $values = array();

    // cas des fields repeat
    if ($field['type'] == 'REPEAT-GROUP') {
        if (!isset($post_data[$field['id']])) return log\error('INVALID_FIELD_STRUCTURE', 'lib.forms.php > In extract_post_data', false);
        $group_data = json_decode($post_data[$field['id']], true);
        if (!is_array($group_data)) return log\error('INVALID_POST_DATA', 'lib.forms.php > extract_post_data for repeat-field with id='.$field['id'], false);

        return $group_data;
    } 

    // cas des fields simples
    $ids = get_field_ids($field);
    foreach ($ids as $id) {
        $values[$id] = (isset($post_data[$id])) ? $post_data[$id] : '';
    }

    return $values;
}


// =======================================================================
//              BASIC FIELDS FUNCTIONS
// =======================================================================

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
    } else if (isset($field['html_label']) && is_array($field['html_label'])) {
        $ids = array();
        foreach ($field['html_label'] as $key => $val) {
            $ids[] = $field['id'].'_'.$key;
        }
        return $ids;
    } else {
        /* if (!$html) return [$field['id']];
        return [$field['id'].'_field']; */
        return [$field['id']];
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
    } else if (isset($field['html_label']) && is_array($field['html_label'])) {
        return array_keys($field['html_label']);
    } else {
        return '';
    }
}

function get_field_html_labels($field) {
    /**
     * 
     */

    if ($field['type'] == 'REPEAT-GROUP') {
        // TODO
    }

    $field_name = get_field_names($field);
    if ($field_name == '') $field_name = $field['id'];
    
    $names = (isset($field['html_label'])) ? $field['html_label'] : $field_name;
    if (is_array($names)) $names = array_values($names);
    if (!is_array($names)) $names = array($names);

    $ids = get_field_ids($field);
    return lib\array_build($ids, $names);
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
            //$new_id = $field['id'];
            $new_field = array_values($el_to_copy_from)[0];
            //$field['id'] = $new_id;
            $field = array_merge($new_field, $field);
        }
        if (field_structure_is_valid($field)) array_push($new_fields, $field);
    
    }
    
    return $new_fields;
}


// ========================================================================================
//              FIELDS VALIDATION
// ========================================================================================


function fields_structure_is_valid($fields) {
    /**
     * Vérifie que les fields en paramètre sont tous bien valides
     */

    // CHECK 1 = Tests unitaires
    $restants = array_filter($fields, '\ccn\lib\html_fields\field_structure_is_valid');
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
    // doit etre un bool ou un array de même longueur que fields\get_field_ids($field)

    return true;
}

?>