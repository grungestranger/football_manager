$(document).ready(function(){

	var fieldHeight = 600, fieldCoef = 0.5, margin = 10, rolesAreas = [];

	function selectSettings(id) {
		$.ajax({				
			url: '/team',
			data: 'settings_id=' + id,
			success: function(data) {
				$.each(data.settings.settings, function(k, v){
					// only for selects yet
					$('#settingsForm [name="settings[' + k + ']"] option[value="' + v + '"]').prop('selected', true);
				});
				$.each(data.players, function(k, v){
					var player = $('.player[data-id="' + v.id + '"]');
					if (v.settings.position) {
						player.show();
					} else {
						player.hide();
					}
					replaceRow(k, v.id);
					$.each(v.settings, function(k1, v1){
						if (v1 === null) {
							v1 = 'NULL';
						} else if (typeof(v1) == 'object') {
							v1 = JSON.stringify(v1);
						}
						$('#settingsForm [name="players[' + v.id + '][' + k1 + ']"]').val(v1);
					});
				});
				playersPositions();
				$('#save_settings').hide();
			}
		});
	}

	function replaceRow(index, id) {
		var row = $('#players > tbody > tr[data-id="' + id + '"]');
		if (parseInt(index)) {
			row.insertAfter('#players > tbody > tr:eq(' + (index - 1) + ')');
		} else {
			row.prependTo('#players > tbody');
		}
	}

	function setPosition(player) {
		var pos = JSON.parse($('#settingsForm [name="players[' + player.data('id') + '][position]"]').val());
		player.css({
			left: (pos.x * fieldCoef) + 'px',
			bottom: (pos.y * fieldCoef) + 'px',
			top: 'auto'
		});
	}

	function playersPositions() {
		$('.player:visible').each(function(){
			setPosition($(this));
		});
	}

	// rolesAreas setup
	$('.rolesArea').each(function(){
		var coords = $(this).data('coords');
		rolesAreas[rolesAreas.length] = coords;
		$(this).css({
			left: ((coords.x[0] * fieldCoef) + margin) + 'px',
			bottom: ((coords.y[0] * fieldCoef) + margin) + 'px',
			width: ((coords.x[1] - coords.x[0]) * fieldCoef) + 'px',
			height: ((coords.y[1] - coords.y[0]) * fieldCoef) + 'px',
			lineHeight: ((coords.y[1] - coords.y[0]) * fieldCoef) + 'px'
		});
	});

	playersPositions();

	// Select Settings
	$('#settingsForm [name="settings_id"]').change(function(){
		selectSettings($(this).val());
	});

	// show button save
	// only for selects yet
	$('#settingsForm select:not([name="settings_id"])').change(function(){
		$('#save_settings').show();
		if (window.matchFunction) {
			matchFunction();
		}
	});

	// open saveAs popup
	$('#save_as_settings_open').click(function(){
		$('#save_as_settings_block').show();
	});

	// Moving players
	$('.player').draggable({
		containment: 'parent',
		stop: function(e, ui) {
			$('#settingsForm [name="players[' + $(this).data('id') + '][position]"]').val(JSON.stringify({
				x : Math.round(ui.position.left / fieldCoef),
				y : fieldHeight - Math.round(ui.position.top / fieldCoef)
			}));
			var temp = {};
			$('.player:visible').each(function(){
				var id = $(this).data('id');
				var pos = JSON.parse($('#settingsForm [name="players[' + id + '][position]"]').val());
				$.each(rolesAreas, function(k, v){
					if (
                        pos.x >= v.x[0] && pos.x <= v.x[1]
                        && pos.y >= v.y[0] && pos.y <= v.y[1]
					) {
						if (!isset(temp[k])) {
							temp[k] = [];
						}
						temp[k][temp[k].length] = {id: id, pos: pos};
						return false;
					}
				});
			});
			var keys = [];
			// if you trust the search order of object properties,
			// you can not use an array - "keys"
			$.each(temp, function(k, v){
				keys[keys.length] = parseInt(k);
				if (v.length > 1) {
					v.sort(function(a, b){
	                    if (a.pos.y < b.pos.y) {
	                        return 1;
	                    } else if (a.pos.y > b.pos.y) {
	                        return -1;
	                    } else {
	                        if (a.pos.x < b.pos.x) {
	                            return -1;
	                        } else if (a.pos.x > b.pos.x) {
	                            return 1;
	                        } else {
	                            return 0;
	                        }
	                    }
					});
				}
			});
			keys.sort(function(a, b){
				return a - b;
			});
			var result = [];
			$.each(keys, function(k, v){
				$.each(temp[v], function(k1, v1){
					result[result.length] = v1.id;
				});
			});
			$.each(result, function(k, v){
				replaceRow(k, v);
			});
			$('#save_settings').show();
			if (window.matchFunction) {
				matchFunction();
			}
		}
	});

	// save td width
	$('#players td').each(function(){
		$(this).width($(this).width());
	});
	// fields for replace changes
	var fields = ['position', 'reserveIndex'];
	// replace rows
	$('#players > tbody > tr').draggable({
		containment: 'parent',
        helper: 'clone',
        axis: 'y',
        opacity: 0.6,
		stop: function(e, ui) {
			var items = [];
			items[0] = {id: ui.helper.data('id')};
			ui.helper.remove();
			$('#players > tbody > tr').each(function(){
				if (ui.offset.top - $(this).offset().top < $(this).height() / 2) {
					items[1] = {id: $(this).data('id')};
					if (items[0].id != items[1].id) {
						items[0].row = $('#players > tbody > tr[data-id="' + items[0].id + '"]');
						items[1].row = $(this);
						for (var i = 0; i <= 1; i++) {
							items[i].index = $('#players > tbody > tr').index(items[i].row);
							items[i].player = $('.player[data-id="' + items[i].id + '"]');
							items[i].val = {};
							$.each(fields, function(k, v){
								items[i].val[v] = $('#settingsForm [name="players[' + items[i].id + '][' + v + ']"]').val();
							});
						}
						for (var i = 0; i <= 1; i++) {
							var j = Math.abs(i - 1);
							$.each(fields, function(k, v){
								$('#settingsForm [name="players[' + items[i].id + '][' + v + ']"]').val(items[j].val[v]);
							});
							if (items[0].index > items[1].index && i == 1) {
								var index = items[j].index + 1;
							} else {
								var index = items[j].index;
							}
							replaceRow(index, items[i].id);
							if (items[j].val.position != 'NULL') {
								items[i].player.show();
								setPosition(items[i].player);
							} else {
								items[i].player.hide();
							}
						}
						$('#save_settings').show();
						if (window.matchFunction) {
							matchFunction();
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
			data: $('#settingsForm').serialize(),
			success: function(data) {
				if (data.success) {
					$('#save_settings').hide();
					alert(data.message);
				} else {
					alert(data.error);
				}
			}
		});
		return false;
	});

	// saveAs settings
	$('#save_as_settings').click(function(){
		$.ajax({  
			type: 'POST', 				
			url: '/team/save-as',
			data: $('#settingsForm').serialize(),
			success: function(data) {
				$('#save_as_settings_block').hide();
				if (data.success) {
					$('#settingsForm [name="settings_id"]')
						.append('<option value="' + data.settings.id + '">' + htmlspecialchars(data.settings.name) + '</option>');
					$('#settingsForm [name="settings_id"]').val(data.settings.id);
					$('#save_as_settings_block [name="settings_name"]').val('');
					$('#remove_settings').show();
					$('#save_settings').hide();
					alert(data.message);
				} else {
					alert(data.error);
				}
			}
		});
		return false;
	});

	// remove settings
	$('#remove_settings').click(function(){
		var id = $('#settingsForm [name="settings_id"]').val();
		$.ajax({  
			type: 'POST', 				
			url: '/team/remove',
			data: 'settings_id=' + id,
			success: function(data) {
				if (data.success) {
					$('#settingsForm [name="settings_id"] option[value="' + id + '"]').remove();
					selectSettings($('#settingsForm [name="settings_id"]').val());
					if ($('#settingsForm [name="settings_id"] option').length < 2) {
						$('#remove_settings').hide();
					}
					alert(data.message);
				} else {
					alert(data.error);
				}
			}
		});
		return false;
	});

	// rolesAreas highlight
	var fieldOffset = $('#field').offset();
	$('#field').mousemove(function(e) {
		$('.rolesArea').removeClass('hover');
		$('.rolesArea').each(function(){
			var coords = $(this).data('coords');
			if (
				e.pageX - fieldOffset.left - margin >= coords.x[0] * fieldCoef
				&& e.pageX - fieldOffset.left - margin <= coords.x[1] * fieldCoef
				&& e.pageY - fieldOffset.top - margin <= (fieldHeight - coords.y[0]) * fieldCoef
				&& e.pageY - fieldOffset.top - margin >= (fieldHeight - coords.y[1]) * fieldCoef
			) {
				$(this).addClass('hover');
				return false;
			}
		});
	});
	$('#field').mouseleave(function() {
		$('.rolesArea').removeClass('hover');
	});

});