$(document).ready(function(){

	var fieldHeight = 600, fieldCoef = 0.5, rolesAreas;

	function sortRows() {

	}

	$('.player').each(function(){
		var pos = JSON.parse($('#settingsForm [name="players[' + $(this).data('id') + '][position]"]').val());
		$(this).css({
			left: (pos.x * fieldCoef) + 'px',
			bottom: (pos.y * fieldCoef) + 'px'
		});
	});

	// show button save
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
			$('#settingsForm [name="players[' + $(this).data('id') + '][position]"]').val(JSON.stringify({
				x : Math.round(ui.position.left / fieldCoef),
				y : fieldHeight - Math.round(ui.position.top / fieldCoef)
			}));
			if (!rolesAreas) {
				$.ajax({
              		url: '/team/get-roles-areas',
              		success: function(data) {
              			rolesAreas = data;
              			sortRows();
              		}
        		});
			} else {
				sortRows();
			}

			$('#save_settings').show();
		}
	});

	//
	$('#players td').each(function(){
		$(this).width($(this).width());
	});
	var fields = ['position', 'reserveIndex'];
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
						var row = [], player = [], rowHtml = [], playerHtml = [], val = [];
						row[0] = $('#players > tbody > tr[data-id="' + id[0] + '"]');
						row[1] = $(this);
						for (var i = 0; i <= 1; i++) {
							player[i] = $('.player[data-id="' + id[i] + '"]');
							if (player[i].length) {
								playerHtml[i] = player[i].html();
							} else {
								playerHtml[i] = row[i].find('td:eq(0)').html();
							}
							rowHtml[i] = row[i].html();
							val[i] = {};
							forEach(fields, function(k, v){
								val[i][v] = $('#settingsForm [name="players[' + id[i] + '][' + v + ']"]').val();
							});
						}
						for (var i = 0; i <= 1; i++) {
							var j = Math.abs(i - 1);
							row[i].html(rowHtml[j]).attr('data-id', id[j]).data('id', id[j]);
							player[i].html(playerHtml[j]).attr('data-id', id[j]).data('id', id[j]);
							forEach(fields, function(k, v){
								$('#settingsForm [name="players[' + id[i] + '][' + v + ']"]').val(val[j][v]);
							});
						}

						$('#save_settings').show();
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