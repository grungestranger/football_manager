$.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
});

function forEach(data, callback) {
	for(var key in data){
		if(data.hasOwnProperty(key)){
			if (callback(key, data[key]) === false) {
				break;
			}
		}
	}
}

function isset(variable) {
	return typeof(variable) != 'undefined' ? true : false;
}

function htmlspecialchars(string) {
	return $('<div/>').text(string).html();
}

// popups
$('.popup').click(function(){
	$(this).hide();
});

$('.popup_content').click(function(event){
	event.stopPropagation();
});