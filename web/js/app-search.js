var params = {
    limit: 25,
    start: 0
};

var filters = [];

var api_response = null;

$(document).ready(function() {

    // On load, focus on the search box  
    $('#query').focus();

    $('#search-awesome').submit(function() {
        params.start = 0;
        getResults();
        return false;
    });
    
    
      showHome();
      showCategories();

});

$(".filter").live("click", function(event){
    filters.push('category_raw:' + $(this).attr("id"));
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

function getResults() {
    var query = $("#query").val();

    var api_url = "api/item/search?callback=?&limit=" + params.limit + "&start=" + params.start;

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

function showHome(){ 
  $.getJSON("http://hlsl10.law.harvard.edu/dev/annie/law-apps/api/item/search?callback=?&limit=6&start=0&sort=clicks desc&filter[]=_all:http", function(json_data) {
    var source = $("#browse-template").html();
    var template = Handlebars.compile(source);
    $('#popular').html(template(json_data));
  });
  
  $.getJSON("http://hlsl10.law.harvard.edu/dev/annie/law-apps/api/item/search?callback=?&limit=6&start=0&sort=last_modified desc&filter[]=_all:http", function(json_data) {
    var source = $("#browse-template").html();
    var template = Handlebars.compile(source);
    $('#new').html(template(json_data));
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

Handlebars.registerHelper('displayDescription', function(description) {
  var display = description.replace(/<a.*href="(.*?)".*>(.*?)<\/a>/gi, "$2");
  display = display.substr(0, 175);
  display = display.substr(0, Math.min(display.length, display.lastIndexOf(" ")));
  return display;
});

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
    $('#controls').html(template(data));
}