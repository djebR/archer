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
   "SELECT ?predicate ?object
   WHERE {
      <".$instance."> ?predicate ?object . 
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

$instanceURL = getInstances($_REQUEST['class'], "http://dbtune.org/musicbrainz/sparql", array('query'=>'query','format'=>'json'), $_REQUEST["limit"], "dbpedia");

$instanceArray = json_decode(request($instanceURL), true); 
    
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
    margin: 20px;
}

.card td {
    padding: 0px 0px 0px 10px;
    font-size:small;
}
</style>

</head>

<body>
    <h1>Archer</h1>

    <div class="row">
        <div class="col-8">
            <h4>Class name:</h3>
            <input name="class" type="text" style="width:100%;" value="<?php echo $_REQUEST["class"]; ?>">
        </div>
        <div class="col-4">
            <h4>Number of resources:</h3>
            <input name="limit" type="text" style="width:100%;" value="<?php echo $_REQUEST["limit"]; ?>">
        </div>
    </div>
    <br/>

    <h3>Resources: </h3>
    <table class="table table-bordered">
    <?php
        ob_start();
ob_implicit_flush(true);
ob_end_flush();

        echo "<tr>";
        echo "<th scope='col'>N°</th>";
        foreach ($instanceArray["head"]["vars"] as $key => $value) {
            echo "<th scope='col'>".$value."</th>";
        }
        echo "<th scope='col'>Analyze</th></tr>";
        foreach ($instanceArray["results"]["bindings"] as $key => $value) {
            $i = 0;
            echo "<tr><th scope='row'>".($key+1)."</th>";
            foreach ($value as $key2 => $value2) {
                
                echo "<td><a data-toggle='collapse'
                 href='#collapseExample".$key.$key2."' role='button' aria-expanded='false' aria-controls='collapseExample".$key.$key2."'>".$value2["value"]."<span class='badge badge-dark float-right'>";
                
                $cbdURL = getCBD($value2["value"], ($i==0)?"http://dbtune.org/musicbrainz/sparql":"http://dbpedia.org/sparql", array('query'=>'query','format'=>'json'));
                $cbd = json_decode(request($cbdURL), true); 
                echo count($cbd["results"]["bindings"])."</span></a><div class='collapse' id='collapseExample".$key.$key2."'><div class='card card-body'>";
                echo "<table class='table'><tr><th>Predicate</th><th>Object</th></tr>";
                foreach ($cbd["results"]["bindings"] as $key3 => $value3) {
                    echo "<tr>";
                        echo "<td>".$value3["predicate"]["value"]."</td>";
                        echo "<td>".$value3["object"]["value"]."</td>";
                    echo "</tr>";
                    $cbdAnswer[$key][$i][] = array("subject" => $value2["value"], "predicate" =>$value3["predicate"]["value"], "object" => $value3["object"]["value"]);
                }
                echo "</table>";
                echo "</div></div></td>";

                $fp = fopen("results/{$i}_{$key}.json", 'w');
                fwrite($fp, json_encode($cbdAnswer[$key][$i]));
                fclose($fp);
                $i += 1;
            }

            echo "<td><a class='analysis' href='#' data-key='{$key}' data-toggle='modal' data-target='#exampleModalCenter'>Analyze</a></td></tr>";
        }

    ?>
    </table>

    <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalCenterTitle">Analyzing </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

    <script>

        $('.analysis').on('click', function(){
            var that = $(this);
            var key = $(this).data("key");
            var firstTriple = $(this).parent().siblings('td').children('a:first');
            var secondTriple = $(this).parent().siblings('td').children('a:last');

            $.ajax({
                type: "get",
                url: "analyze.php?key=" + key,
                data: "data",
                dataType: "json",
                success: function (response) {
                    firstTriple.html(firstTriple.html() + "<span class='badge badge-danger float-right'>"+ response.nodesFrom + "</span>");
                    secondTriple.html(secondTriple.html() + "<span class='badge badge-danger float-right'>"+ response.nodesTo + "</span>");
                    that.html(that.html() + "<span class='badge badge-success float-right'>"+ response.links + "</span>")
                }
            });


        });

    </script>
</body>
</html>



