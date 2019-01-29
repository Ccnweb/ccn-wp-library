// fonctions utiles pour la custom logic des metabox

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
            if (check_rule_condition(rule)) {
                let target = jQuery(rule.target_selector)
                target.show();
                // we enable all fields tha are now shown
                target.find('.ccnlib_post').removeAttr('disabled');
            } else {
                let target = jQuery(rule.target_selector)
                target.hide();
                // we disable all fields that are now hidden
                target.find('.ccnlib_post').attr('disabled', 'disabled');
            }
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