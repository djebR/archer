<?php 
/*
    $class: the focus class, from which we want to get resources
    $limit: number of instances from the focused class
*/

function getInstances($class, $source, $parameters, $limit = 500, $sourceSimilarity = null){
   $format = 'json';
 
   $query = 
        "SELECT distinct ?instance  ".(!is_null($sourceSimilarity)?("?instance2"):"")."
        WHERE {
            ?instance a ".$class." .
            ".(!is_null($sourceSimilarity)?("
            ?instance <http://www.w3.org/2002/07/owl#sameAs> ?instance2 .
            FILTER (CONTAINS(STR(?instance2),'".$sourceSimilarity."'))
            "):"")."
        }
        LIMIT ".$limit;
 
    $searchUrl = $source ."?". $parameters['query'].'='.urlencode($query);
    if(isset($parameters['format'])) $searchUrl .= '&format='.$parameters['format'];

    return $searchUrl;
}

function getCBD($instance, $source, $parameters){
 
   $query = 
   "SELECT ?p ?o
   WHERE {
      <".$instance."> ?p ?o . 
   }";
 
    $searchUrl = $source ."?". $parameters['query'].'='.urlencode($query);
    if(isset($parameters['format'])) $searchUrl .= '&format='.$parameters['format'];
	  
   return $searchUrl;
}

function request($url){
 
   // is curl installed?
   if (!function_exists('curl_init')){ 
      die('CURL is not installed!');
   }
 
   // get curl handle
   $ch= curl_init();
 
   // set request url
   curl_setopt($ch, 
      CURLOPT_URL, 
      $url);
 
   // return response, don't print/echo
   curl_setopt($ch, 
      CURLOPT_RETURNTRANSFER, 
      true);
 
   /*
   Here you find more options for curl:
   http://www.php.net/curl_setopt
   */		
 
   $response = curl_exec($ch);
 
   curl_close($ch);
 
   return $response;
}


$cbdAnswer = array();

$instanceURL = getInstances($_REQUEST['class'], "http://dbtune.org/musicbrainz/sparql", array('query'=>'query','format'=>'json'), 5, "dbpedia");

$responseArray = json_decode(request($instanceURL), true); 
    
/*
    - Get instances from a class, with limits
    - Get the CBD for every instance
    - Compare the CBDs, 
    - extract metrics,
    - use the extracted metrics to query for conjunctions; (Example: get a website from the other source, it will be annotated with the same value that propagated through the link)

*/
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>

<title>Archer</title>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<style>
body {
    margin: 10px;
}

</style>

</head>

<body>
    <h1>Archer</h1>

    <h3>Request URL:</h3>
    <div><input name="url" type="text" style="width:100%;" value="<?php echo $instanceURL ?>"> </div>
    <br/>

    <h3>Abstract: </h3>
    <table class="table">
    <?php
        ob_start();
ob_implicit_flush(true);
ob_end_flush();

        echo "<tr>";
        echo "<th scope='col'>N°</th>";
        foreach ($responseArray["head"]["vars"] as $key => $value) {
            echo "<th scope='col'>".$value."</th>";
        }
        echo "<th scope='col'>Analyze</th></tr>";
        foreach ($responseArray["results"]["bindings"] as $key => $value) {
            $i = 0;
            echo "<tr><th scope='row'>".($key+1)."</th>";
            foreach ($value as $key2 => $value2) {
                
                echo "<td><a data-toggle='collapse'
                 href='#collapseExample".$key.$key2."' role='button' aria-expanded='false' aria-controls='collapseExample".$key.$key2."'>".$value2["value"]."</a>
                 <div class='collapse' id='collapseExample".$key.$key2."'>
                    <div class='card card-body'>";
                
                $cbdURL = getCBD($value2["value"], ($i==0)?"http://dbtune.org/musicbrainz/sparql":"http://dbpedia.org/sparql", array('query'=>'query','format'=>'json'));
                $cbdAnswer[$key][$value2["value"]] = json_decode(request($cbdURL), true); 
                echo "<table class='table'><tr><th>Predicate</th><th>Object</th></tr>";
                foreach ($cbdAnswer[$key][$value2["value"]]["results"]["bindings"] as $key3 => $value3) {
                    echo "<tr>";
                    foreach ($value3 as $key4 => $value4) {
                        echo "<td>".$value4["value"]."</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
                echo "</div></div></td>";
                $i += 1;
            }

            echo "<td>Analyze</td></tr>";
        }



        $fp = fopen('results.json', 'w');
        fwrite($fp, json_encode($cbdAnswer));
        fclose($fp);
    ?>
    </table>


    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

</body>
</html>



