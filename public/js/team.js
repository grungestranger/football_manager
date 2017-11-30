$(document).ready(function(){

	var fieldCoefficient = 0.5;

	$('.player').each(function(){
		$(this).css({
			left: ($(this).data('pos_x') * fieldCoefficient) + 'px',
			bottom: ($(this).data('pos_y') * fieldCoefficient) + 'px'
		});
	});

	$('#tactics').change(function(){
		$('#save_tactic').show();
	});

	$('#save_as_tactic').click(function(){
		$('#save_as_tactic_block').show();
	});

});