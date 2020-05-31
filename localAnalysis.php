<?php

//session_start();
ini_set('xdebug.var_display_max_depth', '10');
ini_set('xdebug.var_display_max_children', '256');
ini_set('xdebug.var_display_max_data', '90000');
ini_set('max_execution_time', '0');

if (ob_get_level() == 0) ob_start();
include('assets/function.php');


function setProgress($value, $comment)
{
    $_SESSION['progress'] = array('percent' => $value, 'comment' => $comment);
}

// --------------- Commands for debug purpose only ---------------

// ----------------------------------------------------------------


// --------------- Global variables & Config ---------------

$semanticWeights = array(
    'sameURI' => 0.2,
    'MU1_L' => 0.5,
    'MU4_L' => 0.2,
    'MU5_L' => 0.1,
);

// Thresholds
$step       = 0.25;
// General configuration
$objectSymMethod = (isset($_REQUEST['method'])) ? $_REQUEST['method'] : "default";

if (!file_exists("results/links/" . $_REQUEST['folder'] . "/" . $_REQUEST['method'])) {
    if (!mkdir("results/links/" . $_REQUEST['folder']) && !mkdir("results/links/" . $_REQUEST['folder'] . "/" . $_REQUEST['method'])) {
        die('Cannot create folder for results');
    }
}

// ------------------- Coding: Iterate over all sets --------------------------

// ********************************************
//
//           Complete analysis
//
// ********************************************

//echo "Start analysis:<br>";
$folder     = $_REQUEST['folder'];

$fileCount = floor(count(glob("results/" . $folder . "/*.json")) / 2);

$LinkedPred         = array();
$counts             = array();

for ($key = 0; $key < $fileCount; $key++) {

    // read files into json objects
    $s0 = json_decode(file_get_contents("results/" . $folder . "/0_{$key}.json"));
    $s1 = json_decode(file_get_contents("results/" . $folder . "/1_{$key}.json"));

    // Triple count in each representative set
    $counts['tripleCount'][0][$key] = count($s0);
    $counts['tripleCount'][1][$key] = count($s1);
    // analyse focus -> ref links
    $LinkedPred[$key] = array();

    // Check for links, push links into $LinkedPred
    foreach ($s0 as $triple0) {
        $p0 = prefixed($triple0->predicate);
        // Count distinct predicates for Reference set (for I2 later)
        isset($counts[0][$p0][$key]) ? $counts[0][$p0][$key]++ : ($counts[0][$p0][$key] = 1);
  
        foreach ($s1 as $triple1) {
            $p1 = prefixed($triple1->predicate);

            $v = valMatch($triple0->object, $triple1->object, $objectSymMethod);
            $t = typeMatch($triple0->objectMeta, $triple1->objectMeta);
            if($v && $t) $LinkedPred[$key][] = array($p0, $p1, $t, $v);
        }
    }

    // analyse ref -> focus links
    foreach ($s1 as $triple1) {
        $p1 = prefixed($triple1->predicate);
        // Count distinct predicates for Reference set (for I2 later)
        isset($counts[1][$p1][$key]) ? $counts[1][$p1][$key]++ : ($counts[1][$p1][$key] = 1);
    }

}
$p = fopen("results/links/" . $_REQUEST['folder'] . "/" . $_REQUEST['method'] . "/feat.json", "w");
$h = fopen("results/links/" . $_REQUEST['folder'] . "/" . $_REQUEST['method'] . "/heat.json", "w");
$c = fopen("results/links/" . $_REQUEST['folder'] . "/" . $_REQUEST['method'] . "/predBox.json", "w");
$feat = array();
$heater = array();
$predBox = array();
// Now all links are stored in $LinkedPred[$w_val][$tau_o] (t1, t2, sym_o)
// we analyse for each w_val, tau_o, tau_avg and progressive filecount
for ($w_val = 0; $w_val <= 1; $w_val += 0.25) {
    for ($tau_o = 0; $tau_o < 1; $tau_o += 0.25) {
        for ($tau_avg = 0; $tau_avg < 1; $tau_avg += 0.25) {
            $predFeatureTensor  = array();
            $sym_p              = array();

            $refLocalKeys = array();
            $focLocalKeys = array();
            $refCumulativeKeys = array();
            $focCumulativeKeys = array();
            $sublinkCumulativeCount = array();        // to avoid starting from -1 with the cumulative count
            $counter = 0;

            $scores = array();

            foreach ($LinkedPred as $cbdID => $sublinks) {
                $focLocalKeys[$cbdID] = array();
                $refLocalKeys[$cbdID] = array();
                foreach ($sublinks as $sublink) {
                    $t = $sublink[2];
                    $v = $sublink[3];

                    $sym_o = (1-$w_val) * $t + $w_val * $v;
                    if($sym_o > $tau_o){
                        $p0 = $sublink[0];
                        $p1 = $sublink[1];

                        // Useful cumulative index
                        $focLocalKeys[$cbdID][$p0] = 0;
                        $refLocalKeys[$cbdID][$p1] = 0;

                        // Sum of object similarities (QoL) for p0-p1 couples in the current CBD
                        isset($scores[$cbdID][$p0 . "%%" . $p1]['I1']) ? ($scores[$cbdID][$p0 . "%%" . $p1]['I1'] += $sym_o)  : ($scores[$cbdID][$p0 . "%%" . $p1]['I1'] = $sym_o);
                        // Count of possible p0-p1 links for the current CBD
                        $scores[$cbdID][$p0 . "%%" . $p1]['I2'] = $counts[0][$p0][$cbdID] * $counts[1][$p1][$cbdID];
                        // Number of p0-p1 links in the current CBD
                        isset($scores[$cbdID][$p0 . "%%" . $p1]['I3']) ? ($scores[$cbdID][$p0 . "%%" . $p1]['I3']++)          : ($scores[$cbdID][$p0 . "%%" . $p1]['I3'] = 1);

                    }
                }
            }

            
            for($cbdID = 0; $cbdID < $fileCount; $cbdID++){
                // Cumulative loop, to calculate the effect of link count on indicators
                $couples = 0;
                $sublinkCumulativeCount[$cbdID] = $counter;
                $scores[$cbdID]['I5'] = 0;

                $refCumulativeKeys    = array_unique(array_merge($refCumulativeKeys, array_keys($refLocalKeys[$cbdID])));
                $focCumulativeKeys    = array_unique(array_merge($focCumulativeKeys, array_keys($focLocalKeys[$cbdID])));
                $exportKeys           = array();
                $exportKeys['foc']           = array();
                $exportKeys['ref']           = array();
                $heat = array();
                $heat['sameURI'] = "";
                $heat['MU1_L'] = "";
                $heat['MU4_L'] = "";
                $heat['MU5_L'] = "";
                $heat['sym_p'] = "";

                // Only existing ref and foc hereby are accounted, with sliced focus and reference scores by CBD cumulative count
                foreach ($refCumulativeKeys as $ref) {
                    foreach ($focCumulativeKeys as $foc) {
                        $v1 = isset($predFeatureTensor[$foc . "%%" . $ref]['sem']['MU1_L']) ? $predFeatureTensor[$foc . "%%" . $ref]['sem']['MU1_L'] : 0;
                        $v4 = isset($predFeatureTensor[$foc . "%%" . $ref]['sem']['MU4_L']) ? $predFeatureTensor[$foc . "%%" . $ref]['sem']['MU4_L'] : 0;
                        $vi1_3 = isset($scores[$cbdID][$foc . "%%" . $ref]['I1']) ? ($scores[$cbdID][$foc . "%%" . $ref]['I1']/ $scores[$cbdID][$foc . "%%" . $ref]['I3']) : 0;
                        $vi3 = isset($scores[$cbdID][$foc . "%%" . $ref]['I3']) ? ($scores[$cbdID][$foc . "%%" . $ref]['I3']) : 0;
                        $vi3_2 = isset($scores[$cbdID][$foc . "%%" . $ref]['I2']) ? ($scores[$cbdID][$foc . "%%" . $ref]['I3'] / $scores[$cbdID][$foc . "%%" . $ref]['I2']) : 0;

                        $checkTau = ($v1 * $cbdID + $vi1_3) / ($cbdID + 1);

                        if($checkTau >= $tau_avg){

                            $sublinkCumulativeCount[$cbdID] += $vi3;
                            $counter = $sublinkCumulativeCount[$cbdID];
                            $scores[$cbdID]['I5'] += $vi3;

                            $predBox["{$tau_o}_{$tau_avg}_{$w_val}"][$foc . "%%" . $ref]['R3'][$cbdID] = round($vi1_3,4);
                            $predBox["{$tau_o}_{$tau_avg}_{$w_val}"][$foc . "%%" . $ref]['R2'][$cbdID] = round($vi3_2,4);
                            $predFeatureTensor[$foc . "%%" . $ref]['sem']['sameURI']= ($foc == $ref) ? 1 : 0;
                            $predFeatureTensor[$foc . "%%" . $ref]['sem']['MU1_L']  = $checkTau;
                            $predFeatureTensor[$foc . "%%" . $ref]['sem']['MU4_L']  = ($v4 * $cbdID + $vi3_2)   / ($cbdID + 1);
                            $exportKeys['foc'][] = $foc;
                            $exportKeys['ref'][] = $ref;
                        }  
                    }
                }

                $focLocal = array_unique($exportKeys['foc']);
                $refLocal = array_unique($exportKeys['ref']);

                foreach ($refLocal as $ref) {
                    $heat['sameURI'] .= "[";
                    $heat['MU1_L'] .= "[";
                    $heat['MU4_L'] .= "[";
                    $heat['MU5_L'] .= "[";
                    $heat['sym_p'] .= "[";

                    foreach ($focLocal as $foc) {
                        if(isset($predFeatureTensor[$foc . "%%" . $ref]['sem']['MU1_L'])){
                            $v5 = isset($predFeatureTensor[$foc . "%%" . $ref]['sem']['MU5_L']) ? $predFeatureTensor[$foc . "%%" . $ref]['sem']['MU5_L'] : 0;
                            $vi3_5 = isset($scores[$cbdID][$foc . "%%" . $ref]['I3']) ? ($scores[$cbdID][$foc . "%%" . $ref]['I3']/ $scores[$cbdID]['I5']) : 0;
                            
                            $predFeatureTensor[$foc . "%%" . $ref]['sem']['MU5_L']  = ($v5 * $cbdID + $vi3_5) / ($cbdID + 1);
                            $predBox["{$tau_o}_{$tau_avg}_{$w_val}"][$foc . "%%" . $ref]['R1'][$cbdID] = round($vi3_5, 4);

                            $sym_p[$foc][$ref] = round(dotProduct($predFeatureTensor[$foc . "%%" . $ref]['sem'], $semanticWeights), 4);

                            if($sym_p[$foc][$ref]>0){
                                $couples++;
                                $heat['sym_p']   = $heat['sym_p'] . $sym_p[$foc][$ref] . ",";
                            } else {
                                $heat['sym_p'] .= "null,";
                            }
                            $heat['sameURI'] = $heat['sameURI'] . $predFeatureTensor[$foc . "%%" . $ref]['sem']['sameURI'] . ",";
                            $heat['MU1_L']   = $heat['MU1_L'] . round($predFeatureTensor[$foc . "%%" . $ref]['sem']['MU1_L'], 6) . ",";
                            $heat['MU4_L']   = $heat['MU4_L'] . round($predFeatureTensor[$foc . "%%" . $ref]['sem']['MU4_L'], 4) . ",";
                            $heat['MU5_L']   = $heat['MU5_L'] . round($predFeatureTensor[$foc . "%%" . $ref]['sem']['MU5_L'], 4) . ",";

                        } else {
                            $heat['sameURI'] .= "null,";
                            $heat['MU1_L'] .= "null,";
                            $heat['MU4_L'] .= "null,";
                            $heat['MU5_L'] .= "null,";
                            $heat['sym_p'] .= "null,";
                        }
                    }

                    $heat['sameURI'] .= "],";
                    $heat['MU1_L'] .= "],";
                    $heat['MU4_L'] .= "],";
                    $heat['MU5_L'] .= "],";
                    $heat['sym_p'] .= "],";
                }

                // End of Analysis for the Cumulation of [0-cbdID] indices
                // Dump data for the current analysis, including, w_val, tau_l, cbdCount (avg will be added during post treatement)

                // Dump numerical data for reuse
                


                //fwrite($p, json_encode(array("data" => $predFeatureTensor, "foc" => $focCumulativeKeys, "ref" => $refCumulativeKeys)));

                $feat["{$cbdID}_{$tau_o}_{$tau_avg}_{$w_val}"] = array("totalLinks" => $sublinkCumulativeCount[$cbdID], "CoupleNumber" => $couples, "couples");
                $heater["{$cbdID}_{$tau_o}_{$tau_avg}_{$w_val}"] = array("data" => $heat, "foc" => $focLocal, "ref" => $refLocal);
                // Dump heatmaps as strings (for easy loading with js)
                //$h = fopen("results/links/" . $_REQUEST['folder'] . "/" . $_REQUEST['method'] . "/heat_{$cbdID}_{$tau_o}_{$tau_avg}_{$w_val}.json", "w");
                //fwrite($h, json_encode(array("data" => $heat, "foc" => $focLocal, "ref" => $refLocal)));
                //fclose($h);


            }
        }
        echo "Progress: ".round($w_val*88+$tau_o*13.33,2)."%<br/>";
        ob_flush();
        flush();
    }
}
fwrite($p, json_encode($feat));
fwrite($h, json_encode($heater));
fwrite($c, json_encode($predBox));
fclose($p);
fclose($h);
fclose($c);
ob_end_flush();
echo "Progress: 100% .. done<br/>";
//session_destroy();
?>