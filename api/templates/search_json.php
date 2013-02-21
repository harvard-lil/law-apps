<?php
header('Content-type: application/json');

// Get our results from our controller
$docs = $this->get('results');
$facets = $this->get('facets');
$num_found = $this->get('num_found');
$callback = $this->get('callback');

//Start building our response
$response = array();
$response['num_found'] = $num_found;
$response['docs'] = $docs;
$response['facets'] = $facets;

// Not much else to do. Dump it to the screen.
if ($callback) echo $callback . '(' . json_encode($response) . ')';
    else echo json_encode($response);
?>