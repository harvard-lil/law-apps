var params = {
    limit: 25,
    start: 0
};

var filters = [];

var api_response = null;

$(document).ready(function() {

    // On load, focus on the search box  
    $('#query').focus();
    var params = getParams();
    query = params.q;
    if(query) {
      $("#query").val(query);
      getResults();
    }

    /*$('#search-awesome').submit(function() {
        params.start = 0;
        getResults();
        return false;
    });*/
    
    showCategories();

});

/*$(".filter").live("click", function(event){
    var category = $(this).attr("id").toLowerCase();
    filters.push('category_raw:' + category);
    params.start = 0;
    getResults();
});*/



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

$(".result").live("click", function(event){
  //var link = $(this).find('a').attr('href');
  var link = $(this).data('href');
	window.open(link);
	event.preventDefault();
});

function getResults() {
    var query = $("#query").val();

    var api_url = "api/item/search?callback=?&sort=clicks desc&limit=" + params.limit + "&start=" + params.start;

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

function showCategories(){   
  $.getJSON("http://hlsl10.law.harvard.edu/dev/annie/law-apps/api/item/categories?callback=?", function(categories) {
    var source = $("#categories-template").html();
    var template = Handlebars.compile(source);
    $('#categories').append(template(categories));
  });
}

Handlebars.registerHelper('displayCategory', function(category) {
  return category.charAt(0).toUpperCase() + category.slice(1);
});

function showResults(){ 
    var source = $("#search-template").html();
    var template = Handlebars.compile(source);
    $('#search-results').html(template(api_response));
}

Handlebars.registerHelper('displayDescription', function(description) {
  var display = description.replace(/<a.*href="(.*?)".*>(.*?)<\/a>/gi, "$2");
  return display;
});

function showFacets(){
    var source = $("#filters-template").html();
    var template = Handlebars.compile(source);
    $('#filters').html(template(api_response.facets['category_raw']));
}

function showControls(){
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
    
    var source = $("#controls-template").html();
    var template = Handlebars.compile(source);
    $('.controls').html(template(data));
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