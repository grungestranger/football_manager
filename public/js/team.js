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
		containment: 'parent',
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
	$('#players > tbody > tr').draggable({
		containment: 'parent',
        helper: 'clone',
        axis: 'y',
        opacity: 0.6,
		stop: function(e, ui) {
			var id = [];
			id[0] = ui.helper.data('id');
			ui.helper.remove();
			$('#players > tbody > tr').each(function(){
				if (ui.offset.top - $(this).offset().top < $(this).height() / 2) {
					id[1] = $(this).data('id');
					if (id[0] != id[1]) {
						var row = [], html = [];
						row[0] = $('#players > tbody > tr[data-id="' + id[0] + '"]');
						row[1] = $(this);
						for (var i = 0; i <= 1; i++) {
							html[i] = row[i].html();
						}
						for (var i = 0; i <= 1; i++) {
							var j = Math.abs(i - 1);
							row[i].html(html[j]).attr('data-id', id[j]).data('id', id[j]);
						}
					}
					return false;
				}
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