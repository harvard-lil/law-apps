var $j = jQuery.noConflict();

$j(document).ready(function() {
  $j('#search-awesome').submit(function() {
		var query = $j("#query").val();
		$j.get("api/item/search?limit=45&filter=_all:" + query, function(data) {
		  showResults(data);
		});
		return false;
	});
});

function showResults(data){ 
  var source = $j("#search-template").html();
	var template = Handlebars.compile(source);
  $j('#search-results').html(template(data));
}