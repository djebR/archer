<?php

    header('Content-Type: application/json');
    
    
    if(isset($_REQUEST['key']) && $_REQUEST['key'] >= 0 && file_exists("results/0_".$_REQUEST['key'].".json") && file_exists("results/1_".$_REQUEST['key'].".json")){

        $key = $_REQUEST['key'];
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

        $data = array(
            "links" => $count,
            "nodesFrom" => $linked0,
            "nodesTo" => $linked1,
            "possibleLinkedPred" => $possibleLinks
        );
        echo json_encode($data);
    }
    else
    {

        $totalTriples0 = 0;
        $totalTriples1 = 0;
        $totalLinkedNodes0 = 0;
        $totalLinkedNodes1 = 0;
        $zeroResources0 = 0;
        $zeroResources1 = 0;
        $zeroResourcesTriples0 = 0;
        $zeroResourcesTriples1 = 0;
        $maxNodes0 = 0;
        $maxNodes1 = 0;
        $LinkedPred = array();

        $fileCount = floor(count(glob("results/*.json"))/2);
        for($key = 0; $key < $fileCount; $key++){

            // read files into json objects
            $s0 = json_decode(file_get_contents("results/0_{$key}.json"));
            $s1 = json_decode(file_get_contents("results/1_{$key}.json"));

            $oldTotal = $totalLinkedNodes0;
            // analyse from -> to links
            foreach ($s0 as $triple0) {
                $boo = false;
                foreach ($s1 as $triple1) {
                    if(strtolower($triple1->object) == strtolower($triple0->object)){
                        $boo = true;
                        $LinkedPred[] = array($triple0->predicate, $triple1->predicate);
                    }
                }
                if($boo) $totalLinkedNodes0 += 1;
            }
            $totalTriples0 += count($s0);
            if($oldTotal == $totalLinkedNodes0){
                $zeroResources0++;
                $zeroResourcesTriples0 += count($s0);
            } 

            $oldTotal = $totalLinkedNodes1;
            // analyse to -> from links
            foreach ($s1 as $triple1) {
                foreach ($s0 as $triple0) {
                    if(strtolower($triple1->object) == strtolower($triple0->object)) {
                        $totalLinkedNodes1 += 1;
                        break;
                    }
                }
            }
            $totalTriples1 += count($s1);

            if($oldTotal == $totalLinkedNodes1){
                $zeroResources1++;
                $zeroResourcesTriples1 += count($s1);
            } 

        }

        $arr = array();

        foreach ($LinkedPred as $item) {
            if(isset($arr[$item[0].$item[1]])){
                $arr[$item[0].$item[1]][2]++; // the count is in the third column
            } else {
                $arr[$item[0].$item[1]][0] = $item[0];
                $arr[$item[0].$item[1]][1] = $item[1];
                $arr[$item[0].$item[1]][2] = 1;
            }
        }

        $pred1 = array_column($arr, 0);
        $pred2 = array_column($arr, 1);
        $predCount = array_column($arr, 2);

        array_multisort($predCount, SORT_DESC, $pred1, SORT_DESC, $pred2, SORT_DESC, $arr);

        $data = array(
            "totalTriples0" => $totalTriples0,
            "totalTriples1" => $totalTriples1,
            "totalLinkedNodes0" => $totalLinkedNodes0,
            "totalLinkedNodes1" => $totalLinkedNodes1,
            "zeroResources0" => $zeroResources0,
            "zeroResources1" => $zeroResources1,
            "zeroResourcesTriples0" => $zeroResourcesTriples0,
            "zeroResourcesTriples1" => $zeroResourcesTriples1,
            "maxNodes0" => $maxNodes0,
            "maxNodes1" => $maxNodes1,
            "LinkedPred" => $arr
        );
        echo json_encode($data);

    }

?>