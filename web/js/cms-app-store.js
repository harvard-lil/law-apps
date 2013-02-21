// script for the CMS app store page
// http://law.harvard.edu/library/research/databases/apps.html

//var $ = jQuery.noConflict();

$(document).ready(function() {
  $('head').append('<link rel="stylesheet" href="http://hlsl10.law.harvard.edu/dev/annie/law-apps/css/cms-app-store.css" type="text/css" />');
  $('#search-template').hide();
  $('#search-apps').css('margin-bottom', '15px');

  $('#search-apps').submit(function() {
		var query = $("#query").val();
		$.getJSON("http://hlsl10.law.harvard.edu/dev/annie/law-apps/api/item/search?callback=?&limit=45&filter=_all:" + query, function(data) {
		  //showResults(data);
		  $('#search-results').html('<p>' + data.num_found + ' results</p>');
		  var results_list = '';
		  $.each(data.docs, function(key, value) { 
        results_list += '<div class="result"><h2><a href="' + value.link + '" target="_blank">' + value.name + '</a></h2>';
        results_list += '<a href="' + value.link + '"><span class="preview"><img src="http://hlsl10.law.harvard.edu/dev/annie/law-apps/images/db-thumbs/' + value.slug +'_thumb.jpg" alt=""></span></a>';
        results_list += '<span class="desc">' + value.description + '</span></div>';
      });
      $('#search-results').append(results_list);
      //$('#search-results').append('<dl>' + results_list + '</dl>').fadeIn();
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
