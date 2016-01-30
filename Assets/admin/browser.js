$(function(){
	$.delete({
		categories : {
			category : {
				url : "/admin/module/blog/category/delete"
			},
			post : {
				url : "/admin/module/blog/post/delete"
			}
		}
	});
});