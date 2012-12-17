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
		  $('#search-results').html('<p>' + data.num_found + ' results</p><dl>');
		  $.each(data.docs, function(key, value) { 
        $('#search-results').append('<dt><a href="' + value.link + '">' + value.name + '</a></dt>');
        $('#search-results').append('<dd>' + value.description + '</dd>').fadeIn();
        $('#hide-results').fadeIn();
      });
      $('#search-results').append('</dl>');
		});
		return false;
	});
	
	$('dd a, li a, dt a').on("click", function(event) {
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