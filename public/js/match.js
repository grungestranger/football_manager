socket.on('message', function (data) {
	data = JSON.parse(data);
	switch (data.action) {
		case 'matchActions':
			doActions(data.actions);
			console.log('yeah');
			break
	}
});

function doActions(actions) {
				$.each(actions.motions, function(k, v){
				$.each(v[1], function(k1, v1){
					$('#matchField > span[data-id="' + k1 + '"]').animate({
								left: v1[0]+'px',
								bottom: v1[1]+'px'
							}, {duration: v[0], easing: 'linear'});
				});
			});
}

$(document).ready(function(){

	var settingsSelect = $('#settingsForm [name="settings_id"]');

	window.matchFunction = function(){
		if (settingsSelect.val() != 'NULL') {
			settingsSelect.prepend('<option value="NULL">Load</option>').val('NULL');
		}
		$('#confirm_settings').show();
	}

	// load settings
	settingsSelect.change(function(){
		$('#confirm_settings').show();
		$(this).children('[value="NULL"]').remove();
	});

	// confirm settings
	$('#confirm_settings').click(function(){
		$.ajax({  
			type: 'POST', 				
			url: '/match/save',
			data: $('#settingsForm').serialize(),
			success: function(data) {
				if (data.success) {
					$('#confirm_settings').hide();
					alert(data.message);
				} else {
					alert(data.error);
				}
			}
		});
		return false;
	});

	// Loader timer
	if ($('#matchLoader').length) {
		var matchLoaderTimerId = setInterval(function(){
			var sec = parseInt($('#matchLoader > span').text());
			if (sec > 0) {
				$('#matchLoader > span').html(sec - 1);
			} else {
				clearInterval(matchLoaderTimerId);
				$('#matchLoader').remove();
			}
		}, 1000);
	}

	if (window.actions) {
		doActions(window.actions);
	}
/*
	function get_motion() {
		$.ajax({  
			type: 'GET', 				
			url: '/match/get-actions',
			success: function(json) {
				var time = 0;
				forEach(json, function(key, val){
					time += val[1];
					forEach(val[0], function(key1, val1){
						var unit = $('#unit_'+key1);
						unit.animate({
							left: val1[0]+'px',
							bottom: val1[1]+'px'
						}, {duration: val[1], easing: 'linear'});
					});
				});
				setTimeout(function() {
					get_motion();
				}, time);
			}
		});
	}

	get_motion();
*/
});