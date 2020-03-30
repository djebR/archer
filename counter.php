<?php
    //header('Content-Type: application/json');

        // is curl installed?
        if (!function_exists('curl_init')){ 
            die('CURL is not installed!');
        }
        ob_start();  
$out = fopen('php://output', 'w');

        // get curl handle
        $ch = curl_init();
        //$urr = $_REQUEST['source']."?format=json&query=".urlencode("SELECT (count(distinct ?s) as ?Counter) WHERE {?s a " . $_REQUEST['class'] . "}");
        $urr = "http://localhost:8890/sparql?format=json&query=SELECT+%28count%28distinct+%3Fs%29+as+%3FCounter%29+WHERE+%7B%3Fs+a+%3Chttp%3A%2F%2Fpurl.org%2Fontology%2Fmo%2FMusicArtist%3E%7D";
curl_setopt($ch, CURLOPT_VERBOSE, true);  
curl_setopt($ch, CURLOPT_STDERR, $out);  
        // set request url
        curl_setopt($ch, 
            CURLOPT_URL, 
            $urr);
        
        // set request url

        //curl_setopt ($ch, CURLOPT_PORT , 8890);        

        // return response, don't print/echo
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        /*
        Here you find more options for curl:
        http://www.php.net/curl_setopt
        */		
        
        $response = json_decode(curl_exec($ch), true);
fclose($out);  
$debug = ob_get_clean();
            echo "<pre>";
        print_r($debug);
        echo "</pre>";
        curl_close($ch);
        
        echo json_encode(array("url"=>$urr, "counter" => $response["results"]["bindings"][0]["Counter"]["value"]));
?>