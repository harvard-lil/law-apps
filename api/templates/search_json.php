<?php
header('Content-type: application/json');

// Get our results from our controller
$docs = $this->get('results');
$callback = $this->get('callback');

//Start building our response
$response = array();
$response['num_found'] = count($docs);
$response['docs'] = $docs;

// Not much else to do. Dump it to the screen.
if ($callback) echo $callback . '(' . json_encode($response) . ')';
else echo json_encode($response);
?>