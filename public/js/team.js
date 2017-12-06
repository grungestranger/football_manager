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
			var id1 = ui.helper.data('id');
			ui.helper.remove();
			var row1 = $('#players > tbody > tr[data-id="' + id1 + '"]');
			var html1 = row1.html();
			$('#players > tbody > tr').each(function(){
				if (ui.offset.top - $(this).offset().top < $(this).height() / 2) {
					var id2 = $(this).data('id');
					var row2 = $(this);
					var html2 = row2.html();
					row1.html(html2)/*.attr('data-id', id2)*/;
					row2.html(html1)/*.attr('data-id', id1)*/;
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