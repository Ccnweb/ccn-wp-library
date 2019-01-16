<?php

require_once(CCN_LIBRARY_PLUGIN_DIR . '/lib.php');

function render_HTML_radio($field, $options) {
    // == 1. == 
    $field_default = array(
        'id' => 'dummy_id',     // l'id du custom meta field correspondant (ou post_title etc...)
        //'init_val' => 'value1', // précise la valeur à checker par défaut à l'init
        'options' => array(
            'value1' => 'label1',
            'value2' => 'label2',
        ),
        'options_preciser' => array('value1'), // les id des options qui doivent avoir un champs 'input type="text"' en plus 
    );
    $field = assign_default($field_default, $field);

    $options_default = array(
        'layout' => 'column', // 'row', 'column'
        'value' => '',
    );
    $options = assign_default($options_default, $options);


    // == 2. == PARAMS
    $ifinline = ($options['layout'] == 'row') ? ' form-check-inline' : ''; // display radio buttons in row (inline) or column


    // == 3. == HTML Bootstrap
    $html = '<div class="form-radio-container">';
    $compteur = 1;
    foreach ($field['options'] as $value => $label) {
        $curr_id = $field['id'].'_field__'.$compteur;
        $ifchecked = ($value == $options['value']) ? 'checked': '';
        $if_a_preciser = (in_array($value, $field['options_preciser'])) ? '<input type="text" class="form-control" name="'.$curr_id.'_preciser" id="'.$curr_id.'_preciser">' : '';

        $html .= '<div class="form-check'.$ifinline.'">
                    <input class="form-check-input" type="radio" name="'.$field['id'].'_field" id="'.$curr_id.'" value="'.$value.'" '.$ifchecked.'>
                    <label class="form-check-label" for="'.$curr_id.'">'.$label.'</label>
                    '.$if_a_preciser.'
                </div>';
        $compteur++;
    }

    $html .= '</div>';
    return $html;

}

?>