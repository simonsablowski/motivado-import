$(function() {
	$('.check-all')
		.click(function() {
			$('input[type="checkbox"]').attr('checked', $(this).is(':checked'));
		});
	
	$('.import > tr.divider')
		.toggleClass('expand')
		.css('cursor', 'pointer')
		.click(function() {
			$(this).siblings(':not(.divider).' + this.id).toggle();
			$(this).toggleClass('expand')
			$(this).toggleClass('collapse');
		});
	$('.import > tr:not(.divider)').hide();
});