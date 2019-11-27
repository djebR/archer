<?php 

    //ini_set('max_execution_time', 0); // to get unlimited php script execution time
    /*
        $class: the focus class, from which we want to get resources
        $limit: number of instances from the focused class
    */

    function getInstances($class, $source, $parameters, $limit = 500, $sourceSimilarity = null){
        $format = 'json';
        
        $query = 
                "SELECT distinct ?source1  ".(!is_null($sourceSimilarity)?("?source2"):"")."
                WHERE {
                    ?source1 a ".$class." .
                    ".(!is_null($sourceSimilarity)?("
                    ?source1 <http://www.w3.org/2002/07/owl#sameAs> ?source2 .
                    FILTER (CONTAINS(STR(?source2),'".$sourceSimilarity."'))
                    "):"")."
                }
                LIMIT ".$limit;
    
        $searchUrl = $source ."?". $parameters['query'].'='.urlencode($query);
        if(isset($parameters['format'])) $searchUrl .= '&format='.$parameters['format'];

        return $searchUrl;
    }

    function getCBD($instance, $source, $parameters, $level = 1){
        $query = 
        "SELECT ?predicate ?object
        WHERE {{
            <".urldecode($instance)."> ?predicate ?object .}
             ";
            for ($i=1; $i < $level; $i++) {
                // loop all the levels
                $query .= " UNION {<".urldecode($instance)."> ?p0 ?o0 . ";

                for ($j=0; $j < $i; $j++) { 
                    $query .= "?o{$j} ?p".($j+1)." ?o".($j+1).".";
                }
                $query .= "BIND(?p{$i} as ?predicate).";
                $query .= "BIND(?o{$i} as ?object).";
                $query .= "}";
            }

        $query .= "}";
        
            $searchUrl = $source ."?". $parameters['query'].'='.urlencode($query);
            if(isset($parameters['format'])) $searchUrl .= '&format='.$parameters['format'];
            
        return $searchUrl;
    }

    function getSymCBD($instance, $source, $parameters, $level = 1){
        $query = 
        "SELECT ?predicate ?object
        WHERE {{
            <".urldecode($instance)."> ?predicate ?object .} UNION { ?object ?predicate <".urldecode($instance)."> .}";
        if($level > 1) {
            for ($i=1; $i < $level; $i++) {
                // loop all the levels
                $query .= " UNION {<".urldecode($instance)."> ?p0 ?o0 . ";

                for ($j=0; $j < $i; $j++) { 
                    $query .= "?o{$j} ?p".($j+1)." ?o".($j+1).".";
                }
                $query .= "BIND(?p{$i} as ?predicate).";
                $query .= "BIND(?o{$i} as ?object).";
                $query .= "} UNION {";

                for ($j=$i; $j > 0; $j--) { 
                    $query .= "?o".($j)." ?p".($j)." ?o".($j-1)." .";
                }

                $query .= "?o0 ?p0 <".urldecode($instance).">. ";
                $query .= "BIND(?p{$i} as ?predicate).";
                $query .= "BIND(?o{$i} as ?object).";
                $query .= "}";
            }
        }

        $query .= "}";
            $searchUrl = $source ."?". $parameters['query'].'='.urlencode($query);
            if(isset($parameters['format'])) $searchUrl .= '&format='.$parameters['format'];
            
        return $searchUrl;
    }

    function getCustomCBD($instance, $source, $parameters, $pattern){
        $query = 
        "SELECT ?predicate ?object
        WHERE {".$pattern."}";
        
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
    $instanceURL = getInstances($_REQUEST['class'], $_REQUEST['main'], array('query'=>'query','format'=>'json'), $_REQUEST["limit"], $_REQUEST["similarity"]);
    $instanceArray = json_decode(request($instanceURL), true); 
    var_dump($instanceArray);

    /*
        - Compare the CBDs
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

        .card td, .stats td {
            padding: 0px 0px 0px 10px;
            font-size:small;
        }

        .icn {
            width:20px;
            height:20px;
            margin-right: 10px;
        }
        
        #plotter {
            height:500px;
            border: 1px solid;
            overflow: hidden;
        }

        .plot-container.plotly {
            position:absolute;
        }

        .progress {
            margin-top: 11px;
        }
    </style>

    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
</head>

<body>


    <h1>Archer</h1>

    <div class="row">
        <div class="col-12">
            <form action='mapper.php' method="POST">
                <div class="form-group row">
                    <label for="class" class="col-sm-1 col-form-label">Class</label>
                    <div class="col-sm-5">
                    <input type="text" class="form-control" id="class" name="class" placeholder="Class" value='<?php echo $_REQUEST["class"]; ?>'/>
                    </div>
                    <label for="limit" class="col-sm-1 col-form-label">Limit</label>
                    <div class="col-sm-5">
                    <input type="text" class="form-control" id="limit" name="limit" placeholder="Class" value='<?php echo $_REQUEST["limit"]; ?>'/>
                    </div>
                </div>
                <div class="form-group row">
                    <label for="main" class="col-sm-1 col-form-label">Main source</label>
                    <div class="col-sm-5">
                    <input type="text" class="form-control" id="main" name="main" placeholder="Main source" value='<?php echo $_REQUEST["main"]; ?>'/>
                    </div>
                    <label for="second" class="col-sm-1 col-form-label">Secondary source</label>
                    <div class="col-sm-5">
                    <input type="text" class="form-control" id="second" name="second" placeholder="Class" value='<?php echo $_REQUEST["second"]; ?>'/>
                    </div>
                </div>
                <div class="form-group row">
                    <label for="similarity" class="col-sm-1 col-form-label">similarity string</label>
                    <div class="col-sm-3">
                        <input type="text" class="form-control" id="similarity" name="similarity" placeholder="similarity string" value='<?php echo $_REQUEST["similarity"]; ?>'/>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exampleRadios" id="exampleRadios1" value="option1" checked>
                            <label class="form-check-label" for="exampleRadios1">
                                Level-based CBD
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exampleRadios" id="exampleRadios2" value="option2">
                            <label class="form-check-label" for="exampleRadios2">
                                Symmetric Level-based CBD
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exampleRadios" id="exampleRadios3" value="option3">
                            <label class="form-check-label" for="exampleRadios3">
                                Custom CBD
                            </label>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="numCBD" class="col-sm-2 col-form-label">CBD Level</label>
                            <div class="col-sm-10">
                                <input type="number" name="numCBD" id="numCBD" value="1">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="custom" class="col-sm-2 col-form-label">Custom CBD<br/>(Use ?predicate ?object for your output)</label>
                            <div class="col-sm-10">
                                <textarea class="form-control" id="custom" name='customCBD' rows="3" disabled="disabled"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-sm-2">
                    <button type="submit" class="btn btn-primary">Query</button>
                    </div>
                    <div class="col-sm-8">
                    <div class="progress">
                        <div class="progress-bar" id="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    </div>
                    <div class="col-sm-2">
                        <a href="#" id='analyseAll' class="float-right btn btn-primary">Analyze all</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <p>
        <a class="btn btn-primary dropdown-toggle" data-toggle="collapse" href="#allResults" role="button" aria-expanded="true" aria-controls="allResults">
            Collapse Results
        </a>
    </p>

    <?php
        $files = glob('results/*.json'); // get all file names
        foreach($files as $file){ // iterate files
            if(is_file($file))
                unlink($file); // delete file
        }

 
        // Results start
        echo "<div class='collapse show' id='allResults'>";

        echo "<table class='table table-bordered' id='results'>";
        $counter = array(0 => 0, 1 => 0);
        ob_start();
        ob_implicit_flush(true);
        ob_end_flush();

        echo "<tr>";
        echo "<th scope='col'>N°</th>";
        foreach ($instanceArray["head"]["vars"] as $key => $value) {
            echo "<th scope='col'>".$value."</th>";
        }
        echo "<th scope='col'>Analyze</th></tr>";

        $all = count($instanceArray["results"]["bindings"]);
        $nom = 0;
        foreach ($instanceArray["results"]["bindings"] as $key => $value) {
            $nom++;
            $percent = round($nom*100/$all);
            echo "<script class='deletelater'>document.getElementById('progress-bar').setAttribute('style', 'width:{$percent}% !important;')</script>";
            $i = 0;
            echo "<tr><th scope='row'>".($key+1)."</th>";
            foreach ($value as $key2 => $value2) {
                
                echo "<td><a href='".$value2["value"]."' target='_blank'><img class='icn' src='assets/img/lnk.png'/></a><a data-toggle='collapse'
                 href='#collapseExample".$key.$key2."' role='button' aria-expanded='false' aria-controls='collapseExample".$key.$key2."'>".urldecode($value2["value"])."<span class='triple{$i} badge badge-dark float-right'>";
                $cbdURL = "";
                switch ($_REQUEST['exampleRadios']) {
                    case 'option1':
                        $cbdURL = getCBD($value2["value"], ($i==0)?$_REQUEST['main']:$_REQUEST['second'], array('query'=>'query','format'=>'json'), $_REQUEST['numCBD']);
                        break;
                    case 'option2':
                        $cbdURL = getSymCBD($value2["value"], ($i==0)?$_REQUEST['main']:$_REQUEST['second'], array('query'=>'query','format'=>'json'), $_REQUEST['numCBD']);
                        break;
                    case 'option3':
                        $cbdURL = getCustomCBD($value2["value"], ($i==0)?$_REQUEST['main']:$_REQUEST['second'], array('query'=>'query','format'=>'json'), $_REQUEST['customCBD']);
                        break;
                }

                $cbd = json_decode(request($cbdURL), true); 
                if(is_array($cbd) && count($cbd["results"]["bindings"]) > 0) {
                    $counter[$i] += count($cbd["results"]["bindings"]);
                    echo count($cbd["results"]["bindings"])."</span></a><div class='collapse' id='collapseExample".$key.$key2."'><div class='card card-body'>";
                    var_dump($cbdURL);
                    echo "<table class='table'><tr><th>Predicate</th><th>Object</th></tr>";
                    foreach ($cbd["results"]["bindings"] as $key3 => $value3) {
                        echo "<tr>";
                            echo "<td>".$value3["predicate"]["value"]."</td>";
                            echo "<td>".$value3["object"]["value"]."</td>";
                        echo "</tr>";
                        $cbdAnswer[$key][$i][] = array("subject" => $value2["value"], "predicate" =>$value3["predicate"]["value"], "object" => $value3["object"]["value"]);
                    }
                    echo "</table>";
                    $fp = fopen("results/{$i}_{$key}.json", 'w');
                    fwrite($fp, json_encode($cbdAnswer[$key][$i]));
                    fclose($fp);

                } else {
                    echo "0</span></a><div class='collapse' id='collapseExample".$key.$key2."'><div class='card card-body'>";
                    $fp = fopen("results/{$i}_{$key}.json", 'w');
                    fwrite($fp, json_encode(array()));
                    fclose($fp);

                }
                echo "</div></div></td>";

                $i += 1;
            }

            echo "<td><a class='analysis' href='#' data-key='{$key}' data-toggle='modal' data-target='#exampleModalCenter'>Analyze</a></td></tr>";
        }

        echo "<tr>
                <td>Total</td>
                <td><span id='count0' class='badge badge-dark float-right'>".$counter[0]."</span></td>
                <td><span id='count1' class='badge badge-dark float-right'>".$counter[1]."</span></td>
                <td><span id='count2'></span></td>
            </tr>
            </table>";


        echo "</div>";
        // Results end
    ?>

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

        $('.deletelater').remove();

        $('[name=exampleRadios]').on('change', function(){
            if(this.value == 'option3'){
                $('#custom').prop('disabled', '');
            } else {
                $('#custom').prop('disabled', 'disabled');
            }
        });

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

        $('#analyseAll').on('click', function(){

            var keys = $("a[class=analysis]");
            var count0 = 0;
            var count1 = 0;
            var countLinks = 0;

            var y0 = [];
            var y1 = [];
            var y2 = [];

            $.each(keys, function (indexInArray, valueOfElement) { 
                var that = $(this);
                var key = $(this).data("key");
                var firstTriple = $(this).parent().siblings('td').children('a[role]:first');
                var secondTriple = $(this).parent().siblings('td').children('a[role]:last');

                $.ajax({
                    async: false,
                    type: "get",
                    url: "analyze.php?key=" + key,
                    data: "data",
                    dataType: "json",
                    success: function (response) {
                        firstTriple.html(firstTriple.html() + "<span class='badge badge-danger float-right'>"+ response.nodesFrom + "</span>");
                        secondTriple.html(secondTriple.html() + "<span class='badge badge-danger float-right'>"+ response.nodesTo + "</span>");
                        that.html(that.html() + "<span class='badge badge-success float-right'>"+ response.links + "</span>");
                        count0 += response.nodesFrom;
                        count1 += response.nodesTo;
                        countLinks += response.links;

                        triple0 = Number(that.parent().siblings('td').find('.triple0').text());
                        triple1 = Number(that.parent().siblings('td').find('.triple1').text());
                        y0[key] = (triple0 == 0)?0:(response.nodesFrom/triple0);
                        y1[key] = (triple1 == 0)?0:(response.nodesTo/triple1);
                        y2[key] = (triple0 == 0 || triple1 == 0)?0:Math.max(response.nodesFrom,response.nodesTo)/Math.min(triple0, triple1);
                        /* will be update to use with d3
                        
                        response.possibleLinkedPred.forEach(element => {
                            if(!hash.hasOwnProperty(element)){
                                possibleLinks.push(element);
                                hash[element] = 1;
                            } else {
                                hash[element]++;
                            }
                        });*/
                    }
                });
            });

            $('#count0').parent().html($('#count0').parent().html() + "<span class='badge badge-danger float-right'>"+ count0 + "</span>");
            $('#count1').parent().html($('#count1').parent().html() + "<span class='badge badge-danger float-right'>"+ count1 + "</span>");
            $('#count2').parent().html($('#count2').parent().html() + "<span class='badge badge-success float-right'>"+ countLinks + "</span>");

            $.ajax({
                async: false,
                type: "get",
                url: "analyze.php",
                data: "data",
                dataType: "json",
                success: function (response) {
    
                    var content = "<p><a class='btn btn-primary dropdown-toggle' data-toggle='collapse' href='#analyseResults' role='button' aria-expanded='true' aria-controls='analyseResults'>Collapse Analysis</a></p><div id='analyseResults' class='collapse show'>";
                    content += "<div class='row'><div class='col-sm-6'>" +
                        "Number of Triples issued from MusicBrainz : " + response.totalTriples0 + "<br/>"
                        + "Number of Triples issued from DBpedia : " + response.totalTriples1 + "<br/>"
                        + "Number of (MB) Resources linked with (DBP) : " + response.totalLinkedNodes0 + "<br/>"
                        + "Number of (DBP) Resources linked with (MB) : " + response.totalLinkedNodes1 + "<br/>"
                        + "Number of (MB) Resources with zero links : " + response.zeroResources0 + "<br/>"
                        + "Number of (DBP) Resources with zero links : " + response.zeroResources1 + "<br/>"
                        + "Number of Triples of (MB) Resources with zero links : " + response.zeroResourcesTriples0 + "<br/>"
                        + "Number of Triples of (DBP) Resources with zero links : " + response.zeroResourcesTriples1 + "</div>"
                        + "<div class='col-sm-6' id='plotter'></div></div><table class='table stats'><tr>";

                    for (element in response.LinkedPred){
                        content += '<tr><td>' + response.LinkedPred[element][0] + '</td><td>' + response.LinkedPred[element][1] + '</td><td>' + response.LinkedPred[element][2] + '</td></tr>'
                    };
                    content += "</table></div>";

                    $('body').append(content);

                    var trace1 = {
                    y: y0,
                    type: 'box',
                    name: 'MusicBrainz'
                    };

                    var trace2 = {
                    y: y1,
                    type: 'box',
                    name: 'DBpedia'
                    };

                    var trace3 = {
                    y: y2,
                    type: 'box',
                    name: 'Total'
                    };

                    var data = [trace1, trace2, trace3];

                    Plotly.newPlot('plotter', data);
                }
            });

        });

    </script>
</body>
</html>



