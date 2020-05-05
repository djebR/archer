<?php
    include('assets/jaro.php');
    session_start();
    
    function setProgress($value, $comment){
        $_SESSION['progress'] = array('percent' => $value, 'comment' => $comment);
    }

    // --------------- Commands for debug purpose only ---------------
    ini_set('xdebug.var_display_max_depth', '10');
    ini_set('xdebug.var_display_max_children', '256');
    ini_set('xdebug.var_display_max_data', '90000');
    // ----------------------------------------------------------------


    // --------------- Global variables & Config ---------------
    
    // Weights
        // w_type [sym_o]: to favor expressive datatype similarity (xsd:datetime, xsd:string, uri, etc.)
        // w_val  [sym_o]: to favor value matching (string matching)
        // w_local [sym_p]: to favor link existance ratio between two predicates
        // w_sem [sym_p]: to favor semantic similarity score between two predicates
    $Weights = array('w_type' => 0.0, 'w_val' => 1.0, 'w_local' => 0.5, 'w_sem' => 0.5);
    $semanticWeights = array(
                        'sameURI' => 0.2,
                        'MU1_G' => 0.0,
                        'MU1_E' => 0.0,
                        'MU1_L' => 0.5,
                        'MU2_E' => 0.0,
                        'MU2_L' => 0.0,
                        'MU3_E' => 0.0,
                        'MU3_L' => 0.0,
                        'MU4_E' => 0.0,
                        'MU4_L' => 0.2,
                        'MU4_G' => 0.0,
                        'MU5_E' => 0.0,
                        'MU5_L' => 0.1,
                        'MU5_G' => 0.0);

    // Thresholds
    $tau_o      = (isset($_REQUEST['tauO']))?$_REQUEST['tauO']:0.5;
    $tau_avg    = (isset($_REQUEST['tauAvg']))?$_REQUEST['tauAvg']:0.5;

    // General configuration
    $objectSymMethod = (isset($_REQUEST['method']))?$_REQUEST['method']:"default";


    // --------------- Helper functions ---------------

    // prefixed: give the prefixed version of a uri,
    // example: for "http://www.w3.org/1999/02/22-rdf-syntax-ns#type" returns "rdf:type"
    function prefixed($uri){
        $prefixDB = array(
            "http://www.w3.org/1999/02/22-rdf-syntax-ns#"       => "rdf",
            "http://xmlns.com/foaf/0.1/"                        => "foaf",
            "http://yago-knowledge.org/resource/"               => "yago",
            "http://www.w3.org/2000/01/rdf-schema#"             => "rdfs",
            "http://dbpedia.org/ontology/"                      => "dbo",
            "http://dbpedia.org/property/"                      => "dbp",
            "http://dbpedia.org/ontology/Person/"               => "dbo-person",
            "http://dbpedia.org/ontology/Work/"                 => "dbo-work",
            "http://fr.dbpedia.org/property/"                   => "frdbp",
            "http://purl.org/goodrelations/v1#"                 => "gr",
            "http://purl.org/dc/elements/1.1/"                  => "dc",
            "http://purl.org/linguistics/gold/"                  => "gold",
            "http://www.w3.org/2002/07/owl#"                    => "owl",
            "http://data.ordnancesurvey.co.uk/ontology/spatialrelations/"                       => "spacerel",
            "http://www.w3.org/2004/02/skos/core#"              => "skos",
            "http://www.opengis.net/ont/geosparql#"             => "geo",
            "http://www.w3.org/ns/dcat#"                        => "dcat",
            "http://www.w3.org/2001/XMLSchema#"                 => "xsd",
            "http://www.loc.gov/mads/rdf/v1#"                   => "madsrdf",
            "http://purl.org/net/ns/ontology-annot#"            => "ont",
            "http://id.loc.gov/ontologies/bflc/"                => "bflc",
            "http://purl.org/linked-data/cube#"                 => "qb",
            "http://purl.org/xtypes/"                           => "xtypes",
            "http://rdfs.org/sioc/ns#"                          => "sioc",
            "http://www.w3.org/ns/org#"                         => "org",
            "http://www.ontotext.com/"                          => "onto",
            "http://www.w3.org/ns/prov#"                        => "prov",
            "http://dbpedia.org/resource/"                      => "dbpedia",
            "http://www.w3.org/ns/sparql-service-description#"  => "sd",
            "http://www.w3.org/ns/people#"                      => "gldp",
            "http://purl.org/rss/1.0/"                          => "rss",
            "http://search.yahoo.com/searchmonkey/commerce/"    => "commerce",
            "http://purl.org/dc/terms/"                         => "dcterms",
            "http://rdfs.org/ns/void#"                          => "void",
            "http://www.wikidata.org/entity/"                   => "wd",
            "http://purl.org/ontology/bibo/"                    => "bibo",
            "http://purl.org/NET/c4dm/event.owl#"               => "event",
            "http://purl.org/dc/terms/"                         => "dct",
            "http://www.geonames.org/ontology#"                 => "geonames",
            "http://rdf.freebase.com/ns/"                       => "fb",
            "http://purl.org/dc/dcmitype/"                      => "dcmit",
            "http://purl.org/science/owl/sciencecommons/"       => "sc",
            "http://www.w3.org/ns/md#"                          => "md",
            "http://purl.org/prog/"                             => "prog",
            "http://creativecommons.org/ns#"                    => "cc",


            "http://purl.org/ontology/mo/" => "mo", 
            "http://purl.org/ontology/mbz#" => "mbz", 
            "http://purl.org/vocab/bio/0.1/" => "bio", 
            "http://www.holygoat.co.uk/owl/redwood/0.1/tags/" => "tags", 
            "http://www.lingvoj.org/ontology#" => "lingvoj", 
            "http://purl.org/vocab/relationship/" => "rel", 
            "http://dbtune.org/musicbrainz/resource/vocab/" => "vocab", 
            "http://dbtune.org/musicbrainz/resource/" => "dbtunes", 
        );

        $base = "";

        $parsed = parse_url($uri);
        if(isset($parsed['fragment']) && $parsed['fragment'] != "") {
            // anchored
            $base = $parsed['fragment'];
        } else {
            // slashed
            $base = basename($uri);
        }

        $rest = substr($uri, 0, -strlen($base));
        return $prefixDB[$rest].":".$base;
    }

    /**
     * dotProduct: dot product of two vectors (normal or associative)
     * 
     * @param array $v1 first vector
     * @param array $v2 second vector
     * 
     * @return double dot product of the two vectors
     */ 
    function dotProduct($v1, $v2){
        $sum = 0;

        foreach($v1 as $index => $value){
            $sum += $value * $v2[$index];
        }
        return $sum;
    }

    // magnitude: sqrt(sum(vector with each element to the power of 2))
    function magnitude($v) {
        $squares = array_map(function($x) {
            return pow($x, 2);
        }, $v);
        return sqrt(array_reduce($squares, function($a, $b) {
            return $a + $b;
        }));
    }

    // cosine: dot(a,b)/(magnitude(a) * magnitude(b))
    function cosine($v1, $v2){
        // Calculate the cosine similarity between $v1 and $v2 using predefined weights
        return round(dotProduct($v1, $v2) / (magnitude($v1) * magnitude($v2)), 3);
    }

    // valMatch: syntactic similarity between two "serialized" objects
    function valMatch($s1, $s2, $type = 'default', $point = 4){
        $sim = 0;
        switch ($type) {
            case 'jaccard':
                $ss1 = str_split($s1);
                $ss2 = str_split($s2);
                $arr_intersection   = array_intersect($ss1, $ss2);
                $arr_union          = array_merge($ss1, $ss2);
                $sim                = 2 * count( $arr_intersection ) / count( $arr_union );
                break;
            case 'hamming':
                $l1 = strlen($s1);
                $l2 = strlen($s2);
                $l = min($l1,$l2);
                $d = 0;
                for ($i=0;$i<$l;$i++) {
                    $d += (int) ($s1[$i]!=$s2[$i]);
                }

                $sim = $d + abs($l1-$l2);
                break;
            case 'jaro-winkler':
                $sim = JaroWinkler($s1, $s2);
                break;
            case 'default':
                // full string match
                $sim = (strtolower($s1) == strtolower($s2))?1:0;
                break;
        }
        return round($sim, $point);
    }

    // typeMatch: type similarity between two objects (extension type)
    function typeMatch($objMeta1, $objMeta2){
        $result = 0;
        $a = ($objMeta1->type == "uri");
        $b = ($objMeta2->type == "uri");
        $c = (isset($objMeta1->datatype) && isset($objMeta2->datatype))?($objMeta1->datatype == $objMeta2->datatype):false;

        if(($a && $b) || (!$a && !$b && $c)) $result = 1;
        return $result;
    }

    /**
        * Predicate similarity: for statistical similarity between two predicates, taking into account the clusters
        *
        * @param string $p1
        * @param string $p2
        *
        * @return double $semanticSimilarity
        */
    function sym_p($p1, $p2, $localCount, $sym_p, $cardRf){
        //return round($Weights['w_local'] * $localCount + $Weights['w_sem'] * $sym_p[$p1][$p2], 4);
        return $sym_p[$p1][$p2];
    }


    if(isset($_REQUEST['key']) && file_exists("results/".$_REQUEST['folder']."/0_".$_REQUEST['key'].".json") && file_exists("results/".$_REQUEST['folder']."/1_".$_REQUEST['key'].".json")){
        
        // ------------------- Coding: One couple of rep-sets --------------------------

        //setProgress(1, "Getting content ...");
    
        // Analyse one line 

        $key = $_REQUEST['key'];
        // read files into json objects
        $s0 = json_decode(file_get_contents("results/".$_REQUEST['folder']."/0_{$key}.json"));
        $s1 = json_decode(file_get_contents("results/".$_REQUEST['folder']."/1_{$key}.json"));

        //setProgress(2, "Analysing representative sets ...");

        $linked0 = 0;
        $linked1 = 0;
        $linkCount = 0;
        $perdCount = array();

        // This will contain couples of possible links between predicates: useful for ontology alignment purposes
        $possibleLinks = array();

        // analyse from -> to links
        foreach ($s0 as $triple0) {
            if(isset($perdCount[0][prefixed($triple0->predicate)])) $perdCount[0][prefixed($triple0->predicate)]++;
            else $perdCount[0][prefixed($triple0->predicate)] = 1;

            $boo = false;
            foreach ($s1 as $triple1) {
                $symo = $Weights['w_type'] * typeMatch($triple0->objectMeta, $triple1->objectMeta) + $Weights['w_val'] * valMatch($triple0->object, $triple1->object, $objectSymMethod);
                if($symo > $tau_o){
                    $linkCount += 1;
                    $boo = true;
                    $possibleLinks[] = array($triple0->predicate, $triple1->predicate, $symo);
                }
            }
            if($boo) $linked0 += 1;
        }

        // analyse from <- to links
        foreach ($s1 as $triple1) {
            if(isset($perdCount[1][prefixed($triple1->predicate)])) $perdCount[1][prefixed($triple1->predicate)]++;
            else $perdCount[1][prefixed($triple1->predicate)] = 1;
            
            $boo = false;
            foreach ($s0 as $triple0) {
                $symo = $Weights['w_type'] * typeMatch($triple0->objectMeta, $triple1->objectMeta) + $Weights['w_val'] * valMatch($triple0->object, $triple1->object, $objectSymMethod);
                if($symo > $tau_o){
                    $boo = true;
                    break;
                }
            }
            if($boo) $linked1 += 1;
        }

        //setProgress(4, "Analysing sublinks ...");

        $focusPredicates    = array();
        $refPredicates      = array();
        $coupleIndices      = array();

        foreach($possibleLinks as $link){
            $p0     = prefixed($link[0]);
            $p1     = prefixed($link[1]);
            $score  = $link[2];
            if(isset($focusPredicates[$p0][$p1])) $focusPredicates[$p0][$p1] += $score;
            else $focusPredicates[$p0][$p1] = $score;

            if(isset($refPredicates[$p1][$p0])) $refPredicates[$p1][$p0]++;
            else $refPredicates[$p1][$p0] = 1;

            $coupleIndices[$p0."%%".$p1] = 0;
        }

        $data = array(
            "linkCount"         => $linkCount,
            "distinctLinkCount" => count(array_keys($coupleIndices)),
            "nodesFrom"         => $linked0,
            "nodesTo"           => $linked1,
            "possibleLinkedPred" => $possibleLinks
        );

        //setProgress(8, "Exporting data ...");

        ?>
        
        <div class='row'>
            <div class='col-6' style="padding:1.5rem; border:solid #f7f7f9 0.2rem;">
                <h2>Indicators</h2>
                <p>Number of sublinks<span class='float-right'>I<sub>1</sub>: <?php echo $data['linkCount'];?></span></p>
                <p>Distinct Predicate-couples in sublinks<span class='float-right'>I<sub>4</sub>: <?php echo $data['distinctLinkCount'];?></span></p>
            </div>
            <div class='col-6'>
                <div id='avgSymScores'></div>
            </div>
        </div>
        <div class='clearfix'></div>

        <div class='row'>
 
            <div class='col-4'>
                <div id='CoupleCountMap'></div>
            </div>
            <div class='col-4'>
                <div id='LinkCountMap'></div>
            </div>
            <div class='col-4'>
                <div id='RatioLinkTotal'></div>
            </div>
        </div>
        <div class='clearfix'></div>


        <script>

            // Average quality of sublinks, per predicate-couple
            var data_avgSymScores = [{
                            z: [
                                <?php
                                foreach(array_keys($refPredicates) as $ref){
                                    echo "[";
                                        foreach(array_keys($focusPredicates) as $foc){
                                        if(isset($focusPredicates[$foc][$ref])) echo round($focusPredicates[$foc][$ref]/$refPredicates[$ref][$foc], 4).",";
                                        else echo "null,";
                                    }
                                    echo "],";
                                }
                                ?>
                                ],
                            x: <?php echo json_encode(array_keys($focusPredicates)); ?>,
                            y: <?php echo json_encode(array_keys($refPredicates)); ?>,
                            type: 'heatmap',
                            hoverongaps: false, 
                            xgap :3,
                            ygap :3, 
                            zauto: false, 
                            zmin:0, 
                            zmax:1, 
                            colorscale: [[0, '#ff4757'], [<?php echo $tau_o; ?>, '#ffffff'], [<?php echo ($tau_o+0.01*$tau_o); ?>, '#7bed9f'], [1, '#000000']]
                        }];
            var Lay_avgSymScores = {
                        title:'Average Similarity Scores (<?php echo $objectSymMethod; ?>)',
                        margin: {
                            l:150
                        }
                };
            Plotly.newPlot('avgSymScores', data_avgSymScores, Lay_avgSymScores, {responsive: true});

            // I2: number of possible combination per predicate couple
            var data_CoupleCountMap = [{
                            z: [
                                <?php
                                foreach(array_keys($refPredicates) as $ref){
                                    echo "[";
                                        foreach(array_keys($focusPredicates) as $foc){
                                        if(isset($perdCount[0][$foc]) && isset($perdCount[1][$ref])) echo $perdCount[0][$foc] * $perdCount[1][$ref].",";
                                        else echo "null,";
                                    }
                                    echo "],";
                                }
                                ?>
                            ],
                            x: <?php echo json_encode(array_keys($focusPredicates)); ?>,
                            y: <?php echo json_encode(array_keys($refPredicates)); ?>,
                            type: 'heatmap',
                            hoverongaps: false,
                            xgap :3,
                            ygap :3
                        }];
            var Lay_CoupleCountMap = {
                        title:'I2 (Possible Combinations)',
                        margin:{
                            l:150
                        }
                };
        
            Plotly.newPlot('CoupleCountMap', data_CoupleCountMap, Lay_CoupleCountMap, {responsive: true});

            // I3: number of sublinks per predicate couple
            var data_LinkCountMap = [{
                            z: [
                                <?php
                                foreach(array_keys($refPredicates) as $ref){
                                    echo "[";
                                        foreach(array_keys($focusPredicates) as $foc){
                                        if(isset($focusPredicates[$foc][$ref])) echo $refPredicates[$ref][$foc].",";
                                        else echo "null,";
                                    }
                                    echo "],";
                                }
                                ?>
                            ],
                            x: <?php echo json_encode(array_keys($focusPredicates)); ?>,
                            y: <?php echo json_encode(array_keys($refPredicates)); ?>,
                            type: 'heatmap',
                            hoverongaps: false,
                            xgap :3,
                            ygap :3
                        }];
            var Lay_LinkCountMap = {
                        title:'I3 (Existing links)',
                        margin:{
                            l:150
                        }
                };
        
            Plotly.newPlot('LinkCountMap', data_LinkCountMap, Lay_LinkCountMap, {responsive: true});

            // I5: ratio (sublinks / possible combinations)
            var data_RatioLinkTotal = [{
                            z: [
                                <?php
                                foreach(array_keys($refPredicates) as $ref){
                                    echo "[";
                                        foreach(array_keys($focusPredicates) as $foc){
                                        if(isset($focusPredicates[$foc][$ref]) && isset($perdCount[0][$foc]) && isset($perdCount[1][$ref]))
                                            echo ($refPredicates[$ref][$foc] / ($perdCount[0][$foc] * $perdCount[1][$ref])).",";
                                        else echo "null,";
                                    }
                                    echo "],";
                                }
                                ?>
                            ],
                            x: <?php echo json_encode(array_keys($focusPredicates)); ?>,
                            y: <?php echo json_encode(array_keys($refPredicates)); ?>,
                            type: 'heatmap',
                            hoverongaps: false,
                            xgap :3,
                            ygap :3
                        }];
            var Lay_RatioLinkTotal = {
                        title:'I4 (I3/I2)',
                        margin:{
                            l:150
                        }
                };
        
            Plotly.newPlot('RatioLinkTotal', data_RatioLinkTotal, Lay_RatioLinkTotal, {responsive: true});
        
        </script>

        <div class='card card-body fixedBot'>
            <a data-toggle='collapse' href='#collapseDiv' role='button' aria-expanded='false' aria-controls='collapseDiv'>Debug information</a>
            <div class='collapse' id='collapseDiv'>
                <?php var_dump(json_encode($data, JSON_PRETTY_PRINT)); ?>
            </div>
        </div>
       <?php  
       //setProgress(10, "Done.");
    }
    else
    {

        // ------------------- Coding: Iterate over all sets --------------------------

        // ********************************************
        //
        //           Complete analysis
        //
        // ********************************************

        

        $LinkedPred = array();
        $counts     = array();  // indices: tripleCount[0-1][$key], tripleLinked[0-1][$key], linkCount[$key], [$key][0-1][predicate])
        $folder     = $_REQUEST['folder'];

        $simm = 0;

        // Later:   For semantics of each predicate (sym, reflex, trans, funct)
        //          $predicateSemantics = json_decode(file_get_contents("prerequisits/semantics.json"), true);

        $fileCount = floor(count(glob("results/".$folder."/*.json"))/2);
        
        setProgress(1, "Analysing {$fileCount} links for sublinks ...");

        for($key = 0; $key < $fileCount; $key++){

            // read files into json objects
            $s0 = json_decode(file_get_contents("results/".$folder."/0_{$key}.json"));
            $s1 = json_decode(file_get_contents("results/".$folder."/1_{$key}.json"));
            
            // init the array (required for $counts)
            $LinkedPred[$key] = array();

            // Triple count in each representative set
            $counts['tripleCount'][0][$key] = count($s0);
            $counts['tripleCount'][1][$key] = count($s1);
            
            // Init linked triple Count
            $counts['tripleLinked'][0][$key] = 0;
            $counts['tripleLinked'][1][$key] = 0;

            // analyse focus -> ref links
            foreach ($s0 as $triple0) {
                $p0 = prefixed($triple0->predicate);
                // Count distinct predicates for Focus set (for I2 later)
                isset($counts[0][$p0][$key]) ? $counts[0][$p0][$key]++ : ($counts[0][$p0][$key] = 1);

                // Check for links, push links into $LinkedPred, add one triple to the count
                $boo = false;
                foreach ($s1 as $triple1) {
                    $symo = $Weights['w_type'] * typeMatch($triple0->objectMeta, $triple1->objectMeta) + $Weights['w_val'] * valMatch($triple0->object, $triple1->object, $objectSymMethod);
                    if($symo >= $tau_o){
                        $boo = true;
                        $LinkedPred[$key][] = array($triple0, $triple1, $symo);
                    }
                }
                if($boo) $counts['tripleLinked'][0][$key]++;
            }
            
            // analyse ref -> focus links
            foreach ($s1 as $triple1) {
                $p1 = prefixed($triple1->predicate);
                // Count distinct predicates for Reference set (for I2 later)
                isset($counts[1][$p1][$key]) ? $counts[1][$p1][$key]++ : ($counts[1][$p1][$key] = 1);
                
                // Check for links, add one triple to the count
                $boo = false;
                foreach ($s0 as $triple0) {
                    $symo = $Weights['w_type'] * typeMatch($triple0->objectMeta, $triple1->objectMeta) + $Weights['w_val'] * valMatch($triple0->object, $triple1->object, $objectSymMethod);
                    if($symo > $tau_o){
                        $boo = true;
                        break;
                    }
                }
                if($boo) $counts['tripleLinked'][1][$key]++;
            }

            $counts['linkedAvg'][0][$key] = ($counts['tripleCount'][0][$key])?($counts['tripleLinked'][0][$key]/$counts['tripleCount'][0][$key]):0;
            $counts['linkedAvg'][1][$key] = ($counts['tripleCount'][1][$key])?($counts['tripleLinked'][1][$key]/$counts['tripleCount'][1][$key]):0;
            $counts['LinkCount'][$key] = count($LinkedPred[$key]);
        }



        setProgress(2, "Analysing sublinks ...");

        // LinkedPred = array(CBD_ID, r = array(x1,y1,z1), r' = array(x2,y2,z2), sim_degree(z1, z2))

        $predFeatureTensor  = array();
        $sym_p              = array();
        $semanticTensor     = array();
        $focusPredicates    = array();          // average object similarity per couple of predicates, per couple of sets
        $refPredicates      = array();          // number of links per couple of predicates, per couple of sets

        // Get the count for each and every couple of predicates
        foreach($LinkedPred as $cbdID => $sublinks){
            foreach ($sublinks as $sublink) {
                $p0 = prefixed($sublink[0]->predicate);
                $p1 = prefixed($sublink[1]->predicate);
                $score = $sublink[2];
                isset($focusPredicates[$p0][$p1][$cbdID])   ? ($focusPredicates[$p0][$p1][$cbdID] += $score)    : ($focusPredicates[$p0][$p1][$cbdID] = $score);
                isset($refPredicates[$p1][$p0][$cbdID])     ? ($refPredicates[$p1][$p0][$cbdID]++)              : ($refPredicates[$p1][$p0][$cbdID] = 1);
            }
        }

        setProgress(3, "Setting Predicate Tensor ...");

        
        $focusPattern = array();
        $refPattern = array();


        // Convert the measures from all arrays in one tensor: $predFeatureTensor
        foreach(array_keys($refPredicates) as $ref){
            foreach(array_keys($focusPredicates) as $foc){
                if(isset($focusPredicates[$foc][$ref])){
                    // We check here per rep-set couple and for $foc-$ref sublinks


                    // Sums
                   
                    $sum_I1byI3             = array_sum(array_map(function($a, $b){return $a/$b;}, $focusPredicates[$foc][$ref], $refPredicates[$ref][$foc]));
                    $checkTau               = round($sum_I1byI3/$fileCount, 4);
                    if($checkTau >= $tau_avg){

                        $numOfCBDsWithFocRef = count($focusPredicates[$foc][$ref]);
                        // All Average object similarity measures (non normalised)
                        $predFeatureTensor[$foc."%%".$ref]['I1']        = $focusPredicates[$foc][$ref];

                        // All sublink counts
                        $predFeatureTensor[$foc."%%".$ref]['I3']        = $refPredicates[$ref][$foc];

                        // $foc-$ref possible combination count
                        foreach($focusPredicates[$foc][$ref] as $cbdID => $value){
                            $predFeatureTensor[$foc."%%".$ref]['I2'][$cbdID] = $counts[0][$foc][$cbdID] * $counts[1][$ref][$cbdID];
                        }

                        $sum_I1                 = array_sum($focusPredicates[$foc][$ref]);
                        $sum_I3                 = array_sum($refPredicates[$ref][$foc]);
                        $sum_I2                 = array_sum($predFeatureTensor[$foc."%%".$ref]['I2']);
                        $sum_I5                 = array_sum($counts['LinkCount']);
                        $sum_I3byI2             = array_sum(array_map(function($a, $b){return $a/$b;}, $predFeatureTensor[$foc."%%".$ref]['I3'], $predFeatureTensor[$foc."%%".$ref]['I2']));
                        $sum_I3byI5             = array_sum(array_map(function($a, $b){return ($b>0)?($a/$b):0;}, $predFeatureTensor[$foc."%%".$ref]['I3'], $counts['LinkCount']));

                    // Averages
                        // Total Avg Sim Score / Total Avg  pred-couple subLink Count
                        $predFeatureTensor[$foc."%%".$ref]['sem']['sameURI'] = ($foc == $ref);
                        $predFeatureTensor[$foc."%%".$ref]['sem']['MU1_G'] = round($sum_I1/$sum_I3, 4);
                        // Sum(Local Avg Sim Score / Local pred-couple subLink Count) / Number of CBDs with the pred-couple
                        $predFeatureTensor[$foc."%%".$ref]['sem']['MU1_E'] = round($sum_I1byI3/$numOfCBDsWithFocRef, 4);
                        // Sum(Local Avg Sim Score / Local pred-couple subLink Count) / Number of CBDs with the pred-couple
                        $predFeatureTensor[$foc."%%".$ref]['sem']['MU1_L'] = $checkTau;

                        $predFeatureTensor[$foc."%%".$ref]['sem']['MU2_E'] = round($sum_I2/$numOfCBDsWithFocRef, 4);
                        $predFeatureTensor[$foc."%%".$ref]['sem']['MU2_L'] = round($sum_I2/$fileCount, 4);

                        $predFeatureTensor[$foc."%%".$ref]['sem']['MU3_E'] = round($sum_I3/$numOfCBDsWithFocRef, 4);
                        $predFeatureTensor[$foc."%%".$ref]['sem']['MU3_L'] = round($sum_I3/$fileCount, 4);

                        $predFeatureTensor[$foc."%%".$ref]['sem']['MU4_E'] = round($sum_I3byI2/$numOfCBDsWithFocRef, 4);
                        $predFeatureTensor[$foc."%%".$ref]['sem']['MU4_L'] = round($sum_I3byI2/$fileCount, 4);
                        $predFeatureTensor[$foc."%%".$ref]['sem']['MU4_G'] = round($sum_I3/$sum_I2, 4);

                        $predFeatureTensor[$foc."%%".$ref]['sem']['MU5_E'] = round($sum_I3byI5/$numOfCBDsWithFocRef, 4);
                        $predFeatureTensor[$foc."%%".$ref]['sem']['MU5_L'] = round($sum_I3byI5/$fileCount, 4);
                        $predFeatureTensor[$foc."%%".$ref]['sem']['MU5_G'] = round($sum_I3/$sum_I5, 4);


                        $sym_p[$foc][$ref] = dotProduct($predFeatureTensor[$foc . "%%" . $ref]['sem'], $semanticWeights);

                        $focusPattern[$foc] = true;
                        $refPattern[$ref] = true;
                    }
                }
            }
        }

        $focusKeys = array_keys($focusPattern);
        $refKeys = array_keys($refPattern);

        setProgress(4, "Dumping data ...");

        $data = array(
            "entitiesAnalysed"                      => $fileCount,
            "totalTriples0"                         => array_sum($counts['tripleCount'][0]),
            "totalTriples1"                         => array_sum($counts['tripleCount'][1]),
            "totalLinkedNodes0"                     => array_sum($counts['tripleLinked'][0]),
            "totalLinkedNodes1"                     => array_sum($counts['tripleLinked'][1]),
            "totalsublinks"                         => array_sum($counts['LinkCount']),
            "avgSublinksPerCBD"                     => array_sum($counts['LinkCount'])/$fileCount,
            "avgLinkedNodes0_perCBD_Global"         => round(array_sum($counts['tripleLinked'][0])/array_sum($counts['tripleCount'][0]), 4),
            "avgLinkedNodes0_perCBD_AvgLocal"       => round(array_sum($counts['linkedAvg'][0])/$fileCount, 4),
            "avgLinkedNodes1_perCBD_Global"         => round(array_sum($counts['tripleLinked'][1])/array_sum($counts['tripleCount'][1]), 4),
            "avgLinkedNodes1_perCBD_AvgLocal"       => round(array_sum($counts['linkedAvg'][1])/$fileCount, 4),
            "focusPredicates"                       => $focusKeys,
            "refPredicates"                         => $refKeys,
        );

        $data_sameURI_z = "";
        $data_MU1_G_z = "";
        $data_MU1_E_z = "";
        $data_MU1_L_z = "";
        $data_MU2_E_z = "";
        $data_MU2_L_z = "";
        $data_MU3_E_z = "";
        $data_MU3_L_z = "";
        $data_MU4_E_z = "";
        $data_MU4_L_z = "";
        $data_MU4_G_z = "";
        $data_MU5_E_z = "";
        $data_MU5_L_z = "";
        $data_MU5_G_z = "";
        $data_sym_p_z = "";

        foreach($refKeys as $ref){
            $data_sameURI_z .= "[";
            $data_MU1_G_z .= "[";
            $data_MU1_E_z .= "[";
            $data_MU1_L_z .= "[";
            $data_MU2_E_z .= "[";
            $data_MU2_L_z .= "[";
            $data_MU3_E_z .= "[";
            $data_MU3_L_z .= "[";
            $data_MU4_E_z .= "[";
            $data_MU4_L_z .= "[";
            $data_MU4_G_z .= "[";
            $data_MU5_E_z .= "[";
            $data_MU5_L_z .= "[";
            $data_MU5_G_z .= "[";
            $data_sym_p_z .= "[";

            foreach($focusKeys as $foc){
                if(isset($predFeatureTensor[$foc."%%".$ref]['sem']['MU1_G'])){
                    $data_sameURI_z = $data_sameURI_z . $predFeatureTensor[$foc."%%".$ref]['sem']['sameURI'] .",";
                    $data_MU1_G_z = $data_MU1_G_z . $predFeatureTensor[$foc."%%".$ref]['sem']['MU1_G'] .",";
                    $data_MU1_E_z = $data_MU1_E_z . $predFeatureTensor[$foc."%%".$ref]['sem']['MU1_E'] .",";
                    $data_MU1_L_z = $data_MU1_L_z . $predFeatureTensor[$foc."%%".$ref]['sem']['MU1_L'] .",";
                    $data_MU2_E_z = $data_MU2_E_z . $predFeatureTensor[$foc."%%".$ref]['sem']['MU2_E'] .",";
                    $data_MU2_L_z = $data_MU2_L_z . $predFeatureTensor[$foc."%%".$ref]['sem']['MU2_L'] .",";
                    $data_MU3_E_z = $data_MU3_E_z . $predFeatureTensor[$foc."%%".$ref]['sem']['MU3_E'] .",";
                    $data_MU3_L_z = $data_MU3_L_z . $predFeatureTensor[$foc."%%".$ref]['sem']['MU3_L'] .",";
                    $data_MU4_E_z = $data_MU4_E_z . $predFeatureTensor[$foc."%%".$ref]['sem']['MU4_E'] .",";
                    $data_MU4_L_z = $data_MU4_L_z . $predFeatureTensor[$foc."%%".$ref]['sem']['MU4_L'] .",";
                    $data_MU4_G_z = $data_MU4_G_z . $predFeatureTensor[$foc."%%".$ref]['sem']['MU4_G'] .",";
                    $data_MU5_E_z = $data_MU5_E_z . $predFeatureTensor[$foc."%%".$ref]['sem']['MU5_E'] .",";
                    $data_MU5_L_z = $data_MU5_L_z . $predFeatureTensor[$foc."%%".$ref]['sem']['MU5_L'] .",";
                    $data_MU5_G_z = $data_MU5_G_z . $predFeatureTensor[$foc."%%".$ref]['sem']['MU5_G'] .",";
                    $data_sym_p_z = $data_sym_p_z . $sym_p[$foc][$ref] .",";
                } else {
                    $data_sameURI_z .= "null,";
                    $data_MU1_G_z .= "null,";
                    $data_MU1_E_z .= "null,";
                    $data_MU1_L_z .= "null,";
                    $data_MU2_E_z .= "null,";
                    $data_MU2_L_z .= "null,";
                    $data_MU3_E_z .= "null,";
                    $data_MU3_L_z .= "null,";
                    $data_MU4_E_z .= "null,";
                    $data_MU4_L_z .= "null,";
                    $data_MU4_G_z .= "null,";
                    $data_MU5_E_z .= "null,";
                    $data_MU5_L_z .= "null,";
                    $data_MU5_G_z .= "null,";
                    $data_sym_p_z .= "null,";
                }
            }

            $data_sameURI_z .= "],";
            $data_MU1_G_z .= "],";
            $data_MU1_E_z .= "],";
            $data_MU1_L_z .= "],";
            $data_MU2_E_z .= "],";
            $data_MU2_L_z .= "],";
            $data_MU3_E_z .= "],";
            $data_MU3_L_z .= "],";
            $data_MU4_E_z .= "],";
            $data_MU4_L_z .= "],";
            $data_MU4_G_z .= "],";
            $data_MU5_E_z .= "],";
            $data_MU5_L_z .= "],";
            $data_MU5_G_z .= "],";
            $data_sym_p_z .= "],";
        }

        setProgress(8, "Preparing data for heatmaps ...");
        ?>
        
        <div class='row'>
            <div class='col-4'><div id='MU1_G'></div></div>
            <div class='col-4'><div id='MU1_E'></div></div>
            <div class='col-4'><div id='MU1_L'></div></div>
        </div>
        <div class='clearfix'></div>
        <div class='row'>
            <div class='col-4'><div id='sameURI'></div></div>
            <div class='col-4'><div id='MU2_E'></div></div>
            <div class='col-4'><div id='MU2_L'></div></div>
        </div>
        <div class='clearfix'></div>
        <div class='row'>
            <div class='col-6'><div id='MU3_E'></div></div>
            <div class='col-6'><div id='MU3_L'></div></div>
        </div>
        <div class='clearfix'></div>
        <div class='row'>
            <div class='col-4'><div id='MU4_G'></div></div>
            <div class='col-4'><div id='MU4_E'></div></div>
            <div class='col-4'><div id='MU4_L'></div></div>
        </div>
        <div class='clearfix'></div>
        <div class='row'>
            <div class='col-4'><div id='MU5_G'></div></div>
            <div class='col-4'><div id='MU5_E'></div></div>
            <div class='col-4'><div id='MU5_L'></div></div>
        </div>
        <div class='clearfix'></div>
        <div class='row'>
            <div class='col-4'><div id='sym_p'></div></div>
            <div class='col-4'></div>
            <div class='col-4'></div>
        </div>
        <div class='clearfix'></div>

        <script>
            var data_sameURI = [{
                            z: [<?php echo $data_sameURI_z;?>],
                            x: <?php echo json_encode($focusKeys); ?>,
                            y: <?php echo json_encode($refKeys); ?>,
                            type: 'heatmap',
                            hoverongaps: false, 
                            xgap :3,
                            ygap :3, 
                            zauto: false, 
                            zmin:0, 
                            zmax:1, 
                            colorscale: [[0, '#ff4757'], [<?php echo ($tau_avg-0.01*$tau_avg); ?>, '#ffffff'], [<?php echo $tau_avg; ?>, '#7bed9f'], [1, '#000000']]
                        }];
            var Lay_sameURI = {
                        title:'sameURI <br><span style="font-size:10px;">Sum(AvgSimPerPredCoupleCBD)/Sum(NumberofLinks)</span>',
                        margin: {
                            l:150,
                            b:50
                        }
                };
            Plotly.newPlot('sameURI', data_sameURI, Lay_sameURI, {responsive: true});

            var data_MU1_G = [{
                            z: [<?php echo $data_MU1_G_z;?>],
                            x: <?php echo json_encode($focusKeys); ?>,
                            y: <?php echo json_encode($refKeys); ?>,
                            type: 'heatmap',
                            hoverongaps: false, 
                            xgap :3,
                            ygap :3, 
                            zauto: false, 
                            zmin:0, 
                            zmax:1, 
                            colorscale: [[0, '#ff4757'], [<?php echo ($tau_avg-0.01*$tau_avg); ?>, '#ffffff'], [<?php echo $tau_avg; ?>, '#7bed9f'], [1, '#000000']]
                        }];
            var Lay_MU1_G = {
                        title:'MU1_G <br><span style="font-size:10px;">Sum(AvgSimPerPredCoupleCBD)/Sum(NumberofLinks)</span>',
                        margin: {
                            l:150,
                            b:50
                        }
                };
            Plotly.newPlot('MU1_G', data_MU1_G, Lay_MU1_G, {responsive: true});

            var data_MU1_E = [{
                            z: [<?php echo $data_MU1_E_z;?>],
                            x: <?php echo json_encode($focusKeys); ?>,
                            y: <?php echo json_encode($refKeys); ?>,
                            type: 'heatmap',
                            hoverongaps: false, 
                            xgap :3,
                            ygap :3, 
                            zauto: false, 
                            zmin:0, 
                            zmax:1, 
                            colorscale: [[0, '#ff4757'], [<?php echo ($tau_avg-0.01*$tau_avg); ?>, '#ffffff'], [<?php echo $tau_avg; ?>, '#7bed9f'], [1, '#000000']]
                        }];
            var Lay_MU1_E = {
                        title:'MU1_E <br><span style="font-size:10px;">Sum(localAvgSimPerPred/localSublink)/NumCBDwithExistSublink</span>',
                        margin: {
                            l:150,
                            b:50
                        }
                };
            Plotly.newPlot('MU1_E', data_MU1_E, Lay_MU1_E, {responsive: true});

            var data_MU1_L = [{
                            z: [<?php echo $data_MU1_L_z;?>],
                            x: <?php echo json_encode($focusKeys); ?>,
                            y: <?php echo json_encode($refKeys); ?>,
                            type: 'heatmap',
                            hoverongaps: false, 
                            xgap :3,
                            ygap :3, 
                            zauto: false, 
                            zmin:0, 
                            zmax:1, 
                            colorscale: [[0, '#ff4757'], [<?php echo ($tau_avg-0.01*$tau_avg); ?>, '#ffffff'], [<?php echo $tau_avg; ?>, '#7bed9f'], [1, '#000000']]
                        }];
            var Lay_MU1_L = {
                        title:'MU1_L <br><span style="font-size:10px;">Sum(localAvgSimPerPred/localSublink)/TotalNumCBDs</span>',
                        margin: {
                            l:150,
                            b:50
                        }
                };
            Plotly.newPlot('MU1_L', data_MU1_L, Lay_MU1_L, {responsive: true});

            var data_MU2_E = [{
                            z: [<?php echo $data_MU2_E_z;?>
                            ],
                            x: <?php echo json_encode($focusKeys); ?>,
                            y: <?php echo json_encode($refKeys); ?>,
                            type: 'heatmap',
                            hoverongaps: false,
                            xgap :3,
                            ygap :3
                        }];
            var Lay_MU2_E = {
                        title:'MU2_E <br><span style="font-size:10px;">Sum(PredCoupleCombiCountPerCBD)/NumCBDwithExistSublink</span>',
                        margin:{
                            l:150,
                            b:50
                        }
                };
        
            Plotly.newPlot('MU2_E', data_MU2_E, Lay_MU2_E, {responsive: true});

            var data_MU2_L = [{
                            z: [<?php echo $data_MU2_L_z;?>
                            ],
                            x: <?php echo json_encode($focusKeys); ?>,
                            y: <?php echo json_encode($refKeys); ?>,
                            type: 'heatmap',
                            hoverongaps: false,
                            xgap :3,
                            ygap :3
                        }];
            var Lay_MU2_L = {
                        title:'MU2_L <br><span style="font-size:10px;">Sum(PredCoupleCombiCountPerCBD)/TotalNumCBDs</span>',
                        margin:{
                            l:150,
                            b:50
                        }
                };
        
            Plotly.newPlot('MU2_L', data_MU2_L, Lay_MU2_L, {responsive: true});

            var data_MU3_E = [{
                            z: [<?php echo $data_MU3_E_z;?>
                            ],
                            x: <?php echo json_encode($focusKeys); ?>,
                            y: <?php echo json_encode($refKeys); ?>,
                            type: 'heatmap',
                            hoverongaps: false,
                            xgap :3,
                            ygap :3
                        }];
            var Lay_MU3_E = {
                        title:'MU3_E <br><span style="font-size:10px;">Sum(PredCoupleSublinkCountPerCBD)/NumCBDwithExistSublink</span>',
                        margin:{
                            l:150,
                            b:50
                        }
                };
        
            Plotly.newPlot('MU3_E', data_MU3_E, Lay_MU3_E, {responsive: true});

            var data_MU3_L = [{
                            z: [<?php echo $data_MU3_L_z;?>
                            ],
                            x: <?php echo json_encode($focusKeys); ?>,
                            y: <?php echo json_encode($refKeys); ?>,
                            type: 'heatmap',
                            hoverongaps: false,
                            xgap :3,
                            ygap :3
                        }];
            var Lay_MU3_L = {
                        title:'MU3_L <br><span style="font-size:10px;">Sum(PredCoupleSublinkCountPerCBD)/TotalNumCBDs</span>',
                        margin:{
                            l:150,
                            b:50
                        }
                };
        
            Plotly.newPlot('MU3_L', data_MU3_L, Lay_MU3_L, {responsive: true});

            var data_MU4_G = [{
                            z: [<?php echo $data_MU4_G_z;?>
                            ],
                            x: <?php echo json_encode($focusKeys); ?>,
                            y: <?php echo json_encode($refKeys); ?>,
                            type: 'heatmap',
                            hoverongaps: false,
                            xgap :3,
                            ygap :3
                        }];
            var Lay_MU4_G = {
                        title:'MU4_G <br><span style="font-size:10px;"></span>',
                        margin:{
                            l:150,
                            b:50
                        }
                };
        
            Plotly.newPlot('MU4_G', data_MU4_G, Lay_MU4_G, {responsive: true});

            var data_MU4_E = [{
                            z: [<?php echo $data_MU4_E_z;?>
                            ],
                            x: <?php echo json_encode($focusKeys); ?>,
                            y: <?php echo json_encode($refKeys); ?>,
                            type: 'heatmap',
                            hoverongaps: false,
                            xgap :3,
                            ygap :3
                        }];
            var Lay_MU4_E = {
                        title:'MU4_E <br><span style="font-size:10px;"></span>',
                        margin:{
                            l:150,
                            b:50
                        }
                };
        
            Plotly.newPlot('MU4_E', data_MU4_E, Lay_MU4_E, {responsive: true});

            var data_MU4_L = [{
                            z: [<?php echo $data_MU4_L_z;?>
                            ],
                            x: <?php echo json_encode($focusKeys); ?>,
                            y: <?php echo json_encode($refKeys); ?>,
                            type: 'heatmap',
                            hoverongaps: false,
                            xgap :3,
                            ygap :3
                        }];
            var Lay_MU4_L = {
                        title:'MU4_L <br><span style="font-size:10px;"></span>',
                        margin:{
                            l:150,
                            b:50
                        }
                };
        
            Plotly.newPlot('MU4_L', data_MU4_L, Lay_MU4_L, {responsive: true});

            var data_MU5_G = [{
                            z: [<?php echo $data_MU5_G_z;?>
                            ],
                            x: <?php echo json_encode($focusKeys); ?>,
                            y: <?php echo json_encode($refKeys); ?>,
                            type: 'heatmap',
                            hoverongaps: false,
                            xgap :3,
                            ygap :3
                        }];
            var Lay_MU5_G = {
                        title:'MU5_G <br><span style="font-size:10px;"></span>',
                        margin:{
                            l:150,
                            b:50
                        }
                };
        
            Plotly.newPlot('MU5_G', data_MU5_G, Lay_MU5_G, {responsive: true});

            var data_MU5_E = [{
                            z: [<?php echo $data_MU5_E_z;?>
                            ],
                            x: <?php echo json_encode($focusKeys); ?>,
                            y: <?php echo json_encode($refKeys); ?>,
                            type: 'heatmap',
                            hoverongaps: false,
                            xgap :3,
                            ygap :3
                        }];
            var Lay_MU5_E = {
                        title:'MU5_E <br><span style="font-size:10px;"></span>',
                        margin:{
                            l:150,
                            b:50
                        }
                };
        
            Plotly.newPlot('MU5_E', data_MU5_E, Lay_MU5_E, {responsive: true});

            var data_MU5_L = [{
                            z: [<?php echo $data_MU5_L_z;?>
                            ],
                            x: <?php echo json_encode($focusKeys); ?>,
                            y: <?php echo json_encode($refKeys); ?>,
                            type: 'heatmap',
                            hoverongaps: false,
                            xgap :3,
                            ygap :3
                        }];
            var Lay_MU5_L = {
                        title:'MU5_L <br><span style="font-size:10px;"></span>',
                        margin:{
                            l:150,
                            b:50
                        }
                };
        
            Plotly.newPlot('MU5_L', data_MU5_L, Lay_MU5_L, {responsive: true});

            var data_sym_p = [{
                            z: [<?php echo $data_sym_p_z;?>
                            ],
                            x: <?php echo json_encode($focusKeys); ?>,
                            y: <?php echo json_encode($refKeys); ?>,
                            type: 'heatmap',
                            hoverongaps: false,
                            xgap :3,
                            ygap :3
                        }];
            var Lay_sym_p = {
                        title:'sym_p <br><span style="font-size:10px;"></span>',
                        margin:{
                            l:150,
                            b:50
                        }
                };
        
            Plotly.newPlot('sym_p', data_sym_p, Lay_sym_p, {responsive: true});

        </script>

        <div class='card card-body fixedBot'>
            <a data-toggle='collapse' href='#collapseDiv' role='button' aria-expanded='false' aria-controls='collapseDiv'>Debug information</a>
            <div class='collapse' id='collapseDiv'>
                <?php var_dump(json_encode($data, JSON_PRETTY_PRINT)); ?>
            </div>
        </div>
<?php
    // END -- Complete analysis    
    setProgress(10, "Done.");  
    }

    session_destroy();
?>