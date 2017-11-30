$.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
});

function forEach(data, callback) {
	for(var key in data){
		if(data.hasOwnProperty(key)){
			callback(key, data[key]);
		}
	}
}

// popups
$('.popup').click(function(){
	$(this).hide();
});

$('.popup_content').click(function(event){
	event.stopPropagation();
});