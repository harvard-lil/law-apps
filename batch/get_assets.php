<?php

/******************************
* From our online resources, generate thumbnails and favicons 
* from their URLs. 
* 
*
*
* Run on the command line:
* $ php -f get_assets.php
*
* Look in batch/asset-holding-pen/thumbs/ and 
* batch/asset-holding-pen/favicons/ for results
*
* For the URLs that we couldn't generate a thumbnail or a favicon, we copy
* a placeholder over. We also put a not in the approprite log file,
* in the log directory
*
*
*
* Be sure you have imagemagick and wkhtmltopdf installed and in your path
*
* Thanks to wkhtmlpdf and imagemagick for the heavy lifting
******************************/

$master_config = parse_ini_file("../etc/master.ini");

// Where we'll store the thumbnails and favicons we retrieve
$thumbs_dir = $master_config['LAW_APPS_HOME'] . '/batch/asset-holding-pen/thumbs/';
$favicons_dir = $master_config['LAW_APPS_HOME'] . '/batch/asset-holding-pen/favicons/';

// Number of things to get from our API
$num_thumbs = 500;



/////////////////////////////////
// Heavy lifting functions
/////////////////////////////////

// Given a slug a url, create a thumbnail using wkhtmlpdf and imagemagick
function thumbnail_from_url($slug, $url) {

    global $master_config;
    global $thumbs_dir;

    // Some online resources have the same slug. If we've already created a thumb for that slug, don't do anything.
    if (!file_exists($thumbs_dir . $slug . '_thumb.jpg')) {
        exec($master_config['WKHTMLTOPDF_FS_PATH'] . ' --quiet --load-error-handling ignore -B 0 -L 0 -R 0 -T 0 ' . $url . ' ' . $thumbs_dir . $slug . '.pdf');
        exec('convert -quiet ' . $thumbs_dir . $slug . '.pdf[0] ' . $thumbs_dir . $slug . '.jpg');
        exec('convert -quiet ' . $thumbs_dir . $slug . '.jpg -resize 250x250 ' . $thumbs_dir . $slug . '_thumb.jpg');

        // Sometimes things go wonky with wkhtmltopdf
        if (!file_exists($thumbs_dir . $slug . '_thumb.jpg')) {
            $message_to_file = date('l, jS \of F Y h:i:s A') . ' -- Unable to create' . $slug . "_thumb.jpg for $url . Creating a placeholder.\n";

            file_put_contents($master_config['LAW_APPS_HOME'] . '/log/thumbnail.log', $message_to_file, FILE_APPEND);
            exec('cp ' . $thumbs_dir . 'blank.jpg ' . $thumbs_dir . $slug . '_thumb.jpg');
        }

        // Do some cleanup. Remove our PDF
        if (file_exists($thumbs_dir . $slug . '.pdf')) {
            exec('rm ' . $thumbs_dir . $slug . '.pdf');
        }

        // Do some cleanup. Remove our fullsize jpg    
        if (file_exists($thumbs_dir . $slug . '.jpg')) {
            exec('rm ' . $thumbs_dir . $slug . '.jpg');
        }
    }
}

function favicon_from_url($slug, $url){
    // Give a slug and a url, get the favicon.
    // With Harvard online resources, we generally have to jump through proxies

    global $master_config;
    global $favicons_dir;

    // Some of our resources have the same favicon. If that's the case here, do nothing
    if (!file_exists($favicons_dir . $slug . '.png')) {

        // Set our cookie
        $cookie_file = tempnam($master_config['LAW_APPS_HOME'] . 'batch/cookie.txt', 'foo');

        // We geneally have proxies and other nastiness. Following any redirects to get cookies and content url
        // We'll grab the final destination page content too, because we might need to parse it
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.2.13) Gecko/20101206 Ubuntu/10.10 (maverick) Firefox/3.6.13');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);        
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);

        // Store our page content, we might to parse it later.
        $page = curl_exec($ch);

        // The last redirected URL
        $last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        $parsed_last_url = parse_url($last_url);


        // A flag we'll use to indicate if favicon was found
        $favicon_found = false;


        // We'll first try to get the favicon by getting http://redirectedurl.whatever/favicon.ico
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $parsed_last_url['host'] . '/favicon.ico');
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.2.13) Gecko/20101206 Ubuntu/10.10 (maverick) Firefox/3.6.13');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        $response = curl_exec($ch);
        $response_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response_http_code !== 404) {
            // If we retrieved a .ico at the host's root, write it to disk

            file_put_contents($favicons_dir . $slug . '.ico', $response);
            $favicon_found =  $slug . '.ico';
        } else {
            // No .ico at the root's host, so let's parse the HTML response and look for 
            // something like <link rel="shortcut icon" href="favicon.ico">

            // A list of things that someone might call their favicon link
            $list_of_attr = array('shortcut','icon','shortcut',' icon');

            $dom_doc = new DOMDocument();

            // A kludge to supress warnings
            libxml_use_internal_errors(true);

            $dom_doc->loadHTML($page);

            // If we find a link to a favicon, we'll put it here
            $favicon_link_in_doc;

            // Loop through all possible links in dom. 
            foreach($dom_doc->getElementsByTagName('link') as $link) {
                if (in_array($link->getAttribute('rel'), $list_of_attr)) {
                    $favicon_link_in_doc = $link->getAttribute('href');
                }
            }

            $favicon_url;
            $favicon_data;

            // If we found a link that we think is a favicon, let's turn it into a URL we can feed cURL
            if (!empty($favicon_link_in_doc)) {
                if (strpos($favicon_link_in_doc, 'http') === 0){
                    $favicon_url = $favicon_link_in_doc;
                } else {
                    $favicon_url = $parsed_last_url['scheme'] . '://' . $parsed_last_url['host'] . $favicon_link_in_doc;
                }
            }

            if (!empty($favicon_url)) {
                $ch  = curl_init();
                curl_setopt($ch, CURLOPT_URL, $favicon_url);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.2.13) Gecko/20101206 Ubuntu/10.10 (maverick) Firefox/3.6.13');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
                $response = curl_exec($ch);
                curl_close($ch);

                // Some nastiness to get the file extension. We should probably do this through mime-types
                // or something
                preg_match('([^\.]+$)', $favicon_url, $matches);
                $extension = $matches[0];

                file_put_contents($favicons_dir . $slug . '.' . $extension, $response);
                $favicon_found =  $slug . '.' . $extension;
            }
        }

        // If we ended up with a favicon, format it
        if ($favicon_found) {
            // If we found a favicon, and it's an ico, convert it to a png
            exec('convert -quiet "' . $favicons_dir . $favicon_found . '" -thumbnail 16x16 -alpha on -background none -flatten "' . $favicons_dir . $slug . '.png"');

            // We don't need to original
            exec('rm ' . $favicons_dir . $favicon_found);
        } else {
            // Copy over our blank slug so that we serve something up (makes the client-side code a little easier)

            $message_to_file = date('l, jS \of F Y h:i:s A') . " -- Unable to create favicon for $url . Creating a placeholder.\n";

            file_put_contents($master_config['LAW_APPS_HOME'] . '/log/favicon.log', $message_to_file, FILE_APPEND);

            exec('cp ' . $favicons_dir. 'blank.png ' . $favicons_dir . $slug . '.png');
        }
    }
}


/////////////////////////////////
// Main logic
/////////////////////////////////

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
    favicon_from_url($doc->slug, $doc->link);
}

?>
