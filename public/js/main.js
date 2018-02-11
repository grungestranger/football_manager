$(document).ready(function(){
	// challenge
	$('.challenge').click(function(){
		var userTo = $(this).data('id');
		$.ajax({  
			type: 'POST', 				
			url: '/challenge',
			data: {
				user_id: userTo
			},
			success: function(data) {
				if (data.success) {
					
				} else {
					alert(data.error);
				}
			}
		});
		return false;
	});
});