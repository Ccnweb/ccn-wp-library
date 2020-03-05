jQuery(document).ready(function($) {

    // on initialise les tooltips bootstrap
    // source : https://getbootstrap.com/docs/4.1/components/tooltips/
    if ($('[data-toggle="tooltip"]').length) $('[data-toggle="tooltip"]').tooltip();

    // check that all html ids are unique ! (to identify when a form is inserted multiple times in the same page)
    if (!check_html_unique_ids()) console.error('We found some HTML ids that are not unique, ccn forms cannot work !'); // TODO do a return here

    // on itinialise les datepickers dans les formulaires
    if ($('.datepicker-here').length) $('.datepicker-here').datepicker();

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
                toastr.options.closeDuration = 10000;
                toastr.success('Les données ont bien été envoyées !');
                // on remet les champs à zéro
                form_elements.each(function() {
                    jQuery(this).val('')
                });
            }

            // remise à zéro interne
            submit_btn.html(submit_btn_html);
            goToStep(form_obj, 0, true); // this returns to the first step. Function defined in forms/steps/load_front_steps.js
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
    'REGISTRATION_EXIST': 'Une pré-inscription avec ces informations existe déjà',
    'TOO_YOUNG': 'La pré-inscription est possibles uniquement aux personnes majeures',
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
        console.log('source sel', source_selector);

        // we define the current rule function
        let curr_check_rule = function() {
            if (check_rule_condition(rule)) jQuery(rule.target_selector).show({duration: 300, easing: 'swing'});
            else jQuery(rule.target_selector).hide({duration: 300, easing: 'swing'});
        }

        // we execute the rule now
        curr_check_rule();

        // we register the rule in a change event
        jQuery(source_selector).change(curr_check_rule); // curr_check_rule
    }
}

function check_rule_condition(rule) {
    /**
     * Renvoie true ou false selon si la rule est ou non vérifiée
     */
    //console.log('checking rule', rule);
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

function get_select_options(el) {
    let options = [];
    let html_options = el[0].options;
    for(i = 0; i < html_options.length; i++) {
        options.push(html_options[i].value)
    }
    return options
}

function check_html_unique_ids() {
    /**
     * Checks that all HTML elements have a unique id
     */

    let ids = [];
    jQuery('[id]').each(function() {
        ids.push(jQuery(this).attr('id'));
    });
    let mem = [];
    for (let i of ids) {
        if (mem.includes(i)) {
            console.error('found html duplicate ids : '+i);
            return false;
        }
        mem.push(i);
    }
    return true;
}


// ================================================
//          FUNCTIONS FOR TESTING PURPOSE
// ================================================

function populate_forms(form_id = null) {
    let form = form_id;
    if (!form_id) form = jQuery('form.form-container');
    if (typeof form_id == 'string') form = jQuery('#'+form_id);
    if (form.length < 1) return console.log('Impossible de trouver le formulaire à populer');

    // we get all elements to be populated
    let form_elements = form.find('.ccnlib_post');

    // we initialize variables
    let alphabet = 'a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z'.split(',');
    let prenoms = ['jonas', 'bruce', 'galadriel', 'aragorn'];
    let noms = ['baugé', 'parker', 'lepetit', 'bergstein', 'skywalker'];
    let prenoms_noms = ['Peter Parker', 'Bruce Banner', 'Tony Stark', 'Bruce Wayne', 'Clark Kent', 'Luke Skywalker'];
    let domaines = ['gmail.com', 'chemin-neuf.org', 'yahoo.fr', 'wanadoo.fr', 'mail.com'];
    let adjectifs = ['great', ''];
    let streets = ['Chemin de traverse', 'Montée du Chemin Neuf', 'Pentagon street'];
    let cities = ['Mountain View', 'Palo Alto', 'Beijing'];
    let gen_number = (n = 5) => Array.from(Array(n), () => Math.floor(Math.random()*10)).join('');
    let gen_string = (n = 5) => pick_alea(alphabet, n).join('');
    let pick_alea = (arr, n = 1) => (n == 1) ? arr[Math.round(Math.random()*(arr.length-1))] : Array.from(Array(n), () => arr[Math.round(Math.random()*(arr.length-1))]);
    let firstLetterUp = (s) => s.charAt(0).toUpperCase() + s.slice(1);
    let gen_prenom_nom = () => (Math.random() > 0.5) ? [firstLetterUp(pick_alea(prenoms)), firstLetterUp(pick_alea(noms))] : pick_alea(prenoms_noms).split(' ') ;
    let gen_email = () => gen_prenom_nom().join('.').toLowerCase() + '@' + pick_alea(domaines);
    let gen_birthdate = () => '0' + Math.floor(Math.random()*10) + '-0' + Math.floor(Math.random()*10) + '-19' + gen_number(2);
    let gen_street = () => gen_number(pick_alea([2,2,3])) + ', ' + pick_alea(streets);
    let gen_city = () => pick_alea(cities);
    let gen_postalcode = () => Math.round(Math.random()*100000).toString();

    form_elements.each(function() {
        let el = jQuery(this);
        let tag = el.prop('tagName');
        let name = el.attr('name');
        let prenom_nom = gen_prenom_nom();

        if (tag == 'INPUT' && !el.val()) {
            let type = el.attr('type');
            if (type == 'email') el.val(gen_email());
            else if (type == 'text' && /(_firstname|prenom)/gi.test(name)) {el.val(prenom_nom[0]); prenom_nom = gen_prenom_nom()}
            else if (type == 'text' && /(_name|nom)/gi.test(name)) {el.val(prenom_nom[1]); prenom_nom = gen_prenom_nom()}
            else if (type == 'text' && el.hasClass('datepicker-here')) el.val(gen_birthdate());
            else if (type == 'text' && /(street|rue)/gi.test(name)) el.val(gen_street());
            else if (type == 'text' && /(_postalcode)/gi.test(name)) el.val(gen_postalcode());
            else if (type == 'text' && /(_city|ville)/gi.test(name)) el.val(gen_city());
            else if (type == 'text') el.val(gen_string(7));
            else if (type == 'tel') el.val(gen_number(10));
        } else if (tag == 'SELECT') {
            let options = get_select_options(el);
            el.val(pick_alea(options));
        } else if (el.hasClass('form-radio-container')) {
            let options = el.find('input[type="radio"]');
            let n = Math.floor(Math.random()*options.length);
            options.eq(n).prop("checked", true);
        }
    })
}