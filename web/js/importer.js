$(function() {
	$('.body > tr.divider')
		.toggleClass('expand')
		.css('cursor', 'pointer')
		.click(function() {
			$(this).siblings(':not(.divider).' + this.id).toggle();
			$(this).toggleClass('expand')
			$(this).toggleClass('collapse');
	});
	$('.body > tr:not(.divider)').hide();
});