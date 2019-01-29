
//jQuery time
var current_fs, next_fs, previous_fs; //fieldsets
let current_fs_index = 1;
var left, opacity, scale; //fieldset properties which we will animate
var animating; //flag to prevent quick multi-click glitches


jQuery(".next").click(function(){
	if(animating) return false;
	animating = true;
	
	current_fs = jQuery(this).parent(); // current <fieldset>
	next_fs = jQuery(this).parent().next();
	
	// we check validity of all fields in current fieldset
	let valid = validate_elements(current_fs.find('.ccnlib_post:visible'));
	if (!valid) {
		animating = false;
		return toastr.error('Veuillez renseigner tous les champs')
	}

	//activate next step on progressbar using the index of next_fs
	current_fs_index++;
	jQuery("#ccnlib_progressbar > li").eq(current_fs_index).addClass("active");
	
	//show the next fieldset
	next_fs.show(); 
	//hide the current fieldset with style
	current_fs.animate({opacity: 0}, {
		step: function(now, mx) {
			//as the opacity of current_fs reduces to 0 - stored in "now"
			//1. scale current_fs down to 80%
			scale = 1 - (1 - now) * 0.2;
			//2. bring next_fs from the right(50%)
			left = (now * 50)+"%";
			//3. increase opacity of next_fs to 1 as it moves in
			opacity = 1 - now;
			current_fs.css({
				'transform': 'scale('+scale+')',
				'position': 'absolute'
			});
			next_fs.css({'left': left, 'opacity': opacity});
		}, 
		duration: 800, 
		complete: function(){
			current_fs.hide();
			animating = false;
		}, 
		//this comes from the custom easing plugin
		easing: 'easeInOutBack'
	});
});

jQuery(".previous").click(function(){
	if(animating) return false;
	animating = true;
	
	current_fs = jQuery(this).parent();
    previous_fs = jQuery(this).parent().prev();
	
	//de-activate current step on progressbar
	jQuery("#ccnlib_progressbar li").eq(current_fs_index).removeClass("active");
	current_fs_index--;
	
	//show the previous fieldset
	previous_fs.show(); 
	//hide the current fieldset with style
	current_fs.animate({opacity: 0}, {
		step: function(now, mx) {
			//as the opacity of current_fs reduces to 0 - stored in "now"
			//1. scale previous_fs from 80% to 100%
			scale = 0.8 + (1 - now) * 0.2;
			//2. take current_fs to the right(50%) - from 0%
			left = ((1-now) * 50)+"%";
			//3. increase opacity of previous_fs to 1 as it moves in
			opacity = 1 - now;
			current_fs.css({'left': left});
			previous_fs.css({'transform': 'scale('+scale+')', 'opacity': opacity});
		}, 
		duration: 800, 
		complete: function(){
			current_fs.hide();
			animating = false;
		}, 
		//this comes from the custom easing plugin
		easing: 'easeInOutBack'
	});
});

jQuery(".submit").click(function(){
	return false;
})