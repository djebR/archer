<?php

    header('Content-Type: application/json');
    $key = $_REQUEST['key'];

    if(file_exists("results/0_{$key}.json") && file_exists("results/1_{$key}.json")){
        // read files into json objects
        $s0 = json_decode(file_get_contents("results/0_{$key}.json"));
        $s1 = json_decode(file_get_contents("results/1_{$key}.json"));

        $linked0 = 0;
        $linked1 = 0;
        $count = 0;

        // This will contain couples of possible links between predicates: useful for ontology alignment purposes
        $possibleLinks = array();

        // analyse from -> to links
        foreach ($s0 as $triple0) {
            $boo = false;
            foreach ($s1 as $triple1) {
                if(strtolower($triple1->object) == strtolower($triple0->object)){
                    $count += 1;
                    $boo = true;
                    $possibleLinks[] = array($triple0->predicate, $triple1->predicate);
                }
            }
            if($boo) $linked0 += 1;
        }

        // analyse to -> from links
        foreach ($s1 as $triple1) {
            $boo = false;
            foreach ($s0 as $triple0) {
                if(strtolower($triple1->object) == strtolower($triple0->object)) {
                    $linked1 += 1;
                    break;
                }
            }
        }

    }
    else die();

    $data = array(
        "links" => $count,
        "nodesFrom" => $linked0,
        "nodesTo" => $linked1,
        "possibleLinkedPred" => $possibleLinks
    );
    echo json_encode($data);
?>