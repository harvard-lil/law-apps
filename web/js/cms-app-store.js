// script for the CMS app store page
// http://law.harvard.edu/library/research/databases/apps.html

//var $ = jQuery.noConflict();

var params = {
    limit: 25,
    start: 0
};

var filters = [];

var api_response = null;
var categories = null;
var query = "http";

$(document).ready(function() {
  $('head').append('<link rel="stylesheet" href="http://hlsl10.law.harvard.edu/dev/annie/law-apps/css/cms-app-store.css" type="text/css" />').delay(600).each(function() {
    $('#search-apps').fadeIn();
  });
  
  var params = getParams();
  query = params.q;
  if(query) {
    $("#query").val(query);
    getResults();
  } else {
    getHome();
  }
		
  $('#search-apps').submit(function() {
    query = $("#query").val();
		/*params.start = 0;
    getResults();*/
    window.location = "apps.html" + "?q=" + query;
		return false;
	});

	$(".filter").live("click", function(event){
	  var filter = $(this).attr("id").toLowerCase();
    filters.push('category:' + filter);
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
  
  $(".link-out").live("click", function(event){
    //var link = $(this).find('a').attr('href');
    var link = $(this).data('href');
		window.open(link);
		event.preventDefault();
  });
  
  
	
});

function getHome() {
  $.getJSON("http://hlsl10.law.harvard.edu/dev/annie/law-apps/api/item/search?callback=?&limit=6&start=0&sort=clicks desc&filter[]=_all:http", function(json_data) {
    var results_list = '';
    $.each(json_data.docs, function(key, value) { 
      var description = value.description.replace(/<a.*href="(.*?)".*>(.*?)<\/a>/gi, "$2");
      description = description.substr(0, 175);
      description = description.substr(0, Math.min(description.length, description.lastIndexOf(" ")));
      results_list += '<div class="vertscrollbox browse-result link-out category-' + value.category + '" data-href="' + value.link + '"><div class="slide imageslide"><div class="slidewrapper"><div class="browse-preview"><img src="http://hlsl10.law.harvard.edu/dev/annie/law-apps/images/db-thumbs/' + value.slug +'_thumb.jpg" alt=""></div><div class="browse-name">' + value.name + '</div><div class="browse-clicks">' + value.clicks + ' clicks</div></div></div><div class="slide textslide"><div class="slidewrapper"><div class="browse-name">' + value.name + '</div><p>' + description + '</p></div></div></div>';
    });
    $('#app-results').html('<h2>Categories</h2><div id="filters"></div><h2>Popular</h2>' + results_list);
    
    showNew();
  });
  
  function showNew(){
  $.getJSON("http://hlsl10.law.harvard.edu/dev/annie/law-apps/api/item/search?callback=?&limit=6&start=0&sort=last_modified desc&filter[]=_all:http", function(json_data) {
    var results_list = '';
    $.each(json_data.docs, function(key, value) { 
      var description = value.description.replace(/<a.*href="(.*?)".*>(.*?)<\/a>/gi, "$2");
      description = description.substr(0, 175);
      description = description.substr(0, Math.min(description.length, description.lastIndexOf(" ")));
      results_list += '<div class="vertscrollbox browse-result link-out category-' + value.category + '" data-href="' + value.link + '"><div class="slide imageslide"><div class="slidewrapper"><div class="browse-preview"><img src="http://hlsl10.law.harvard.edu/dev/annie/law-apps/images/db-thumbs/' + value.slug +'_thumb.jpg" alt=""></div><div class="browse-name">' + value.name + '</div><div class="browse-clicks">' + value.clicks + ' clicks</div></div></div><div class="slide textslide"><div class="slidewrapper"><div class="browse-name">' + value.name + '</div><p>' + description + '</p></div></div></div>';
    });
    $('#app-results').append('<h2>New</h2>' + results_list + '');
    showCategories();
    $('.vertscrollbox').cycle({
      fx: 'scrollVert',
      timeout: 0,
      containerResize: 0,
      easing: 'easeOutQuint',
      speed: 500
    }).hoverIntent({
      timeout: 500,
      over: function() {
      $(this).cycle('prev');
      },
      out: function() {
      $(this).cycle('next');
      }
    }); 
  });
  }
}

  function showCategories(){   
  $.getJSON("http://hlsl10.law.harvard.edu/dev/annie/law-apps/api/item/categories?callback=?", function(categories) {
    var filters_list = '<ul>';
    var column = 0;
    $.each(categories, function(key, value) { 
      var category = value.charAt(0).toUpperCase() + value.slice(1);
      if(column % 3 === 0)
        filters_list += '</ul><ul>';
      filters_list += '<li id="' + category + '" class="filter control-action category">' + category + '</li>';
      column++;
    });
    $('#filters').html(filters_list + '</ul>');
  });
}

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
    });
}

function showResults(){ 
	var results_list = '';
	$.each(api_response.docs, function(key, value) { 
	  var category = value.category.charAt(0).toUpperCase() + value.category.slice(1);
    results_list += '<div class="result link-out"><h2><a href="' + value.link + '" target="_blank">' + value.name + '</a></h2>';
    
    results_list += '<a href="' + value.link + '"><span class="preview"><img src="http://hlsl10.law.harvard.edu/dev/annie/law-apps/images/db-thumbs/' + value.slug +'_thumb.jpg" alt=""></span></a>';
    results_list += '<span class="desc">' + value.description + '</span><span class="result-category">' + category + '</span><span class="result-clicks">' + value.clicks + ' clicks</span></div>';
  });
  $('#app-results').html('<div id="filters"></div><div class="controls"></div>' + results_list + '<div class="controls"></div>');
  showControls();
  showFacets();
}

function showFacets(){ 
  var filters_list = '<ul>';
  var column = 0;
  $.each(api_response.facets.category.terms, function(key, value) { 
    if(column % 3 === 0)
        filters_list += '</ul><ul>';
    filters_list += '<li id="' + value.term + '" class="filter control-action category column' + column + '">' + value.term + ' (' + value.count + ')</li>';
    column++;
	});
	$('#filters').html(filters_list + '</ul>');
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

function getParams() {
	    var vars = [], hash;

        var hashes = window.location.href.slice(inArray('?', window.location.href) + 1).split('&');

	    // create array for each key
	    for(var i = 0; i < hashes.length; i++) {
	    	hash = hashes[i].split('=');
	    	vars[hash[0]] = [];
	    }
	    
	    // populate newly created entries with values 
	    for(var i = 0; i < hashes.length; i++) {
	        hash = hashes[i].split('=');
	        if (hash[1]) {
	        	vars[hash[0]].push(decodeURIComponent(hash[1].replace(/\+/g, '%20')));
	        }
	    }

	    return vars;
}

function inArray( elem, array ) {
        if ( array.indexOf ) {
            return array.indexOf( elem );
        }

        for ( var i = 0, length = array.length; i < length; i++ ) {
            if ( array[ i ] === elem ) {
                return i;
            }
        }
        return -1;
    }


