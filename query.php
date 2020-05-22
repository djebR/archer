<?php

if (isset($_REQUEST['qq'])) {

    header('Content-Type: application/json');
    ini_set('max_execution_time', 0); // to get unlimited php script execution time
    /*
            Helper functions
        */

    function getInstances($class, $source, $parameters, $limit = 500, $sourceSimilarity = null)
    {

        $query =
            "SELECT distinct ?source1  " . (!is_null($sourceSimilarity) ? ("?source2") : "") . "
                    WHERE {
                        ?source1 a " . $class . " .
                        " . (!is_null($sourceSimilarity) ? ("
                        ?source1 <http://www.w3.org/2002/07/owl#sameAs> ?source2 .
                        FILTER (CONTAINS(STR(?source2),'" . $sourceSimilarity . "'))
                        ") : "") . "
                    }
                    LIMIT " . $limit;

        $searchUrl = $source . "?" . $parameters['query'] . '=' . urlencode($query);
        if (isset($parameters['format'])) $searchUrl .= '&format=' . $parameters['format'];
        else $searchUrl .= '&format=json';

        return $searchUrl;
    }

    // Change the CBD according to definition
    // We limit the number of triples to 300 so that it doesn't explode
    function getCBD($instance, $source, $parameters, $level = 1, $limit = 300, $symmetric = false, $withBlanks = false)
    {

        // -- QUERY START
        // Clean Comments
        $query = str_replace("%SUBJECT%", urldecode($instance), file_get_contents("queries/cbd.rq"));
        $query = str_replace("%LIMITED%", $limit, $query);
        // -- QUERY END

        // Create the URL to query
        $searchUrl = $source . "?" . $parameters['query'] . '=' . urlencode($query) . '&format=json';

        return $searchUrl;
    }

    function getSymCBD($instance, $source, $parameters, $level = 1)
    {
        $query =
            "SELECT ?predicate ?object
            WHERE {{
                <" . urldecode($instance) . "> ?predicate ?object .} UNION { ?object ?predicate <" . urldecode($instance) . "> .}";
        if ($level > 1) {
            for ($i = 1; $i < $level; $i++) {
                // loop all the levels
                $query .= " UNION {<" . urldecode($instance) . "> ?p0 ?o0 . ";

                for ($j = 0; $j < $i; $j++) {
                    $query .= "?o{$j} ?p" . ($j + 1) . " ?o" . ($j + 1) . ".";
                }
                $query .= "BIND(?p{$i} as ?predicate).";
                $query .= "BIND(?o{$i} as ?object).";
                $query .= "} UNION {";

                for ($j = $i; $j > 0; $j--) {
                    $query .= "?o" . ($j) . " ?p" . ($j) . " ?o" . ($j - 1) . " .";
                }

                $query .= "?o0 ?p0 <" . urldecode($instance) . ">. ";
                $query .= "BIND(?p{$i} as ?predicate).";
                $query .= "BIND(?o{$i} as ?object).";
                $query .= "}";
            }
        }

        $query .= "}";
        $searchUrl = $source . "?" . $parameters['query'] . '=' . urlencode($query);
        if (isset($parameters['format'])) $searchUrl .= '&format=' . $parameters['format'];

        return $searchUrl;
    }

    function getCustomCBD($instance, $source, $parameters, $pattern)
    {
        $query =
            "SELECT ?predicate ?object
            WHERE {" . $pattern . "}";

        $searchUrl = $source . "?" . $parameters['query'] . '=' . urlencode($query);
        if (isset($parameters['format'])) $searchUrl .= '&format=' . $parameters['format'];

        return $searchUrl;
    }

    function request($url)
    {

        // is curl installed?
        if (!function_exists('curl_init')) {
            die('CURL is not installed!');
        }

        // get curl handle
        $ch = curl_init();

        // set request url
        curl_setopt(
            $ch,
            CURLOPT_URL,
            $url
        );

        // return response, don't print/echo
        curl_setopt(
            $ch,
            CURLOPT_RETURNTRANSFER,
            true
        );

        /*
            Here you find more options for curl:
            http://www.php.net/curl_setopt
            */

        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }

    $cbdAnswer = array();
    $indices = array();
    $instanceURL = getInstances("<" . $_REQUEST['class'] . ">", $_REQUEST['main'], array('query' => 'query', 'format' => 'json'), $_REQUEST["limit"], $_REQUEST["similarity"]);
    $folder = "results/" . md5($instanceURL);

    // Todo: check for instance count before creating the folder to avoid duplicate result from possible non completely satisfied queries
    // Example: query for 1000 entries while only 100 exists, so one folder should be created for the 100 entities

    if (!file_exists($folder)) {
        mkdir($folder);
        $instanceArray = json_decode(request($instanceURL), true);
        $instanceCount = count($instanceArray["results"]["bindings"]);

        // Results start
        $cbdURL = "";
        $fold = fopen($folder . ".json", 'w');

        foreach ($instanceArray["results"]["bindings"] as $key => $value) {
            $i = 0;
            foreach ($value as $key2 => $value2) {
                $cbdURL = "";
                $cbdURL = getCBD($value2["value"], ($i == 0) ? $_REQUEST['main'] : $_REQUEST['second'], array('query' => 'query'));

                $cbd = json_decode(request($cbdURL), true);
                $fp = fopen($folder . "/{$i}_{$key}.json", 'w');
                if (is_array($cbd) && count($cbd["results"]["bindings"]) > 0) {
                    foreach ($cbd["results"]["bindings"] as $key3 => $value3) {
                        $cbdAnswer[$key][$i][] = array("subject" => $value2["value"], "predicate" => $value3["predicate"]["value"], "object" => $value3["object"]["value"], "objectMeta" => array('type' => $value3["object"]["type"], 'datatype' => isset($value3["object"]["datatype"]) ? $value3["object"]["datatype"] : ""));
                    }
                    fwrite($fp, json_encode($cbdAnswer[$key][$i]));
                } else {
                    fwrite($fp, json_encode(array()));
                }
                fclose($fp);
                $i += 1;
            }

            $indices[$key] = array($value['source1']['value'], $value['source2']['value']);
        }
        fwrite($fold, json_encode($indices));
        fclose($fold);
    }
    echo json_encode(array('result' => 1, 'folder' => md5($instanceURL)));
    die();
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>

    <title>Archer: Query</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <style>
        body {
            margin: 20px;
        }

        .col-sm-offset-3 {
            margin-left: 25%;
        }

        .card td,
        .stats td {
            padding: 0px 0px 0px 10px;
            font-size: small;
        }

        .icn {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }

        #plotter {
            height: 500px;
            border: 1px solid;
            overflow: hidden;
        }

        .plot-container.plotly {
            position: absolute;
        }

        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            padding: 20px;
            display: block;
            overflow-x: hidden;
            overflow-y: auto;
            background-color: #2f3850;
            color: white;
        }
    </style>

    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <div class="sidebar col-sm-3 hidden-xs">
                <h1>Archer</h1>
                <p>Step 1: Query for instances</p>
                </ul>
                <ul id="list"></ul>
            </div>
            <div class="main col-sm-9 col-sm-offset-3">
                <h2>Select your linkset</h2>
                <p>You can query for a set of identity links and/or provide your own identity links.</p>
                <form id='myForm'>
                    <input type="hidden" name="qq" />
                    <div class="row">
                        <div class="col-6">
                            <div class="card">
                                <div class="card-header">
                                    Query for your linklist
                                </div>
                                <div class="card-body">
                                    <div class="form-group row">
                                        <label for="class" class="col-sm-3 col-form-label">Class <span class="badge badge-info" data-toggle="tooltip" data-placement="right" title="Enter a class which instances have identity links in your target dataset">?</span>
                                        </label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="class" name="class" placeholder="Class (ex: http://purl.org/ontology/mo/MusicArtist)" value='<?php echo (!isset($_REQUEST["class"])) ? "" : $_REQUEST["class"]; ?>' />
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="limit" class="col-sm-3 col-form-label">Limit <span class="badge badge-info" data-toggle="tooltip" data-placement="right" title="Enter the max number of instances you want to query for identity links">?</span></label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="limit" name="limit" placeholder="Max number of instances" value='<?php echo (!isset($_REQUEST["limit"])) ? "" : $_REQUEST["limit"]; ?>' />
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="linkpreds" class="col-sm-3 col-form-label">Predicates <span class="badge badge-info" data-toggle="tooltip" data-placement="right" title="The predicates to consider for identity links (owl:sameAs by default, separate your predicates with a comma)">?</span></label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="linkpreds" name="linkpreds" placeholder="Identity predicates (owl:sameAs by default)" value='<?php echo (!isset($_REQUEST["linkpreds"])) ? "owl:sameAs" : $_REQUEST["linkpreds"]; ?>' />
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="similarity" class="col-sm-3 col-form-label">Object pattern <span class="badge badge-info" data-toggle="tooltip" data-placement="right" data-html="true" title="Enter a part of the objects of identity links (leave blank to use all matches, or specify a part of the link to use as substring match)<br>ex: http://dbpedia.org extracts links of english dbpedia, while http://fr.dbpedia gives links with the french chapter.">?</span></label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="similarity" name="similarity" placeholder="Link pattern in your target dataset" value='<?php echo (!isset($_REQUEST["similarity"])) ? "" : $_REQUEST["similarity"]; ?>' />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card">
                                <div class="card-header">
                                    Or provide your custom linklist (N3 format)
                                    <button class="btn btn-primary btn-sm float-right">Import</button>
                                </div>
                                <div class="card-body">
                                    <textarea class="form-control" aria-label="Custom linklist" rows="9"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="row mt-3">
                        <div class="col-6">
                            <div class="form-group row">
                                <label for="main" class="col-sm-3 col-form-label">Target dataset <span class="badge badge-info" data-toggle="tooltip" data-placement="right" title="(IRI) the SPARQL endpoint for your target dataset (the one you want to annotate with uncertainty)">?</span></label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" id="main" name="main" placeholder="SPARQL endpoint for target dataset" value='<?php echo (!isset($_REQUEST["main"])) ? "" : $_REQUEST["main"]; ?>' />
                                </div>

                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group row">
                                <label for="second" class="col-sm-3 col-form-label">Reference dataset <span class="badge badge-info" data-toggle="tooltip" data-placement="right" title="(IRI) the SPARQL endpoint for your reference dataset (you use as a reference)">?</span></label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" id="second" name="second" placeholder="SPARQL endpoint for reference dataset" value='<?php echo (!isset($_REQUEST["second"])) ? "" : $_REQUEST["second"]; ?>' />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="row mt-3">
                        <div class="col-sm-2">
                            <button type="submit" class="btn btn-primary">Query
                                <span class="spnn spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none;"></span>
                                <span class="spnn sr-only" style="display:none;">Loading...</span>
                            </button>
                        </div>
                        <div class="col-sm-8">
                            <div class="clearfix"></div>
                        </div>
                        <div class="col-sm-2">
                            <a href="#" id='analyseAll' class="float-right btn btn-success" style="display:none;">Analyze</a>
                        </div>

                    </div>
                </form>

                <div class="row mt-3">
                    <div class="col-12">
                        <h4>Previous queries</h4>
                        <ul><?php
                            $fileList = glob("results/*.json");
                            $fileCount = count($fileList);
                            if($fileCount){
                                foreach ($fileList as $key => $filePath) {
                                    // read files into json objects
                                    //$s = json_decode(file_get_contents($file));
                                    //$meta = $s['meta'];
                                    //$linkCount = $meta['linkCount'];
                                    $filename = pathinfo($filePath)['filename'];
                                    $linkCount = 0;

                                    echo "<li><a href='mapper.php?folder={$filename}'>" . $filename . "</a><span class='badge badge-primary float-right'>{$linkCount}</span></li>";
                                }
                            } else {
                                echo "<li>No previous queries to select from.</li>";
                            }
                        ?></ul>
                        
                    </div>
                </div>
            </div>
        </div>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js" crossorigin="anonymous">
        </script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

        <script>
            $(function() {
                $('[data-toggle="tooltip"]').tooltip()
            })

            $('#myForm').on('submit', function(event) {
                event.preventDefault();

                var that = $(this);
                $('.spnn').show();
                $.ajax({
                    type: "post",
                    url: "query.php",
                    data: that.serialize(),
                    dataType: "json",
                    success: function(response) {
                        $('#analyseAll').attr('href', 'mapper.php?folder=' + response.folder);
                        $('#analyseAll').show();
                        $('.spnn').hide();
                    }
                });

            });
        </script>
</body>

</html>