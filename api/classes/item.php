<?php

class Item extends F3instance {

    function search() {
        // Do some searching on things coming in from the filter URL param

        /* Start building the query object. If we have filters, we do something like:
        $reqeust =  '{
            "query" : {
                "filtered" : {
                    "query" : {"match_all" : {}},
                    "filter" : {
                        "and" : [
                            {
                                "term" : {
                                    "category" : "legal"
                                }
                            },
                                                {
                                "term" : {
                                    "category" : "academic"
                                }
                            }
                        ]
                    } 
                }
            }
        }'
        
        
        If we don't have filters, we want to build a request like this:
        
        $reqeust =  '{
            "from": 0,
            "size": 25,
            "query": {
                "match_all": {}
            },
            "facets": {
                "category": {
                    "terms": {
                        "term": {
                            "field": "category"
                        }
                    }
                }
            }
        }'
        
        */

        // This is the object we build to send off to elasticsearch
        $request = array();

        // Set some defaults for our controls
        $request['from'] = 0;
        $request['size'] = 25;
        

        // If have filters, our request to elasticserach looks substantially different (than one without filters)
        // Build that request here
        $filters = $this->get('GET.filter');
        $filter_structure = array();

        if (!empty($filters)) {
            $request['query']['filtered']['query'] = array("match_all" => new stdClass);
            foreach ($filters as $filter) {
                $key_and_val = explode(":", $filter);
                if (count($key_and_val) == 2 and !empty($key_and_val[0]) and !empty($key_and_val[1])) {
                    array_push($filter_structure, array("term" => array($key_and_val[0] => $key_and_val[1])));
                }  
            }
            
            $request['query']['filtered']['filter']['and'] = $filter_structure;
            $request['facets']['category']['terms'] = array('field' => 'category');
            $request['facets']['category']['facet_filter'] = $filter_structure;
        } else {
            $request['query']['match_all'] = new stdClass;
            $request['facets']['category']['terms'] = array("term" => array("field" => "category"));
        }

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
        //print $jsoned_request;
        $url = $this->get('ELASTICSEARCH_URL') . '_search';
        $ch = curl_init();
        $method = "GET";

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsoned_request);

        $results = curl_exec($ch);
        curl_close($ch);

        $decoded_results = json_decode($results, True);

        // We should have a response. Let's pull the docs out of it
        $cleaned_results = $this->get_docs_from_es_response($decoded_results);
        
        // callback for jsonp requests
        $incoming_callback = $this->get('GET.callback');
        if (!empty($incoming_callback)) {
            $this->set('callback', $this->get('GET.callback'));
        }
        
        $facets = $this->get_facets_from_es_response($decoded_results);

        // Set our facets in our view        
        if (!empty($facets)) {
            $this->set('facets', $facets);
        }
        
        $this->set('results', $cleaned_results);

        $this->set('num_found', $decoded_results['hits']['total']);

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
            $category = $page;
            
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
            
            if(!empty($link) && !empty($name)) {
            
              $slug = trim(strtolower(stripslashes($link)));
              $slug = str_replace("'", "", $slug);
              $slug = str_replace(":", "", $slug);
              $slug = str_replace("/", "", $slug);
              $slug = preg_replace("/[^A-Za-z0-9]/", "", $slug);
              $new_item['slug'] = $slug;
              
              // This helps us get the recently awesome
              $now = time();
              $new_item['last_modified'] = $now;
              
              $request = array();
              $request['query']['query_string']['fields'] = array('slug');
              $request['query']['query_string']['query'] = $slug;
              $request['query']['query_string']['default_operator'] = 'AND';
                  
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
              
              $docs = json_decode($results, True);
      
      
              $url = $this->get('ELASTICSEARCH_URL');
      
              // See if we already have it. If we do, just bump its awesomed (the counter field)
              if ($docs['hits']['total'] > 0) {           
                  $current_count = $docs['hits']['hits'][0]['_source']['clicks'];
                  $new_item['clicks'] = $current_count;
                  
                  $url = $url . '/' . $docs['hits']['hits'][0]['_id'];           
              } else {
                  // It's not in ES. We need to add it.
                 $new_item['clicks'] = 1;   
              }
           }
           else {
            $new_item['clicks'] = 1;
          }
            
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
          $awesomed_container['slug'] = $slug;
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
    
    function populate() {
    
        $url = $this->get('ELASTICSEARCH_URL') . '_search';
        $ch = curl_init();
        $method = "GET";
        
        $request['size'] = 550;
        
        $jsoned_request = json_encode($request);
      
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsoned_request);
      
        $results = curl_exec($ch);
        curl_close($ch);     
        $docs = json_decode($results, True);
        
        // Store snapshot to compare to new data
        $snapshot = array();
        foreach($docs['hits']['hits'] as $entry){
          array_push($snapshot, $entry['_source']['slug']);
        }

        include('simple_html_dom.php');
        $scraped = array();
        
        $pages = array('legal','academic','historical','newspapers','portals','business','census','economic','government','international-relations','justice','labor');

        foreach($pages as $page) {
        
          $html = file_get_html("http://law.harvard.edu/library/research/databases/$page.html");
    
          // find all link
          foreach($html->find('dt') as $dt) { 
            $name = addslashes($dt->plaintext);
            $dd = $dt->next_sibling('dd');
            $description = addslashes($dd->innertext);
            $link = $dd->first_child('a')->href;
            $category = array($page);
            
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
            
            if(!empty($link) && !empty($name)) {
            
              $slug = trim(strtolower(stripslashes($link)));
              $slug = str_replace("'", "", $slug);
              $slug = str_replace(":", "", $slug);
              $slug = str_replace("/", "", $slug);
              $slug = preg_replace("/[^A-Za-z0-9]/", "", $slug);
              $new_item['slug'] = $slug;
              array_push($scraped, $slug);
              
              // This helps us get the recently awesome
              $now = time();
              $new_item['last_modified'] = $now;
              
              $request = array();
              $request['query']['query_string']['fields'] = array('slug');
              $request['query']['query_string']['query'] = $slug;
              $request['query']['query_string']['default_operator'] = 'AND';
                  
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
              
              $docs = json_decode($results, True);
      
      
              $url = $this->get('ELASTICSEARCH_URL');
      
              // See if we already have it. If we do, just bump its awesomed (the counter field)
              if ($docs['hits']['total'] > 0) {           
                  $current_count = $docs['hits']['hits'][0]['_source']['clicks'];
                  $new_item['clicks'] = $current_count;
                  $existing_categories = $docs['hits']['hits'][0]['_source']['category'];
                  $combined_categories = array_merge($existing_categories, $new_item['category']);
                  $new_item['category'] =  $combined_categories;
                  
                  $url = $url . '/' . $docs['hits']['hits'][0]['_id'];           
              } else {
                  // It's not in ES. We need to add it.
                 $new_item['clicks'] = 1;   
              }
           }
           else {
            $new_item['clicks'] = 1;
          }
            
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
          $awesomed_container['slug'] = $slug;
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
      
      $result = array_diff($snapshot, $scraped);

      foreach($result as $key => $value){
        echo "<p>$value</p>";
        $url = $this->get('ELASTICSEARCH_URL') . "_query?q=slug:$value";
        $ch = curl_init();
        $method = "DELETE";
    
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        $results = curl_exec($ch);
        curl_close($ch);    
      }
    }    
    
    function click() { 
      $link = $this->get('POST.link');
      
      if(!empty($link)) {
            
              $slug = trim(strtolower(stripslashes($link)));
              $slug = str_replace("'", "", $slug);
              $slug = str_replace(":", "", $slug);
              $slug = str_replace("/", "", $slug);
              $slug = preg_replace("/[^A-Za-z0-9]/", "", $slug);
              $new_item['slug'] = $slug;
              
              $request = array();
              $request['query']['query_string']['fields'] = array('slug');
              $request['query']['query_string']['query'] = $slug;
              $request['query']['query_string']['default_operator'] = 'AND';
                  
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
              
              $docs = json_decode($results, True);
      
      
              $url = $this->get('ELASTICSEARCH_URL');
      
              // See if we already have it. If we do, just bump its awesomed (the counter field)
              if ($docs['hits']['total'] > 0 && !empty($docs['hits']['hits'][0]['_source']['name'])) {       
                  $current_count = $docs['hits']['hits'][0]['_source']['clicks'];
                  $new_item = $docs['hits']['hits'][0]['_source'];
                  $new_item['clicks'] = $current_count + 1;
                  
                  $url = $url . '/' . $docs['hits']['hits'][0]['_id'];    
                  
                  $jsoned_new_item = json_encode($new_item);
                  $ch = curl_init();
                  $method = "POST";
    
                  curl_setopt($ch, CURLOPT_URL, $url);
                  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
                  curl_setopt($ch, CURLOPT_POSTFIELDS, $jsoned_new_item);
                
                  $results = curl_exec($ch);
                  curl_close($ch); 
              } 
           }  
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
                if (!empty($result['_source']['slug'])) {
                    $doc['slug'] = $result['_source']['slug'];
                }
                
                if (!empty($result['_source']['description'])) {
                    $doc['description'] = stripslashes($result['_source']['description']);
                }
                if (!empty($result['_source']['category'])) {
                    $doc['category'] = $result['_source']['category'];
                }
                if (!empty($result['_source']['clicks'])) {
                    $doc['clicks'] = $result['_source']['clicks'];
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
    
    function get_facets_from_es_response($es_response) { 
        // Let's pull our docs out of Elasticsearch response here
        $facets = array();
        if (!empty($es_response) && !empty($es_response['facets'])) {
            $facets = $es_response['facets'];
        }
        
        return $facets;
    }
}
?>
