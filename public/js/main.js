$(document).ready(function(){
	// socket
	socket.on('main', function (data) {
		data = JSON.parse(data);
		console.log(data);
		switch (data.action) {
			case 'userConnect':
				$('.user[data-id="' + data.id + '"]')
					.removeClass('offline')
					.addClass('online');
				break
			case 'userDisconnect':
				$('.user[data-id="' + data.id + '"]')
					.removeClass('online')
					.addClass('offline');
				break
		}
	});
});