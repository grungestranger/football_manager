$(document).ready(function(){

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
});