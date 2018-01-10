$(document).ready(function(){
	// popups
	$('.popup').click(function(){
		$(this).hide();
	});

	$('.popup_content').click(function(event){
		event.stopPropagation();
	});
	// show errors
	$('#errors').show();
});
