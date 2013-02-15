<?php

$master_config = parse_ini_file("../etc/master.ini");

function thumbnail_from_url($slug, $url){
    
    global $master_config;
    //print "slug is $slug and url is $url\n";

    exec('/Applications/wkhtmltopdf.app/Contents/MacOS/wkhtmltopdf --quiet ' . $url . ' ' . $master_config['LAW_APPS_HOME'] . '/web/images/db-thumbs/' . $slug . '.pdf');
    exec('convert -quiet ' . $master_config['LAW_APPS_HOME'] . '/web/images/db-thumbs/' . $slug . '.pdf ' . $master_config['LAW_APPS_HOME'] . '/web/images/db-thumbs/' . $slug . '.jpg');
    exec('convert -quiet ' . $master_config['LAW_APPS_HOME'] . '/web/images/db-thumbs/' . $slug . '.jpg -resize 250x250 ' . $master_config['LAW_APPS_HOME'] . '/web/images/db-thumbs/' . $slug . '_thumb.jpg');
    
    if (!file_exists($master_config['LAW_APPS_HOME'] . '/law-apps/web/images/db-thumbs/' . $slug . '_thumb.jpg')) {
        $message_to_file = date('l jS \of F Y h:i:s A') . ' -- Unable to create' . $slug . "_thumb.jpg for $url Creating a placeholder.\n";
        
        file_put_contents($master_config['LAW_APPS_HOME'] . '/log/thumbnail.log', $message_to_file);
        exec('cp ' . $master_config['LAW_APPS_HOME'] . '/web/images/db-thumbs/blank.jpg ' . $master_config['LAW_APPS_HOME'] . '/web/images/db-thumbs/' . $slug . '_thumb.jpg');
    }

    // Do some cleanup. Remove our PDF
    if (file_exists($master_config['LAW_APPS_HOME'] . '/law-apps/web/images/db-thumbs/' . $slug . '.pdf')) {
        exec('rm ' . $master_config['LAW_APPS_HOME'] . '/law-apps/web/images/db-thumbs/' . $slug . '.pdf');
    }

    // Do some cleanup. Remove our fullsize jpg    
    if (file_exists($master_config['LAW_APPS_HOME'] . '/law-apps/web/images/db-thumbs/' . $slug . '.jpg')) {
        exec('rm ' . $master_config['LAW_APPS_HOME'] . '/law-apps/web/images/db-thumbs/' . $slug . '.jpg');
    }

}

$es_url= 'http://localhost/law-apps/api/item/search?limit=20';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $es_url);
//curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
$a = curl_exec($ch);

$response = json_decode($a);



//thumbnail_from_url('test', 'http://github.com');

foreach ($response->docs as $doc) {
    thumbnail_from_url($doc->slug, $doc->link);
}

?>