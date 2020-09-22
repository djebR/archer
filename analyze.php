<?php
    session_start();
    
    function setProgress($value, $comment){
        $_SESSION['progress'] = array('percent' => $value, 'comment' => $comment);
    }

    // --------------- Commands for debug purpose only ---------------
    ini_set('xdebug.var_display_max_depth', '10');
    ini_set('xdebug.var_display_max_children', '256');
    ini_set('xdebug.var_display_max_data', '90000');
// ----------------------------------------------------------------

    include('assets/function.php');

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



    if(isset($_REQUEST['key'])
        && file_exists("results/".$_REQUEST['f'].".json")){
        
        // ------------------- Coding: One couple of rep-sets --------------------------

        //setProgress(1, "Getting content ...");
    
        // Analyse one line 

        $key = $_REQUEST['key'];
        // read files into json objects
        $focusGraphs = json_decode(file_get_contents("results/".$_REQUEST['f'].".json"), true);
        $s0 = $focusGraphs[$key]['target'];
        $s1 = $focusGraphs[$key]['reference'];

        //setProgress(2, "Analysing representative sets ...");

        $linked0 = 0;
        $linked1 = 0;
        $linkCount = 0;
        $perdCount = array();

        // This will contain couples of possible links between predicates: useful for ontology alignment purposes
        $possibleLinks = array();

        // analyse from -> to links
        foreach ($s0 as $triple0) {
            if(isset($perdCount[0][prefixed($triple0['predicate'])])) $perdCount[0][prefixed($triple0['predicate'])]++;
            else $perdCount[0][prefixed($triple0['predicate'])] = 1;

            $boo = false;
            foreach ($s1 as $triple1) {
                $symo = $Weights['w_type'] * typeMatch($triple0['objectMeta'], $triple1['objectMeta']) + $Weights['w_val'] * valMatch($triple0['object'], $triple1['object'], $objectSymMethod);
                if($symo > $tau_o){
                    $linkCount += 1;
                    $boo = true;
                    $possibleLinks[] = array($triple0['predicate'], $triple1['predicate'], $symo);
                }
            }
            if($boo) $linked0 += 1;
        }

        // analyse from <- to links
        foreach ($s1 as $triple1) {
            if(isset($perdCount[1][prefixed($triple1['predicate'])])) $perdCount[1][prefixed($triple1['predicate'])]++;
            else $perdCount[1][prefixed($triple1['predicate'])] = 1;
            
            $boo = false;
            foreach ($s0 as $triple0) {
                $symo = $Weights['w_type'] * typeMatch($triple0['objectMeta'], $triple1['objectMeta']) + $Weights['w_val'] * valMatch($triple0['object'], $triple1['object'], $objectSymMethod);
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
                <p>Number of evidence links<span class='float-right'>I<sub>1</sub>: <?php echo $data['linkCount'];?></span></p>
                <p>Distinct Predicate-couples in evidence links<span class='float-right'>I<sub>2</sub>: <?php echo $data['distinctLinkCount'];?></span></p>
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
                <pre><?php print_r($data); ?></pre>
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
                $p0 = prefixed($triple0['predicate']);
                // Count distinct predicates for Focus set (for I2 later)
                isset($counts[0][$p0][$key]) ? $counts[0][$p0][$key]++ : ($counts[0][$p0][$key] = 1);

                // Check for links, push links into $LinkedPred, add one triple to the count
                $boo = false;
                foreach ($s1 as $triple1) {
                    $symo = $Weights['w_type'] * typeMatch($triple0['objectMeta'], $triple1['objectMeta']) + $Weights['w_val'] * valMatch($triple0['object'], $triple1['object'], $objectSymMethod);
                    if($symo >= $tau_o){
                        $boo = true;
                        $LinkedPred[$key][] = array($triple0, $triple1, $symo);
                    }
                }
                if($boo) $counts['tripleLinked'][0][$key]++;
            }
            
            // analyse ref -> focus links
            foreach ($s1 as $triple1) {
                $p1 = prefixed($triple1['predicate']);
                // Count distinct predicates for Reference set (for I2 later)
                isset($counts[1][$p1][$key]) ? $counts[1][$p1][$key]++ : ($counts[1][$p1][$key] = 1);
                
                // Check for links, add one triple to the count
                $boo = false;
                foreach ($s0 as $triple0) {
                    $symo = $Weights['w_type'] * typeMatch($triple0['objectMeta'], $triple1['objectMeta']) + $Weights['w_val'] * valMatch($triple0['object'], $triple1['object'], $objectSymMethod);
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
                $p0 = prefixed($sublink[0]['predicate']);
                $p1 = prefixed($sublink[1]['predicate']);
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
                $R[$foc . "%%" . $ref]['R3']                     = array_map(function ($a, $b) {return $a / $b;}, $focusPredicates[$foc][$ref], $refPredicates[$ref][$foc]);
                    $sum_I1byI3             = array_sum($R[$foc . "%%" . $ref]['R3']);
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
                    $R[$foc . "%%" . $ref]['R2']                     = array_map(function ($a, $b) {return $a / $b;}, $predFeatureTensor[$foc . "%%" . $ref]['I3'], $predFeatureTensor[$foc . "%%" . $ref]['I2']);
                        $sum_I3byI2             = array_sum($R[$foc . "%%" . $ref]['R2']);
                    $R[$foc . "%%" . $ref]['R1']                     = array_map(function($a, $b){return ($b>0)?($a/$b):0;}, $predFeatureTensor[$foc."%%".$ref]['I3'], $counts['LinkCount']);
                        $sum_I3byI5             = array_sum($R[$foc . "%%" . $ref]['R1']);

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
                <?php print(json_encode($data, JSON_PRETTY_PRINT)); ?>
            </div>
        </div>
<?php
    // END -- Complete analysis    

    $p = fopen("results/links/" . $_REQUEST['folder'] . "/" . $_REQUEST['method'] . "/pred.json", "w");
    fwrite($p, json_encode($feat));
    fclose($p);
    setProgress(10, "Done.");  
    }

    session_destroy();
?>