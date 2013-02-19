$(document).ready(function() {

  categories = ['legal','academic','historical','newspapers','portals','business','census','economic','government','international relations','justice','labor'];
  $.getJSON("http://hlsl10.law.harvard.edu/dev/annie/law-apps/api/item/search?callback=?&limit=25&sort=clicks desc", function(data) {
      data.category = "everything";
		  showResults(data);
		});
	
	$.each(categories, function(index, value) {
	  $.getJSON("http://hlsl10.law.harvard.edu/dev/annie/law-apps/api/item/search?callback=?&limit=25&filter=category:" + value + "&sort=clicks desc", function(data) {
	    value = value.split(' ');
	    value = value[0];
		  data.category = value;
		  showResults(data);
		});
	});
});

function showResults(data){ 
  var source = $("#search-template").html();
	var template = Handlebars.compile(source);
  $('#' + data.category + '-search-results').html(template(data));
}