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
        var link = 'search?q=' + $('#query').val();
	      window.open(link);
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

$(".link-out").live("click", function(event){
  //var link = $(this).find('a').attr('href');
  var link = $(this).data('href');
	window.open(link);
	event.preventDefault();
});

function showHome(){ 
  $.getJSON("http://hlsl10.law.harvard.edu/dev/annie/law-apps/api/item/search?callback=?&limit=6&start=0&sort=clicks desc&filter[]=_all:http", function(json_data) {
    var source = $("#browse-template").html();
    var template = Handlebars.compile(source);
    $('#popular').html(template(json_data));
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
