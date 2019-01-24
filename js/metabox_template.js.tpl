jQuery(document).ready(function($) {

    // ================================================
    // LOGIC FOR DISPLAYING THIS METABOX
    // logique du champs 'condition' dans les options des metaboxes
    // ================================================

    {{condition_logic}} // calculé par la fonction php parse_js_condition

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

})