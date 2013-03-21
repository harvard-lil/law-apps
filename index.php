<?php

// Load F3
$f3 = require_once dirname(__FILE__) . '/lib/f3/base.php';

// Comment out our debug when in prod
$f3->set('DEBUG',3);

// Load our config file
$f3->config(dirname(__FILE__) . '/etc/master.ini');

// Let F3 load our other things
$autoload_path = "{$f3->get('LAW_APPS_HOME')}/api/classes/; {$f3->get('LAW_APPS_HOME')}/lib/;";
$f3->set('AUTOLOAD', $autoload_path);

// API business
$f3->route('GET /api/item/scrape', 'Item->scrape');
$f3->route('GET /api/item/populate', 'Item->populate');
$f3->route('GET /api/item/categories', 'Item->categories');
$f3->route('POST /api/item/click', 'Item->click');
$f3->route('GET /api/item/search', 'Item->search');

$f3->run();

?>
