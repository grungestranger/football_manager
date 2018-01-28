// socket
/*var socket = io.connect('http://localhost:8080', {
	'query': 'token=' + $('meta[name="jwt"]').attr('content')
});
socket.on('connect', function () {
	console.log("authorized!!!");
});*/
var socket = io.connect('http://localhost:8080');
/*
socket.on('connect', function() {

});
*/
socket.on('needToken', function() {
	$.ajax({  				
		url: '/jwt',
		success: function(data) {
			socket.emit('token', data.token);
		}
	});
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
	/*socket.on('app', function (data) {
		data = JSON.parse(data);
		console.log(data);
	});*/
});
