<?php

//session_start();
ini_set('xdebug.var_display_max_depth', '10');
ini_set('xdebug.var_display_max_children', '256');
ini_set('xdebug.var_display_max_data', '90000');
include('assets/jaro.php');
ini_set('max_execution_time', '0');

if (ob_get_level() == 0) ob_start();


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
// --------------- Helper functions ---------------

// prefixed: give the prefixed version of a uri,
// example: for "http://www.w3.org/1999/02/22-rdf-syntax-ns#type" returns "rdf:type"
function prefixed($uri)
{
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
    if (isset($parsed['fragment']) && $parsed['fragment'] != "") {
        // anchored
        $base = $parsed['fragment'];
    } else {
        // slashed
        $base = basename($uri);
    }

    $rest = substr($uri, 0, -strlen($base));
    return $prefixDB[$rest] . ":" . $base;
}

/**
 * dotProduct: dot product of two vectors (normal or associative)
 * 
 * @param array $v1 first vector
 * @param array $v2 second vector
 * 
 * @return double dot product of the two vectors
 */
function dotProduct($v1, $v2)
{
    $sum = 0;

    foreach ($v1 as $index => $value) {
        $sum += $value * $v2[$index];
    }
    return $sum;
}

// magnitude: sqrt(sum(vector with each element to the power of 2))
function magnitude($v)
{
    $squares = array_map(function ($x) {
        return pow($x, 2);
    }, $v);
    return sqrt(array_reduce($squares, function ($a, $b) {
        return $a + $b;
    }));
}

// cosine: dot(a,b)/(magnitude(a) * magnitude(b))
function cosine($v1, $v2)
{
    // Calculate the cosine similarity between $v1 and $v2 using predefined weights
    return round(dotProduct($v1, $v2) / (magnitude($v1) * magnitude($v2)), 3);
}

// valMatch: syntactic similarity between two "serialized" objects
function valMatch($s1, $s2, $type = 'default', $point = 4)
{
    $sim = 0;
    switch ($type) {
        case 'jaccard':
            $ss1 = str_split($s1);
            $ss2 = str_split($s2);
            $arr_intersection   = array_intersect($ss1, $ss2);
            $arr_union          = array_merge($ss1, $ss2);
            $sim                = 2 * count($arr_intersection) / count($arr_union);
            break;
        case 'hamming':
            $l1 = strlen($s1);
            $l2 = strlen($s2);
            $l = min($l1, $l2);
            $d = 0;
            for ($i = 0; $i < $l; $i++) {
                $d += (int) ($s1[$i] != $s2[$i]);
            }

            $sim = $d + abs($l1 - $l2);
            break;
        case 'jaro-winkler':
            $sim = JaroWinkler($s1, $s2);
            break;
        case 'default':
            // full string match
            $sim = (strtolower($s1) == strtolower($s2)) ? 1 : 0;
            break;
    }
    return round($sim, $point);
}

// typeMatch: type similarity between two objects (extension type)
function typeMatch($objMeta1, $objMeta2)
{
    $result = 0;
    $a = ($objMeta1->type == "uri");
    $b = ($objMeta2->type == "uri");
    $c = (isset($objMeta1->datatype) && isset($objMeta2->datatype)) ? ($objMeta1->datatype == $objMeta2->datatype) : false;

    if (($a && $b) || (!$a && !$b && $c)) $result = 1;
    return $result;
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
                            $couples++;

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

                            $sym_p[$foc][$ref] = round(dotProduct($predFeatureTensor[$foc . "%%" . $ref]['sem'], $semanticWeights), 4);

                            $heat['sameURI'] = $heat['sameURI'] . $predFeatureTensor[$foc . "%%" . $ref]['sem']['sameURI'] . ",";
                            $heat['MU1_L']   = $heat['MU1_L'] . round($predFeatureTensor[$foc . "%%" . $ref]['sem']['MU1_L'], 6) . ",";
                            $heat['MU4_L']   = $heat['MU4_L'] . round($predFeatureTensor[$foc . "%%" . $ref]['sem']['MU4_L'], 4) . ",";
                            $heat['MU5_L']   = $heat['MU5_L'] . round($predFeatureTensor[$foc . "%%" . $ref]['sem']['MU5_L'], 4) . ",";
                            $heat['sym_p']   = $heat['sym_p'] . $sym_p[$foc][$ref] . ",";

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
                


                $p = fopen("results/links/" . $_REQUEST['folder'] . "/" . $_REQUEST['method'] . "/feat_{$cbdID}_{$tau_o}_{$tau_avg}_{$w_val}.json", "w");
                fwrite($p, json_encode(array("totalLinks" => $sublinkCumulativeCount[$cbdID], "CoupleNumber" => $couples)));
                //fwrite($p, json_encode(array("data" => $predFeatureTensor, "foc" => $focCumulativeKeys, "ref" => $refCumulativeKeys)));
                fclose($p);

                // Dump heatmaps as strings (for easy loading with js)
                $h = fopen("results/links/" . $_REQUEST['folder'] . "/" . $_REQUEST['method'] . "/heat_{$cbdID}_{$tau_o}_{$tau_avg}_{$w_val}.json", "w");
                fwrite($h, json_encode(array("data" => $heat, "foc" => $focLocal, "ref" => $refLocal)));
                fclose($h);


            }
        }
        echo "Progress: ".round($w_val*88+$tau_o*13.33,2)."%<br/>";
        ob_flush();
        flush();
    }
}

ob_end_flush();
echo "Progress: 100% .. done<br/>";
//session_destroy();
?>