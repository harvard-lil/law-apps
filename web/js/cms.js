$(document).ready(function() {
  $('#search-results, #hide-results').hide();
  $('#hide-results').css('float', 'right').css('cursor', 'pointer');
  $('#search-awesome').css('margin-bottom', '15px');
  $('#hide-results').on("click", function(event){
	  $('#search-results, #hide-results').fadeOut();
  });
  
  $('#search-awesome').submit(function() {
		var query = $("#query").val();
		$.getJSON("http://hlsl10.law.harvard.edu/dev/annie/law-apps/api/item/search?callback=?&limit=45&filter=_all:" + query, function(data) {
		  //showResults(data);
		  $('#search-results').html('<p>' + data.num_found + ' results</p>');
		  var results_list = '';
		  $.each(data.docs, function(key, value) { 
        results_list += '<dt><a href="' + value.link + '" target="_blank">' + value.name + '</a></dt>';
        results_list += '<dd>' + value.description + '</dd>';
      });
      $('#search-results').append('<dl>' + results_list + '</dl>').fadeIn();
      $('#hide-results').fadeIn();
		});
		return false;
	});
	
	$('dd, li, dt').on("click", "a", function(event) {
		var link = $(this).attr('href');
		var url = "http://hlsl10.law.harvard.edu/dev/annie/law-apps/api/item/click";
      
    $.post(url, {link: link}, function(data) {
      //$('#response').html('<p>clicked ' + link + '</p>');
    });
	});
	
});

function showResults(data){ 
  var source = $("#search-template").html();
	var template = Handlebars.compile(source);
  $('#search-results').html(template(data));
}