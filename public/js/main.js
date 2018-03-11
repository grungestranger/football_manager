$(document).ready(function(){
	// challenge
	$(document).on('click', '.challenge', function(){
		var user = $(this).parent();
		$.ajax({  
			type: 'POST', 				
			url: '/challenge',
			data: {
				user_id: user.data('id')
			},
			success: function(data) {
				if (data.success) {
					user.children('.challenge').hide();
					var userTo = user.clone().prependTo('#challengesFrom');
					userTo.children('.challenge').remove();
					userTo.append($('#stdElements .challengeRemove').clone());
				} else {
					alert(data.error);
				}
			}
		});
		return false;
	});
});
