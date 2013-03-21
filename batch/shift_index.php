<?php

/*******************
 * Some utility functions to aid in the export/import/backup of ElasticSearch types
 *
 * These can be helpful when adjusting a type's mapping (schema)
 ******************/

$es_type_path = 'hlsl7.law.harvard.edu:9200/law-apps-matt/item/';
$backup_file_path = 'es_backup.json';

function backup_es_type($es_type_path, $backup_file_path) {
    // Get all ES docs from the passed-in index, write them to disk
    // If the colleciton gets bigger, we should probably use the
    //  ES scan functionality
    
    // Read 500 docs in
    $curl = curl_init($es_type_path . "_search?size=500");
    curl_setopt($curl, CURLOPT_FAILONERROR, true); 
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); 
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); 
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);   
    $response = curl_exec($curl); 

    // We received results. Let's write them to disk as a backup.
    file_put_contents($backup_file_path, $response);
}

function delete_es_type($es_type_path) {
    // Delete an ES type
    
    $curl = curl_init("$es_type_path"); 
    curl_setopt($curl, CURLOPT_FAILONERROR, true); 
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); 
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);   
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");

    $response = curl_exec($curl);
    echo $response;
}

function reindex_es_type($es_type_path, $backup_file_path) {
    // Loop through all of the original data (in the _source field, in the docs 
    // in the file) and send back to ES

    $file = file_get_contents('./'. $backup_file_path, true);

    $jsoned_response = json_decode($file);

    foreach ( $jsoned_response->hits->hits as $hit ) {
        //print_r($hit->_source);
    
        $curl = curl_init("$es_type_path"); 
        curl_setopt($curl, CURLOPT_FAILONERROR, true); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);   
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($hit->_source));
        $response = curl_exec($curl);
    }

}

//backup_es_type($es_type_path, $backup_file_path);
//delete_es_type($es_type_path);


// create new index. (using curl on the command line)
// probably looks something like:
/*curl -XPUT 'http://hlsl7.law.harvard.edu:9200/law-apps-matt/' -d '{
   "settings" : {
       "index" : {
           "number_of_shards" : 1,
           "number_of_replicas" : 2
       }
   },
   "mappings" : {
       "item" : {
           "properties" : {
               "category" : {
                   "type" : "multi_field",
                   "fields" : {
                       "category" : {"type" : "string", "index" : "analyzed"},
                       "category_raw" : {"type" : "string", "index" : "not_analyzed"}
                   }
               }
           }
       }
   }
}'


$ curl -XPUT 'http://hlsl7.law.harvard.edu:9200/law-apps-matt/item/_mapping' -d '
{
    "item" : {
           "properties" : {
               "category" : {
                   "type" : "multi_field",
                   "fields" : {
                       "category" : {"type" : "string", "index" : "analyzed"},
                       "category_raw" : {"type" : "string", "index" : "not_analyzed"}
                   }
               }
           }
       }
}
'*/


//reindex_es_type($es_type_path, $backup_file_path);

?>