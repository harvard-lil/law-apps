//  This is currently used to power the search box on the database page
//  http://law.harvard.edu/library/research/databases/index.html
var app_data = '';
var guide_data = '';
$(document).ready(function() {
  $('#search-results, #hide-results').hide();
  $('#hide-results').css('float', 'right').css('cursor', 'pointer');
  $('#search-awesome').css('margin-bottom', '15px');
  $('#hide-results').on("click", function(event){
	  $('#search-results, #hide-results').fadeOut();
  });
  
  $('#search-awesome').submit(function() {
		var query = $("#query").val();
		$.getJSON("http://hlsl10.law.harvard.edu/dev/annie/law-apps/api/item/search?callback=?&limit=45&filter[]=type:app&filter[]=_all:" + query, function(data) {
		  app_data = data;
		  $.getJSON("http://hlsl10.law.harvard.edu/dev/annie/law-apps/api/item/search?callback=?&limit=45&filter[]=type:guide&filter[]=_all:" + query, function(data) {
		    guide_data = data;
		    $('#search-results').html('<p>' + app_data.num_found + ' database(s), ' + guide_data.num_found + ' guide(s)</p>');
		    var results_list = '';
		    if(app_data.num_found > 0) {
		      results_list = '<h3>Databases</h3>';
		    }
		    $.each(app_data.docs, function(key, value) { 
          results_list += '<dt><a href="' + value.link + '" target="_blank">' + value.name + '</a></dt>';
          results_list += '<dd>' + value.description + '</dd>';
        });
        if(guide_data.num_found > 0) {
		      results_list += '<h3>Guides</h3>';
		    }
        $.each(guide_data.docs, function(key, value) { 
          results_list += '<dt><a href="' + value.link + '" target="_blank">' + value.name + '</a></dt>';
          results_list += '<dd>' + value.description + '</dd>';
        });
        $('#search-results').append('<dl>' + results_list + '</dl>').fadeIn();
        $('#hide-results').fadeIn();
		  });
		});
		return false;
	});
	
	$('.wysiwyg').on("click", "dd a, li a, dt a", function(event) {
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