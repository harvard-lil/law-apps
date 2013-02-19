$(document).ready(function() {
  
  // On load, focus on the search box  
  $('#query').focus();
    
  $('#search-awesome').submit(function() {
		var query = $("#query").val();
		$.getJSON("http://hlsl7.law.harvard.edu/dev/matt/law-apps/api/item/search?callback=?&limit=45&filter=_all:" + query, function(data) {
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
