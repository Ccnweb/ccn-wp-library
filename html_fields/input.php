<?php
namespace ccn\lib\html_fields;

require_once(CCN_LIBRARY_PLUGIN_DIR . '/lib.php'); use \ccn\lib as lib;

function render_HTML_input($field, $options = array()) {
     /**
     * Construit un élément HTML de type <input type="text" ...>
     * 
     * ## SOMMAIRE
     * 1. Gestion des options
     * 2. Calcul des paramètres HTML poru le rendu
     * 3. Rendu HTML
     */

    // == 1. == Gestion des options
    $field_default = array(
        'id'                => 'dummy_id',     // l'id du custom meta field correspondant (ou post_title etc...)
        'type'              => 'text', // 'text', 'date', 'tel', 'postal_code' ou tout attribut accepté par <input type="...">
        'required'          => true,
        'regex_pattern'     => '', // le pattern regex à ajouter éventuellement
        'html_label'        => 'text input',
        'html_attributes'   => array(), // ajout d'attributs html supplémentaires sous forme de $key => $value
        'msg_info'          => '', // message à afficher pour info (uniquement si $options['style'] != 'simple')
        'msg_error'         => '', // message à afficher si erreur (uniquement si $options['style'] != 'simple')
    );
    $field = lib\assign_default($field_default, $field);

    $options_default = array(
        'style'     => 'simple', // 'simple', ou 'bootstrap', 
        'label'     => 'placeholder', // = 'label', 'placeholder', 'both' (utile uniquement si style != 'simple')
        //'label_position' => 'top', // 'top' ou 'left' pour dire où se situe le label par rapport au champs
        'value'     => '',
        'multiple'  => '', // indice qui indique de la combien-ième instance il s'agit
    );
    $options = lib\assign_default($options_default, $options);

    // == 2. == Paramètres HTML calculés
    // gestion des types particuliers:
    // --> date
    $ifdate = ($field['type'] == 'date') ? ' data-date-format="dd-mm-yyyy" data-language="fr" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}"' : '';
    $ifdateclass = ($field['type'] == 'date') ? ' datepicker-here' : '';

    // l'id du field HTML
    $field_id_html = $field['id']."_field";
    if ($options['multiple'] != '') $field_id_html .= '_'.$options['multiple'];

    // le name du field HTML
    $field_name_html =  $field['id']."_field";
    if ($options['multiple'] != '') $field_name_html .= '[]';
    
    // regex
    $regex_pattern = (isset($field['regex_pattern'])) ? $field['regex_pattern'] : get_mytype_HTML_pattern($field['type']);
    $ifregex = ($regex_pattern != '') ? ' pattern="'.$regex_pattern.'" ' : '';
    
    // specific html attributes
    $html_attributes = (count($field['html_attributes']) > 0) ? implode(' ', lib\array_map_assoc($field['html_attributes'], function($k, $v) {return $k.'="'.str_replace('"','\"', $v).'"';})) : '';
    
    // champs requis ou non
    $ifrequired = ($field['required']) ? ' required ' : '' ;
    
    // ajouter un label ou non avant le <input>
    $iflabel = (in_array($options['label'], array('label', 'both'))) ? '<label for="'.$field_id_html.'">'.$field['html_label'].'</label>' : '';
    
    // ajouter un placeholder
    $ifplaceholder = (in_array($options['label'], array('placeholder', 'both'))) ? ' placeholder="'.$field['html_label'].'" ' : "";
    
    // ajouter un élément d'information pour remplir le champs
    $ifdescription = ($field['msg_info']) ? 'aria-describedby="'.$field['id'].'_description"': '';
    $if_msg_info = ($field['msg_info']) ? '<small id="'.$field['id'].'_description" class="form-text text-muted ccnlib_field_info">'.$field['msg_info'].'</small>': '';
    // TODO ajouter un message qui apparaît si le champs est mal rempli 
    $if_msg_error = ($field['msg_error']) ? '': '';


    // == 3.a == rendu HTML simple

    $input = '<input    type="'.get_HTML_field_input_type($field['type']).'" 
                        class="form-control postbox ccnlib_post'.$ifdateclass.'" 
                        id="'.$field_id_html.'" 
                        name="'.$field_name_html.'" 
                        '.$html_attributes.'
                        '.$ifdate.'
                        '.$ifregex.'
                        '.$ifrequired.'
                        '.$ifdescription.' 
                        '.$ifplaceholder.' 
                        value="'.$options['value'].'"
            >';

    if ($options['style'] == 'simple') return $input;

    // == 3.b == rendu HTML bootstrap
    // TODO ajouter le message d'erreur
    $html = '<div class="form-group">
        '.$iflabel.'
        '.$input.'
        '.$if_msg_info.'
    </div>';

    return $html;
}


function get_mytype_HTML_pattern($mytype) {
    /**
     * returns a regex pattern as string, to be used in HTML file, corresponding to the mytype 
     * (e.g. "postal_code" returns "[0-9]{5}")
     */
    if      ($mytype == "postal_code")  {return "[0-9]{5}";}
    else if ($mytype == "date")         {return "[0-9]{2}-[0-9]{2}-[0-9]{4}";}
    else if ($mytype == "tel")          {return "\+?[0-9]{10,11}";}
    else {return "";}
}


function get_HTML_field_input_type($mytype) {
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
?>