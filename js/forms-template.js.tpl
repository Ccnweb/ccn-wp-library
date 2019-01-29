jQuery(document).ready(function($) {

    let custom_data_attributes = {{custom_data_attributes}};
    let fields_array = {{fields_array}}; // contient la liste des fields id à envoyer par POST e.g. ['wpsubs_key_name', ...]

    // ===========================================
    //          VALIDATION ON FOCUSOUT
    // ===========================================
    // on charge la validation en temps réel pour tous les éléments
    for (field_id of fields_array) {
        // pour chaque champs, on affiche le message d'erreur au focusout si le champs est invalide
        let el = jQuery('#' + field_id + "_field");
        if (el.length) {
            el.focusout(function() {
                if(el[0].validity.valid) {
                    el.removeClass('invalid');
                    el.next().removeClass('show');
                } else {
                    el.addClass('invalid');
                    el.next().addClass('show');
                }
            })
        }
    }

    // ===========================================
    //          SUBMIT FORM CLICK
    // ===========================================
    let post_error_messages = {
        'INVALID_DOMAIN': 'Le domaine de cette adresse email est banni',
        'INVALID_POSTALCODE': 'Le code postal est invalide',
        'INVALID_EMAIL': 'L\'adresse email est incorrecte',
        'POST_CREATION_FAILED': 'La requête a échoué, désolé :(',
        'DUPLICATE_POST_KEY': 'Une ressource avec ces informations existe déjà, impossible d\'en créer une autre',
    }

    // add click event to the submit button
    jQuery('#{{action_name}}_submit').click(function() {

        // on valide le formulaire
        if(!validateForm(fields_array)) {
            toastr.error('Certains champs du formulaire sont invalides !')
            return
        }

        // on lance le spinner d'attente de la requête
        let submit_btn_html = jQuery(this).html();
        jQuery(this).html('<i class="fas fa-spinner fa-spin"></i>');
        

        let data = {'action': '{{action_name}}'};

        for (field_id of fields_array) {
            data[field_id] = getVal(`${field_id}_field`);
        }
        console.log("post data : ", data)

        $.post(
            '{{ajax_url}}',
            data, 
            function(raw_json) {
                let json_response;
                if (raw_json === null || raw_json == '') {console.log('Empty response from {{ajax_url}}'); return false}
                if (raw_json === 'null') {console.log('Server responded with string "null" lol'); return false}
                try {
                    json_response = JSON.parse(raw_json)
                } catch (e) {
                    console.log("Unable to parse JSON response, invalid JSON", e, "raw_json=", raw_json);
                    return false;
                }
                console.log("response jquery post = ", json_response);
                
                jQuery('#{{action_name}}_submit').html(submit_btn_html);
                if (!json_response['success'] && post_error_messages[json_response['errno']]) {
                    toastr.error(post_error_messages[json_response['errno']])
                } else if (!json_response['success'] && json_response['descr']) {
                    toastr.error(json_response['descr'])
                } else if (!json_response['success']) {
                    toastr.error('Le requête a échoué, sorry :(')
                } else if (json_response['success']) {
                    toastr.success('C\'est bon tout s\'est bien passé ! Merci Seigneur !');

                }
                
                // on remet les champs à zéro
                for (field_id of fields_array) resetVal(`${field_id}_field`);

                return false;
            }
        ).fail(function() {
            alert( "error" );
          });
    });

    // ===========================================
    //          RUN SPECIFIC LOGIC
    // ===========================================

    {{logic_rules}}
    /* let logic = TODO_ajouter_accolades_icilogic_rules}}

    // on enregistre les événements de logic :
    let global_actions = []; // actions à effectuer pour chaque changement sur n'importe quel champs du formulaire
    for (let rule of logic) {
        if (rule.trigger) {
            if ($(rule.trigger.selector).length && rule.trigger.event) {
                $(rule.trigger.selector).on()
            } else {
                console.log('unknown trigger structure ', rule.trigger)
            }
        } else { // action globale
            if (rule.action && typeof rule.action == 'function') global_actions.push(rule.action);
        }
    }
    // on enregistre les actions globales
    $("form :input").change(function() {for (let action of glob_actions) action()}); */
    

    // ===========================================
    //          HELPER FUNCTIONS
    // ===========================================
    function validateForm(fields_array) {
        /**
         * Validates the values of each field in the HTML form
         */

        // on parcourt les fields_id et on valide chaque élément HTML qui correspond à un field_id
        for (field_id of fields_array) {
            let el = jQuery('#' + field_id + "_field");
            if (el.length) {
                if(!el[0].validity.valid) return false;
            }
        }
        return true;
    }

    function getVal(el_id) {
        /**
         * Récupère la valeur à envoyer par HTTP POST de l'élément HTML avec l'id el_id
         */
        let el = jQuery('#' + el_id);
        if (!el) el = jQuery("input[name='"+el_id+"']:checked"); // dans le cas d'une checkbox

        // si l'élément n'existe pas, c'est qu'il doit peut-être être calculé
        if (typeof custom_data_attributes[el_id] == 'function') {console.log('r', custom_data_attributes[el_id]()); return custom_data_attributes[el_id]();}
        if (el.length < 1) return 'unknown element ' + el_id;

        // si l'élément existe, on récupère sa valeur, selon son tagname
        let tagname = el.prop('tagName');
        if (tagname == 'INPUT') {
            return el.val()
        } else if (tagname == 'TEXTAREA') {
            return el.val().trim();
        }
        return 'unknown_tagname ' + tagname;
    }

    function resetVal(el_id) {
        /**
         * Remet à zéro la valeur du champs
         */
        jQuery('#' + el_id).val('');
    }

});