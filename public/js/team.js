$(document).ready(function(){

	var fieldCoef = 0.5;

	$('.player').each(function(){
		$(this).css({
			left: ($(this).data('pos_x') * fieldCoef) + 'px',
			bottom: ($(this).data('pos_y') * fieldCoef) + 'px'
		});
	});

	$('#tactic').change(function(){
		$('#save_settings').show();
	});

	$('#save_as_settings').click(function(){
		$('#save_as_settings_block').show();
	});

	//
	$('.player').draggable({
		containment: "parent",
		stop: function(e, ui) {
			$('#save_settings').show();
			$(this).data({
				pos_x: Math.round(ui.position.left / fieldCoef),
				pos_y: Math.round(ui.position.top / fieldCoef)
			});
		}
	});

	// save settings
	$('#save_settings').click(function(){
		$.ajax({  
			type: 'POST', 				
			url: '/team/save',
			data: {
				data: JSON.stringify({
					settings: {
						id: $('#settings').val(),
						tactic: $('#tactic').val()
					}
				})
			},
			success: function(data) {
				if (data.success) {
					alert('Успешно.');
				} else {
					alert('error');
				}
			}
		});
	});

});