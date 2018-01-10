$.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
});

function isset(variable) {
	return typeof(variable) != 'undefined' ? true : false;
}

function htmlspecialchars(string) {
	return $('<div/>').text(string).html();
}
