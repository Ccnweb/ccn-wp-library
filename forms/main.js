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

    // prepare submit form buttons
    $('.ccnlib_submit_btn').click(function() {
        ajax_form_submit($(this))
    });

});

// ===============================================================
//              SEND FORM DATA WITH AJAX
// ===============================================================

let FORM_SUBMITTING = false;

function ajax_form_submit(submit_btn) {
    /**
     * Submits form data to wordpress endpoint
     * 
     * @param object submit_btn     the jQuery object representing the HTML submit button
     * 
     * ## SUMMARY
     * 1. get all necessary HTML elements
     * 2. validate the form elements
     * 3. prepare the data to post
     * 4. send the data via POST ajax
     * 5. parse the ajax response
     */

    let simulation = false; // in production this should be false
    
    if (FORM_SUBMITTING) {
        toastr.error("Un formulaire est déjà en cours d'envoi");
        return;
    }
    FORM_SUBMITTING = true;

    // == 1. == we get all the main HTML elements we need
    let form_obj = submit_btn.closest('form'); // the parent form object
    let form_elements = form_obj.find('.ccnlib_post'); // elements to be sent
    let action_name = submit_btn.attr('id').replace(/_submit$/gi,''); // le nom de l'action à poster

    // == 2. == we validate the form elements of the last fieldset
    if (!validate_elements(form_obj.find('.ccnlib_post:visible'))) {
        FORM_SUBMITTING = false;
        return toastr.error('Le formulaire contient des champs invalides, veuillez les corriger');
    }

    // we show the spinner on the submit button
    let submit_btn_html = submit_btn.html();
    submit_btn.html('<i class="fas fa-spinner fa-spin"></i>');

    // == 3. == we prepare the data to post
    let data = {action: action_name}
    form_elements.each(function() {
        let key = jQuery(this).attr('name');
        let val = getVal(jQuery(this));

        // special case of radio buttons
        if (jQuery(this).hasClass('form-radio-container')) {
            key = jQuery(this).find('input:checked').attr('name');
        }

        // cas special des champs dynamiques
        if (key) {
            if (key.substr(-2) == '[]') {
                if (data[key]) data[key].push(val);
                else data[key] = [val];
            } else {
                data[key] = val;
            }
        }
    })
    console.log('data to be posted : ', data);

    // simulation mode 
    if (simulation) {
        toastr.error("Impossible d'envoyer les données, mode simulation");
        console.log("No data sending through ajax : simulation mode ON");
        submit_btn.html(submit_btn_html);
        FORM_SUBMITTING = false;

    // == 4. == we send the data
    } else jQuery.post(
        ajax_url,
        data, 
        function(raw_json) {
            // == 5. == parse the ajax response
            let json_response;
            if (raw_json === null || raw_json == '') {console.log('Empty response from ', ajax_url); return false}
            if (raw_json === 'null') {console.log('Server responded with string "null" lol'); return false}
            try {
                json_response = JSON.parse(raw_json)
            } catch (e) {
                console.log("Unable to parse JSON response, invalid JSON", e, "raw_json=", raw_json);
                return false;
            }
            console.log("response jquery post = ", json_response);
            
            if (!json_response['success'] && post_error_messages[json_response['errno']]) {
                toastr.error(post_error_messages[json_response['errno']])
            } else if (!json_response['success'] && json_response['descr']) {
                toastr.error(json_response['descr'])
            } else if (!json_response['success']) {
                toastr.error('Le requête a échoué')
            } else if (json_response['success']) {
                toastr.success('Les données ont bien été envoyées !');
                // on remet les champs à zéro
                form_elements.each(function() {
                    jQuery(this).val('')
                });
            }

            // remise à zéro interne
            submit_btn.html(submit_btn_html);
            FORM_SUBMITTING = false;
            return false;
        }
    ).fail(function(e) {
        toastr.error('Désolé, erreur du serveur :(');
        submit_btn.html(submit_btn_html);
        FORM_SUBMITTING = false;
        console.log( "ajax post error", e );
    });
}

let post_error_messages = {
    'INVALID_DOMAIN': 'Le domaine de cette adresse email est banni',
    'INVALID_POSTALCODE': 'Le code postal est invalide',
    'INVALID_EMAIL': 'L\'adresse email est incorrecte',
    'POST_CREATION_FAILED': 'La requête a échoué, désolé :(',
    'DUPLICATE_POST_KEY': 'Une ressource avec ces informations existe déjà, impossible d\'en créer une autre',
}

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
        console.log('invalid element id='+el.attr('id')+', name='+el.attr('name'));
        return false;
    } else if (el.hasClass('form-radio-container')) {
        let res = validate_radio(el);
        if (!res) console.log('invalid element radio id='+el.attr('id')+', name='+el.attr('name'));
        return res;
    } else {
        console.log('warning in validate_element() : strange element (validated by default) : ', el)
        return true
    }
}

function validate_radio(container_el) {
    let ok = false;
    let checked_id = '';
    container_el.find('input').each(function() {
        if (jQuery(this).is(':checked')) {
            ok = true;
            checked_id = jQuery(this).attr('id');
        }
    });
    if (!ok) return ok;

    // check if this radio option has an additional input text that is required
    let preciser = container_el.find('input[name="'+checked_id+'_preciser"].preciser_required');
    if (preciser.length && (preciser.val() == '' || !validate_element(preciser))) ok = false;
    
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

    let el = (typeof el_id === 'string') ? jQuery('#' + el_id) : el_id;
    el_id = el.attr('id');

    // cas des INPUT RADIO
    if (el.prop('tagName') == 'INPUT' && el.attr('type') == 'radio') return jQuery('input[name="'+el.attr('name')+'"]:checked').val();
    if (el.hasClass('form-radio-container')) return el.find('input:checked').val();

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