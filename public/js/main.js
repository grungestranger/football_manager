$(document).ready(function(){
	// challenge
	$('.challenge').click(function(){
		var user = $(this).parent();
		$.ajax({  
			type: 'POST', 				
			url: '/challenge',
			data: {
				user_id: user.data('id')
			},
			success: function(data) {
				if (data.success) {
					user.children('.challenge').remove();
					user.clone().prependTo('#challengesFrom');
				} else {
					alert(data.error);
				}
			}
		});
		return false;
	});
});