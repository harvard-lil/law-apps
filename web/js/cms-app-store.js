// script for the CMS app store page
// http://law.harvard.edu/library/research/databases/apps.html

//var $ = jQuery.noConflict();

var params = {
    limit: 25,
    start: 0
};

var filters = [];

var api_response = null;
var query = "http";

$(document).ready(function() {
  $('head').append('<link rel="stylesheet" href="http://hlsl10.law.harvard.edu/dev/annie/law-apps/css/cms-app-store.css" type="text/css" />').delay(600).each(function() {
    $('#search-apps').fadeIn();
  });
  
  getResults();
		
  $('#search-apps').submit(function() {
    query = $("#query").val();
		params.start = 0;
    getResults();
		return false;
	});

	$(".filter").live("click", function(event){
    filters.push('category:' + $(this).attr("id"));
    params.start = 0;
    getResults();
  });
  
  $("#clear").live("click", function(event){
      filters.length = 0;
      params.start = 0;
      getResults();
  });
  
  $("#prev").live("click", function(event){
      if (params.start - params.limit >= 0) {
          params.start = params.start - params.limit;
      }
      getResults();
  });
  
  $("#next").live("click", function(event){
      if (params.start + params.limit <= api_response.num_found) {
          params.start = params.start + params.limit;
      }
      getResults();
  });
	
});

function getResults() {

    var api_url = "http://hlsl10.law.harvard.edu/dev/annie/law-apps/api/item/search?callback=?&sort=clicks desc&limit=" + params.limit + "&start=" + params.start;

    $.each(filters, function(index, value) {
        api_url = api_url + "&filter[]=" + value;
    });
    
    if (query) {
        api_url = api_url + "&filter[]=_all:" + query;
    }

    $.getJSON(api_url, function(json_data) {
        api_response = json_data;
        showResults();
        showFacets();
        showControls();
    });
}

function showResults(){ 
	var results_list = '';
	$.each(api_response.docs, function(key, value) { 
    results_list += '<div class="result"><h2><a href="' + value.link + '" target="_blank">' + value.name + '</a></h2>';
    results_list += '<a href="' + value.link + '"><span class="preview"><img src="http://hlsl10.law.harvard.edu/dev/annie/law-apps/images/db-thumbs/' + value.slug +'_thumb.jpg" alt=""></span></a>';
    results_list += '<span class="desc">' + value.description + '</span></div>';
  });
  $('#search-results').html(results_list);
}

function showFacets(){ 
  var filters_list = '';
  var column = 1;
  $.each(api_response.facets.category.terms, function(key, value) { 
    filters_list += '<li id="' + value.term + '" class="filter control-action column' + column + '">' + value.term + ' (' + value.count + ')</li>';
    column++;
	});
	$('#filters').html('<ul>' + filters_list + '</ul>');
}

function showControls(){
  var controls_html = '';
  $('.controls').html(controls_html);
    var data = {num_found: api_response.num_found};
    data.start = params.start + 1;
    
    if (params.start + params.limit < api_response.num_found) {
        data.end = params.start + params.limit;
    } else {
        data.end = api_response.num_found;
    }
    
    if (data.end < api_response.num_found ) {
        data.next = true;
    }

    if (data.start >= params.limit ) {
        data.prev = true;
    }
    
    if (filters.length !== 0) {
        data.clear = true;
    }
    
    if(data.clear) {
			controls_html += '<p id="clear" class="control-action">Clear filters</p>';
		}
		
		controls_html += '<p>Showing ' + data.start + ' to ' + data.end + ' of ' + data.num_found + ' results';

		if(data.prev) {
			controls_html += ' <span id="prev" class="control-action">Previous</span>';
		}
		
		if(data.next) {
			controls_html += ' <span id="next" class="control-action">Next</span>';
		}
		
		controls_html += '</p>';
		if(api_response.num_found > 0)
		  $('.controls').html(controls_html);
		else 
		  $('.controls:first').html('<p>No results for "' + query + '"</p>');
		  
    
    //var source = $("#controls-template").html();
    //var template = Handlebars.compile(source);
    //$('#controls').html(template(data));
}
