<?php

/******************************
 * Get the URLs from all of our online resources. From them,
 * generate thumbnails from their URLs. 
 * 
 * Thanks to wkhtmlpdf and imagemagick for the heavy lifting
 *
 *
 * Run on the command line:
 * $ php -f get_thumbnails.php
 *
 * Look in law-apps/web/images/db-thumbs for results
 */

$master_config = parse_ini_file("../etc/master.ini");

$num_thumbs = 500;

// Given a slug a url, create a thumbnail using wkhtmlpdf and imagemagick
function thumbnail_from_url($slug, $url){
    
    global $master_config;
    
    //print "slug is $slug and url is $url\n";

    // Some online resources have the same slug. If we've already created a thumb for that slug, don't do anything.
    if (!file_exists($master_config['LAW_APPS_HOME'] . '/web/images/db-thumbs/' . $slug . '_thumb.jpg')) {
        exec($master_config['WKHTMLTOPDF_FS_PATH'] . ' --quiet -B 0 -L 0 -R 0 -T 0 ' . $url . ' ' . $master_config['LAW_APPS_HOME'] . '/web/images/db-thumbs/' . $slug . '.pdf');
        exec('convert -quiet ' . $master_config['LAW_APPS_HOME'] . '/web/images/db-thumbs/' . $slug . '.pdf ' . $master_config['LAW_APPS_HOME'] . '/web/images/db-thumbs/' . $slug . '.jpg');
        exec('convert -quiet ' . $master_config['LAW_APPS_HOME'] . '/web/images/db-thumbs/' . $slug . '.jpg -resize 250x250 ' . $master_config['LAW_APPS_HOME'] . '/web/images/db-thumbs/' . $slug . '_thumb.jpg');
    
        if (!file_exists($master_config['LAW_APPS_HOME'] . '/web/images/db-thumbs/' . $slug . '_thumb.jpg')) {
            $message_to_file = date('l jS \of F Y h:i:s A') . ' -- Unable to create' . $slug . "_thumb.jpg for $url Creating a placeholder.\n";
        
            file_put_contents($master_config['LAW_APPS_HOME'] . '/log/thumbnail.log', $message_to_file, FILE_APPEND);
            exec('cp ' . $master_config['LAW_APPS_HOME'] . '/web/images/db-thumbs/blank.jpg ' . $master_config['LAW_APPS_HOME'] . '/web/images/db-thumbs/' . $slug . '_thumb.jpg');
        }

        // Do some cleanup. Remove our PDF
        if (file_exists($master_config['LAW_APPS_HOME'] . '/web/images/db-thumbs/' . $slug . '.pdf')) {
            exec('rm ' . $master_config['LAW_APPS_HOME'] . '/web/images/db-thumbs/' . $slug . '.pdf');
        }

        // Do some cleanup. Remove our fullsize jpg    
        if (file_exists($master_config['LAW_APPS_HOME'] . '/web/images/db-thumbs/' . $slug . '.jpg')) {
            exec('rm ' . $master_config['LAW_APPS_HOME'] . '/web/images/db-thumbs/' . $slug . '.jpg');
        }
    }
}



// Get all items in our datastore
$api_url =  $master_config['URL_ITEM_API'] . '?limit=' . $num_thumbs;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
$a = curl_exec($ch);

$response = json_decode($a);

// We should have URLs to the online resource and their slugs. Get thumbnails and put them on disk.
foreach ($response->docs as $doc) {
    thumbnail_from_url($doc->slug, $doc->link);
}

?>