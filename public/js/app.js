// socket
var socket = io.connect('http://' + window.location.hostname + ':8080');

socket.on('needToken', function() {
	$.ajax({  				
		url: '/jwt',
		success: function(data) {
			socket.emit('token', data.token);
		}
	});
});

socket.on('message', function (data) {
	data = JSON.parse(data);
	switch (data.action) {
		case 'userConnect':
			$('.user[data-id="' + data.user.id + '"]').removeClass('offline')
				.addClass('online');
			break
		case 'userDisconnect':
			$('.user[data-id="' + data.user.id + '"]').removeClass('online')
				.addClass('offline');
			break
		case 'challengeAdd':
			$('#users .user[data-id="' + data.user.id + '"]').children('.challenge').hide();
			var userFrom = $('#stdElements .user').clone().prependTo('#challengesTo');
			userFrom.attr('data-id', data.user.id);
			userFrom.children('.name').html(htmlspecialchars(data.user.name));
			if (data.user.match) {
				userFrom.addClass('match');
			}
			break
		case 'fromChallengeRemove':
			$('#challengesTo .user[data-id="' + data.user.id + '"]').remove();
			$('#users .user[data-id="' + data.user.id + '"]').children('.challenge').show();
			break
		case 'toChallengeRemove':
			$('#challengesFrom .user[data-id="' + data.user.id + '"]').remove();
			$('#users .user[data-id="' + data.user.id + '"]').children('.challenge').show();
			break
		case 'usersStartMatch':
			$.each(data.users, function(k, v){
				$('.user[data-id="' + v + '"]').addClass('match');
			});
			break
		case 'startMatch':
			$('#mainMenu').append('<li><a href="/match">Match vs ' + htmlspecialchars(data.user.name) + '</a></li>');
			$('#challengesFrom .user[data-id="' + data.user.id + '"]').remove();
			$('#users .user[data-id="' + data.user.id + '"]').children('.challenge').show();
			break
	}
});

$(document).ready(function(){
	// popups
	$('.popup').click(function(){
		$(this).hide();
	});
	$('.popup_content').click(function(event){
		event.stopPropagation();
	});

	// challengeRemove
	$(document).on('click', '.challengeRemove', function(){
		var user = $(this).parent();
		var action = user.parent('#challengesFrom').length ? 'from' : 'to';
		$.ajax({  
			type: 'POST', 				
			url: '/' + action + '-challenge-remove',
			data: {
				user_id: user.data('id')
			},
			success: function(data) {
				if (data.success) {
					$('#users .user[data-id="' + user.data('id') + '"]').children('.challenge').show();
					user.remove();
				} else {
					alert(data.error);
				}
			}
		});
		return false;
	});

	// play
	$(document).on('click', '.play', function(){
		var user = $(this).parent();
		$.ajax({  
			type: 'POST', 				
			url: '/play',
			data: {
				user_id: user.data('id')
			},
			success: function(data) {
				if (data.success) {
					var name = user.children('.name').html();
					$('#mainMenu').append('<li><a href="/match">Match vs ' + name + '</a></li>');
					user.remove();
				} else {
					alert(data.error);
				}
			}
		});
		return false;
	});
});
