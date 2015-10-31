$(function(){
	$.delete({
		categories : {
			category : {
				url : "/admin/module/blog/category/delete.ajax"
			},
			post : {
				url : "/admin/module/blog/post/delete.ajax"
			}
		}
	});
});