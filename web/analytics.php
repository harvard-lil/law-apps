<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<title>Law Apps Analytics</title> 

<link rel="stylesheet" href="css/bootstrap.css" type="text/css" />
<link href="images/favicon.ico" rel="shortcut icon">
<style>

h1 {
  color: #11cbf7;
}

</style>

<script type="text/javascript" src="js/jquery-1.7.1.min.js"></script>
<script src="js/handlebars.js"></script>
<script type="text/javascript" src="js/analytics.js">
</script>
</head>
<body>
<div class="container">
  <div class="row">
    <div id="everything-search-results" class="span4"></div>

<?php
  $count = 1;
  $categories = array('legal','academic','historical','newspapers','portals','business','census','economic','government','international','justice','labor');
  
  foreach($categories as $category) {
    if($count %3 == 0) {
      echo "</div><div class='row'>";
    }
    echo "<div id='$category-search-results' class='span4'></div>";
    $count++;
  }
?>
  
</div>
		<script id="search-template" type="text/x-handlebars-template">
		  <h6>{{category}}</h6>
        {{#docs}}
          <p><a href="{{link}}">{{name}}</a> <b>{{clicks}}</b></p>
        {{/docs}}
		</script>
</body>
</html>