jQuery(document).ready(function($) {

    // on initialise les tooltips bootstrap
    // source : https://getbootstrap.com/docs/4.1/components/tooltips/
    $('[data-toggle="tooltip"]').tooltip();

    // on itinialise les datepickers dans les formulaires
    $('.datepicker-here').datepicker();

    // load validity check of my fields
    $('.ccnlib_post').each(function() {
        let el = $(this);
        el.focusout(function() {
            ok = validate_element(el)
            if (ok) {
                el.removeClass('invalid');
                el.next('.invalid-feedback').removeClass('show');
            } else {
                el.addClass('invalid');
                el.next('.invalid-feedback').addClass('show');
            }
        })
    });

});


// ===============================================================
//              VALIDATION DES FORMULAIRES
// ===============================================================

function validate_elements(elements) {
    let ok = true;
    elements.each(function() {
        if (!validate_element(jQuery(this))) ok = false;
    })
    return ok;
}

function validate_element(el) {
    if(el[0].validity && el[0].validity.valid) {
        return true;
    } else if (el[0].validity) {
        return false;
    } else if (el.hasClass('form-radio-container')) {
        return validate_radio(el)
    } else {
        console.log('warning in validate_element() : strange element (validated by default) : ', el)
        return true
    }
}

function validate_radio(container_el) {
    let ok = false;
    container_el.find('input').each(function() {
        if (jQuery(this).is(':checked')) ok = true;
    })
    return ok;
}


// ===============================================================
// fonctions utiles pour la custom logic des formulaires
// ===============================================================

function load_custom_logic(rules) {
    /**
     * Ici on gère les embranchements/apparition/disparition de metaboxes et de fields
     * 
     * une rule a les attributs :
     * - source_ids (array des id HTML des éléments à monitorer), 
     * - (optional) source_selector qui override source_ids éventuellement
     * - target_selector (les éléments à show/hide) 
     * - condition (la condition à satisfaire)
     */

    for (let rule of rules) {
        let source_selector = (rule.source_selector) ? rule.source_selector : '#' + rule.source_ids.join(', #');

        // we define the current rule function
        let curr_check_rule = function() {
            if (check_rule_condition(rule)) jQuery(rule.target_selector).show();
            else jQuery(rule.target_selector).hide();
        }

        // we execute the rule now
        curr_check_rule();

        // we register the rule in a change event
        jQuery(source_selector).change(curr_check_rule);
    }
}

function check_rule_condition(rule) {
    /**
     * Renvoie true ou false selon si la rule est ou non vérifiée
     */
    let list_values = rule.source_ids.map(id => getVal(id));
    let parsed_condition = rule.condition;
    for (let i = 0; i < rule.source_ids.length; i++) {
        parsed_condition = parsed_condition.replace(new RegExp("\\{\\{"+rule.source_ids[i]+"\\}\\}", "g"), '"'+list_values[i]+'"');
    }
    return eval(parsed_condition);
}

// ================================================
//          HELPER FUNCTIONS
// ================================================

function getVal(el_id) { // existe aussi dans forms-template.js.tpl
    /**
     * Récupère la valeur à envoyer par HTTP POST de l'élément HTML avec l'id el_id
     */
    let el = jQuery('#' + el_id);
    if (!el) el = jQuery("input[name='"+el_id+"']:checked"); // dans le cas d'une checkbox

    // si l'élément n'existe pas, c'est qu'il doit peut-être être calculé
    //if (typeof custom_data_attributes[el_id] == 'function') {console.log('r', custom_data_attributes[el_id]()); return custom_data_attributes[el_id]();}
    if (el.length < 1) return 'unknown element ' + el_id;

    // si l'élément existe, on récupère sa valeur, selon son tagname
    let tagname = el.prop('tagName');
    if (['SELECT', 'INPUT'].includes(tagname)) {
        return el.val()
    } else if (tagname == 'TEXTAREA') {
        return el.val().trim();
    }
    return 'unknown_tagname ' + tagname;
}