jQuery(document).ready(function($) {

    let group_id = "{{group_id}}";

    // we disable all form controls that are in a hidden template group (for dynamic elements)
    $('.ccnlib_hidden_template').find('.ccnlib_post').attr('disabled', 'disabled');

    // ===================================================
    // CLICK event for deleting a dynamic group of fields
    // ===================================================
    let button_delete = function(e) {
        e.preventDefault(); // très important, sinon Wordpress recharge la page
        $(this).parent().fadeOut(300, function(){ $(this).remove();});
    };
    $('.ccnlib_delete_repeat_element').click(button_delete);

    // ===================================================
    // CLICK event for add a new dynamic group of fields
    // ===================================================
    $('#'+group_id+'_button_add_element').click(function(e) {
        e.preventDefault(); // très important, sinon Wordpress recharge la page
        let template_html = $('#'+group_id+'_hidden_group_model > div').clone(false); // true est mieux, mais fait bugger air-datepicker
        let p = $(this).parent();
        let n = p.children().length -2;
        template_html.find('[id]').each(function() {
            
            let curr_id = $(this).attr('id')
            
            $(this).removeAttr('disabled');

            if (/_hidden$/g.test(curr_id)) {
                let myid = curr_id.replace('_hidden', '_'+n);
                $(this).attr('id', myid)
            }

        });

        // on initialise les nouveaux éléments spéciaux (comme le air datepicker) si besoin
        template_html.find('.datepicker-here').datepicker();
        template_html.find('.ccnlib_delete_repeat_element').click(button_delete);

        $(this).before(template_html);

        
    });

})