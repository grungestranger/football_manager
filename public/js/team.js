$(document).ready(function(){

	var fieldCoef = 0.5;

	$('.player').each(function(){
		var pos = JSON.parse($('#settingsForm [name="players[' + $(this).data('id') + '][position]"]').val());
		$(this).css({
			left: (pos.x * fieldCoef) + 'px',
			bottom: (pos.y * fieldCoef) + 'px'
		});
	});

	$('#settingsForm').find('input, select').change(function(){
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
			$('#settingsForm [name="players[' + $(this).data('id') + '][position]"]').val(JSON.stringify({
				x : Math.round(ui.position.left / fieldCoef),
				y : Math.round(ui.position.top / fieldCoef)
			}));
		}
	});

	//
	$('#players td').each(function(){
		$(this).width($(this).width());
	});
	//
	$('#players > tbody').sortable({
        items: 'tr',
        containment: 'parent',
        axis: 'y',
        cursor: 'move',
        opacity: 0.6,
        update: function() {
            //sendOrderToServer();
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