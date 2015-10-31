$(function(){
	$("[name='post[date]']").datepicker();
	$.wysiwyg.init(['post[full]', 'post[introduction]']);
});