// socket
var socket = io.connect('http://localhost:8080', {
	'query': 'token=' + $('meta[name="jwt"]').attr('content')
});

$(document).ready(function(){
	// popups
	$('.popup').click(function(){
		$(this).hide();
	});

	$('.popup_content').click(function(event){
		event.stopPropagation();
	});
	// show errors
	$('#errors').show();

	// socket
	socket.on('connect', function () {
		console.log("authorized!!!");
	});
	socket.on('common', function (data) {
		data = JSON.parse(data);
		console.log(data);
	});
});
