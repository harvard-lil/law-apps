$(document).ready(function() {
  $('#search-awesome').submit(function() {
		var query = $("#query").val();
		$.get("api/item/search?limit=45&filter=_all:" + query, function(data) {
		  showResults(data);
		});
		return false;
	});
});

function showResults(data){ 
  var source = $("#search-template").html();
	var template = Handlebars.compile(source);
  $('#search-results').html(template(data));
}