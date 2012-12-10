<?php

class Item extends F3instance {

    function get_single() {
        // Given an id, get the items details
        // TODO: Some request and ES response validation

        $url = $this->get('ELASTICSEARCH_URL') . $this->get('PARAMS.item_id');
        $ch = curl_init();
        $method = "GET";

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        $results = curl_exec($ch);
        curl_close($ch);
        
        $this->set('results', json_decode($results, true));
        $path_to_template = 'api/templates/direct_access_json.php';
        echo $this->render($path_to_template);
    }

    function search() {
        // Do some searching on things coming in from the filter URL param

        /* Start building the query object. We hope to end up with something like:
        $reqeust = '{
            "from" : 0,
            "size": 10,
            "query" : {
                "terms" : {
                    "creator" : [ "card" ]
                }
            },
            sort: {
                title: {
                    order: "desc"
                }
            }
        }';
        */
        $request = array();

        // Users can query by specifying an url param like &filter=title:ender
        // TODO: We should allow for multiple filters.
        $key_and_val = explode(":", $this->get('GET.filter'));
        if (count($key_and_val) == 2 and !empty($key_and_val[0]) and !empty($key_and_val[1])) {
            $request['query']['query_string']['fields'] = array($key_and_val[0]);
            $request['query']['query_string']['query'] = '*' . $key_and_val[1] . '*';
            $request['query']['query_string']['default_operator'] = 'AND';
        } else {
            $request['query'] = array("match_all" => new stdClass);
        }
        //$request['query']['query_string']['query'] = 'American FactFinder';
        // start parameter (elasticsearch calls this 'from')
        $incoming_start = $this->get('GET.start');
        if (!empty($incoming_start)) {
            $request['from'] = $this->get('GET.start');
        }
        
        // limit parameter (elasticsearch calls this 'size')
        $incoming_limit = $this->get('GET.limit');
        if (!empty($incoming_limit)) {
            $request['size'] = $this->get('GET.limit');
        }
        
        // sort parameter
        $incoming_sort = $this->get('GET.sort');
        $sort_field_and_dir = explode(" ", $this->get('GET.sort'));
        if (count($sort_field_and_dir) == 2) {
            $request['sort'] = array($sort_field_and_dir[0] => array('order' => $sort_field_and_dir[1]));
        }
        
        // We now have our built request, let's jsonify it and send it to ES
        $jsoned_request = json_encode($request);
        
        $url = $this->get('ELASTICSEARCH_URL') . '_search';
        $ch = curl_init();
        $method = "GET";

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsoned_request);

        $results = curl_exec($ch);
        curl_close($ch);
        
        // We should have a response. Let's pull the docs out of it
        $cleaned_results = $this->get_docs_from_es_response(json_decode($results, True));
        // callback for jsonp requests
        $incoming_callback = $this->get('GET.callback');
        if (!empty($incoming_callback)) {
            $this->set('callback', $this->get('GET.callback'));
        }
        
        // We don't want dupes. Dedupe based on hollis_id
        $deduped_docs = $this->dedupe_using_hollis_id($cleaned_results);
        
        $this->set('results', $deduped_docs);
        //$this->set('results', $cleaned_results);
        $path_to_template = 'api/templates/search_json.php';
        echo $this->render($path_to_template);
    }
    
    function recently_awesome() {
        // Get the recently awesome (the items that were most recently
        // dropped in the Awesome Box)
        
        $request = array();

        // Match all items
        $request['query'] = array("match_all" => new stdClass);
        
        // start parameter (elasticsearch calls this 'from')
        $incoming_start = $this->get('GET.start');
        if (!empty($incoming_start)) {
            $request['from'] = $this->get('GET.start');
        }
        
        // limit parameter (elasticsearch calls this 'size')
        $request['size'] = 9;
        
        // limit parameter (elasticsearch calls this 'size')
        $incoming_limit = $this->get('GET.limit');
        if (!empty($incoming_limit)) {
            $request['size'] = $this->get('GET.limit');
        }
        
        // sort parameter
        $request['sort'] = array('last_modified' => array('order' => 'desc'));
        
        // We now have our built request, let's jsonify it and send it to ES
        $jsoned_request = json_encode($request);
        
        $url = $this->get('ELASTICSEARCH_URL') . '_search';
        $ch = curl_init();
        $method = "GET";

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsoned_request);

        $results = curl_exec($ch);
        curl_close($ch);
        
        // We should have a response. Let's pull the docs out of it
        $cleaned_results = $this->get_docs_from_es_response(json_decode($results, True));
        
        $this->set('results', $cleaned_results);
        $path_to_template = 'api/templates/search_json.php';
        echo $this->render($path_to_template);

    }
    
    function most_awesome() {
        // Get the most awesomed (the items that have been awesomed 
        // the greatest number of times)
        
        $request = array();

        // Match all items
        $request['query'] = array("match_all" => new stdClass);
        
        // limit parameter (elasticsearch calls this 'size')
        $request['size'] = 9;
        
        // sort parameter
        $request['sort'] = array('awesomed' => array('order' => 'desc'));
        
        // We now have our built request, let's jsonify it and send it to ES
        $jsoned_request = json_encode($request);
        
        $url = $this->get('ELASTICSEARCH_URL') . '_search';
        $ch = curl_init();
        $method = "GET";

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsoned_request);

        $results = curl_exec($ch);
        curl_close($ch);
        
        // We should have a response. Let's pull the docs out of it
        $cleaned_results = $this->get_docs_from_es_response(json_decode($results, True));
        
        $this->set('results', $cleaned_results);
        $path_to_template = 'api/templates/search_json.php';
        echo $this->render($path_to_template);

    }
    
    function scrape() {
        include('simple_html_dom.php');
        
        $pages = array('legal','academic','historical','newspapers','portals','business','census','economic','government','international-relations','justice','labor');

        foreach($pages as $page) {
        
          $html = file_get_html("http://law.harvard.edu/library/research/databases/$page.html");
    
          // find all link
          foreach($html->find('dt') as $dt) { 
            $name = addslashes($dt->plaintext);
            $dd = $dt->next_sibling('dd');
            $description = addslashes($dd->innertext);
            $link = $dd->first_child('a')->href;
            
            // Start buliding the item
            $new_item = array();
  
            if(!empty($name)) {
                 $new_item['name'] = $name;
            }
            if(!empty($link)) {
                 $new_item['link'] = $link;
            }
            if(!empty($description)) {
                $new_item['description'] = $description;
            }
            if(!empty($category)) {
                 $new_item['category'] = $category;
            }
            
            // This helps us get the recently awesome
            $now = time();
            $new_item['last_modified'] = $now;
            $url = $this->get('ELASTICSEARCH_URL');
            
            // Send the item back to ES.
            $jsoned_new_item = json_encode($new_item);
            $ch = curl_init();
            $method = "POST";
    
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsoned_new_item);
    
            $results = curl_exec($ch);
            curl_close($ch);
            
            
            // We should now have the item details stored. Let's index the Awesomed event (hollis_id and timestamp)
            $awesomed_container = array();
            $awesomed_container['link'] = $link;
            $awesomed_container['checked_in'] = $now;
        
            // Send the event to ES.
            $url = $this->get('ELASTICSEARCH_URL_CHECKED_IN');
            $jsoned_awesomed_container = json_encode($awesomed_container);
            $ch = curl_init();
            $method = "POST";
    
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsoned_awesomed_container);
    
            $results = curl_exec($ch);
            curl_close($ch);
          }
        }
    }    

    function create() {
        // An item has been Awesomed (we received a barcode)
        
        // Start buliding the item
        $new_item = array();
        $incoming_name = $this->get('POST.name');
        if(!empty($incoming_name)) {
             $new_item['name'] = $incoming_name;
        }
        $incoming_link = $this->get('POST.link');
        if(!empty($incoming_link)) {
             $new_item['link'] = $incoming_link;
        }
        $incoming_description = $this->get('POST.description');
        if(!empty($incoming_description)) {
            $new_item['description'] = $incoming_description;
        }
        $incoming_category = $this->get('POST.category');
        if(!empty($incoming_category)) {
             $new_item['category'] = $incoming_category;
        }
        
        // This helps us get the recently awesome
        $now = time();
        $new_item['last_modified'] = $now;
        
        $url = $this->get('ELASTICSEARCH_URL');
        
        // Send the item back to ES.
        $jsoned_new_item = json_encode($new_item);
        $ch = curl_init();
        $method = "POST";

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsoned_new_item);

        $results = curl_exec($ch);
        curl_close($ch);
        
        
        // We should now have the item details stored. Let's index the Awesomed event (hollis_id and timestamp)
        $awesomed_container = array();
        $awesomed_container['link'] = $incoming_link;
        $awesomed_container['checked_in'] = $now;
    
        // Send the event to ES.
        $url = $this->get('ELASTICSEARCH_URL_CHECKED_IN');
        $jsoned_awesomed_container = json_encode($awesomed_container);
        $ch = curl_init();
        $method = "POST";

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsoned_awesomed_container);

        $results = curl_exec($ch);
        curl_close($ch);
        
    }
    
    //////////////////////
    // Local heplers
    //////////////////////
    
    // Taken directly from http://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid
    function gen_uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }  
    
    // TODO: This is terribly inefficient. Find a better way to do this
    // Use the hollis id to dedupe
    function dedupe_using_hollis_id($docs) {
        $deduped_docs = array();
        foreach ($docs as $doc) {
            $in_array = False;
            foreach ($deduped_docs as $deduped_doc) {
                if ($deduped_doc['name'] == $doc['name']) {
                    $in_array= True;
                }
            }
            if (!$in_array) {
                $deduped_docs[] = $doc;
            }
        }
        return $deduped_docs;
    }
    
    function get_docs_from_es_response($es_response) {
        // Let's pull our docs out of Elasticsearch response here

        $docs = array();
        if (!empty($es_response)) {
            // Build the response to use our preferred vocab
            foreach ($es_response['hits']['hits'] as $result) {
                $doc = array();
                if (!empty($result['_source']['name'])) {
                    $doc['name'] = $result['_source']['name'];
                }
                if (!empty($result['_source']['link'])) {
                    $doc['link'] = $result['_source']['link'];
                }
                if (!empty($result['_source']['description'])) {
                    $doc['description'] = stripslashes($result['_source']['description']);
                }
                if (!empty($result['_source']['category'])) {
                    $doc['category'] = $result['_source']['category'];
                }
                if (!empty($result['_source']['checked_in'])) {
                    $doc['checked_in'] = $result['_source']['checked_in'];
                }
                if (!empty($result['_source']['id'])) {
                    $doc['id'] = $result['_source']['id'];
                }
                if (!empty($result['_source']['last_modified'])) {
                    $doc['last_modified'] = $result['_source']['last_modified'];
                }
                array_push($docs, $doc);
            }
        }
        
        return $docs;
    }
}
?>
