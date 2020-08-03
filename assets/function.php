<?php

include('jaro.php');

/**
 * Prefix dictionnary
 */
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


    "http://purl.org/ontology/mo/"                      => "mo",
    "http://purl.org/ontology/mbz#"                     => "mbz",
    "http://purl.org/vocab/bio/0.1/"                    => "bio",
    "http://www.holygoat.co.uk/owl/redwood/0.1/tags/"   => "tags",
    "http://www.lingvoj.org/ontology#"                  => "lingvoj",
    "http://purl.org/vocab/relationship/"               => "rel",
    "http://dbtune.org/musicbrainz/resource/vocab/"     => "vocab",
    "http://dbtune.org/musicbrainz/resource/"           => "dbtunes",
);

/**
 * getInstances: dot product of two vectors (normal or associative)
 * @param string $class first vector
 * @param string $source second vector
 * @param array $parameters second vector
 * @param string $limit second vector
 * @param string $sourceSimilarity second vector
 * @param array $predicates second vector
 * 
 * @return string dot product of the two vectors
 */
function getInstances($class, $source, $parameters, $limit = 500, $sourceSimilarity = null, $predicates){

    $query =
        "SELECT distinct ?source1  " . (!is_null($sourceSimilarity) ? ("?source2") : "") . " WHERE { ?source1 a " . $class . " . " . (!is_null($sourceSimilarity) ? (" ?source1 <http://www.w3.org/2002/07/owl#sameAs> ?source2 . FILTER (CONTAINS(STR(?source2),'" . $sourceSimilarity . "')) ") : "") . " } LIMIT " . $limit;

    $searchUrl = $source . "?" . $parameters['query'] . '=' . urlencode($query);
    if (isset($parameters['format'])) $searchUrl .= '&format=' . $parameters['format'];
    else $searchUrl .= '&format=json';

    return $searchUrl;
}

// Change the CBD according to definition
// We limit the number of triples to 300 so that it doesn't explode
function getCBD($instance, $source, $parameters, $level = 1, $limit = 300, $symmetric = false, $withBlanks = false){

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



// --------------- Helper functions ---------------

// prefixed: give the prefixed version of a uri,
// example: for "http://www.w3.org/1999/02/22-rdf-syntax-ns#type" returns "rdf:type"
function prefixed($uri){
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
    return $GLOBALS['prefixDB'][$rest] . ":" . $base;
}

function deprefix($predicate){
    $arr = explode(":", $predicate);
    $keys = array_search($arr[0], $GLOBALS['prefixDB']);
    return $keys[0].$arr[1];
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
            $arr_intersection   = array_unique(array_intersect($ss1, $ss2));
            $arr_union          = array_unique(array_merge($ss1, $ss2));
            $sim                = count($arr_intersection) / count($arr_union);
            break;
        case 'jaccard-word':
            $ss1 = explode(" ", $s1);
            $ss2 = explode(" ", $s2);
            $arr_intersection   = array_unique(array_intersect($ss1, $ss2));
            $arr_union          = array_unique(array_merge($ss1, $ss2));
            $sim                = count($arr_intersection) / count($arr_union);
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
    $a = ($objMeta1['type'] == "uri");
    $b = ($objMeta2['type'] == "uri");
    $c = (isset($objMeta1['datatype']) && isset($objMeta2['datatype'])) ? ($objMeta1['datatype'] == $objMeta2['datatype']) : false;

    if (($a && $b) || (!$a && !$b && $c)) $result = 1;
    return $result;
}

function printFolder($folder, $tag){
    $fileList = glob($folder . "/*.nt");
    foreach ($fileList as $linkList) {
        echo "<{$tag} value='{$linkList}'>".basename($linkList)."</{$tag}>";
    }
}
?>