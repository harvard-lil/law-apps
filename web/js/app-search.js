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
        getResults();
        return false;
    });
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

function showResults(){ 
    var source = $("#search-template").html();
    var template = Handlebars.compile(source);
    $('#search-results').html(template(api_response));
}

function showFacets(){ 
    var source = $("#filters-template").html();
    var template = Handlebars.compile(source);
    $('#filters').html(template(api_response.facets.category));
}

function showControls(){
    var data = {num_found: api_response.num_found};
    data.start = params.start;
    
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