<?php

    header('Content-Type: application/json');
    
    // Helper functions

    // dotProduct: dot product of two vectors
    function dotProduct($v1, $v2){
        return array_sum(array_map(function($a, $b) {return $a * $b;}, $v1, $v2));
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

    // object similarity: for syntactic similarity between two objects
    function sym_o($s1, $s2, $type = 'default'){
        $sim = 0;
        switch ($type) {
            case 'jaccard':
                # code...
                break;
            case 'levenstein':
                break;
            case 'jaro-winkler':
                break;
            case 'default':
                // full string match
                $sim = (strtolower($s1) == strtolower($s2))?1:0;
                break;
        }
        return $sim;
    }

    if(isset($_REQUEST['key']) && $_REQUEST['key'] >= 0 && file_exists("results/0_".$_REQUEST['key'].".json") && file_exists("results/1_".$_REQUEST['key'].".json")){
        // Analyse one line 

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
                if(sym_o($triple1->object,$triple0->object)){
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
                if(sym_o($triple1->object,$triple0->object)) {
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
        // ********************************************
        //
        //           Complete analysis
        //
        // ********************************************

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
        $hashes = array();
        $counts = array();
        // optional
        $threshold = 0;
        $simm = 0;
        // Weights for semantic similarity: symmetric, reflexive, transitive, functional (assign weights that sum up to 1)
        $Weights = array(0.1,0.1,0.1,0.7);

        // For semantics of each predicate (sym, reflex, trans, funct)
        $predicateSemantics = json_decode(file_get_contents("prerequisits/semantics.json"), true);

        $fileCount = floor(count(glob("results/*.json"))/2);

        for($key = 0; $key < $fileCount; $key++){

            // read files into json objects
            $s0 = json_decode(file_get_contents("results/0_{$key}.json"));
            $s1 = json_decode(file_get_contents("results/1_{$key}.json"));
            
            $oldTotal = $totalLinkedNodes0;
            // analyse from -> to links
            foreach ($s0 as $triple0) {
                isset($counts[0][$key][$triple0->predicate])?$counts[0][$key][$triple0->predicate]++:1;
                $boo = false;
                foreach ($s1 as $triple1) {
                    $simm = sym_o($triple1->object,$triple0->object);
                    if($simm > 0){
                        $boo = true;
                        $LinkedPred[$key][] = array($triple0, $triple1, $simm);
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
                isset($counts[1][$key][$triple1->predicate])?$counts[1][$key][$triple1->predicate]++:1;
                foreach ($s0 as $triple0) {
                    $simm = sym_o($triple1->object,$triple0->object);
                    if($simm > 0){
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

        // LinkedPred = array(CBD_ID, r = array(x1,y1,z1), r' = array(x2,y2,z2), sim_degree(z1, z2))
        // 1 - Syntactic: already done
        // 2 - Sémantique: analyse how many predicates are linked together, and what stats they do share, on the totality of LinkedPred

        // Invert LinkedPred to be indexed by predicate Couples,
        // each element of $SemanticPred contains an array of ('Pred', '$i with $i a CBDiD, in each a set of arrays with the triples of the links (to help with $SemanticTag)')
        foreach ($LinkedPred as $cbdID => $sublinks) {
            foreach ($sublinks as $sublink) {
                $SemanticPred[$sublink[0]->predicate][$sublink[1]->predicate][$cbdID][] = array($sublink[0]->object, $sublink[1]->object, $sublink[2]); //the two objects and their similarity
            }
        }
        /*
                // we need to grab the semantic properties of all distinct predicates, in order to compare them using cosine similarity
                // done either by: providing a schema (mostly rdf, rdfs, foaf, the known ones)
                // analysing the behaviour of each property inside the cbd (this basically means analysing by hand each and every property, based on the whole dataset so that we don't lose semantics, unless if we augment the dataset beforehand)

                // We assume that we have, for each predicate, a vector $Pred[Assoc-PredicateURI] of ones and zeros holding the next properties (symmetric, asymmetric, reflexive, transitive, Functional) issued from analysing the ontologies of the data sources (later for practise, we use existing known ontologies, and we learn for the rest)
                // Example: rdfs:label (0,1,0,0,0)
                //          rdfs:subClass (0,1,1,1,0)

                // Calculate $semanticTag from all triples annotated with that specific property, to know its semantic content
                // Analyse for 5 properties: (symmetric, asymmetric, reflexive, transitive, Functional)
                    // Individual analysis, since we will be comparing two different properties from different data source

                    
                        Hypothesis (for later): 
                        - Functional: owl:maxCardinality = 1; from the same subject
                        - Transitive: find at least three triples where s p o, o p n, s p n
                        - Reflexive: find at least one triple with s p s
                        - symmetric: find at least two triples with s p o, o p s
                        - asymmetric: find no triples with s p o, o p s
            
                        foreach (array_keys($sublink) as $cbdID => $triplesArray) {
                            foreach ($triplesArray as $triples) {
                                $triples[0]->object;
                                $triples[1]->object;
                            }
                        }
                    

                    // for now, if the property doesn't exist in the semantic tag, just ask the sparql endpoint about it
                    // + We can have a ready-to-use json
                    // - we need to query the superproperties as well

                    // Afterwards and using those properties, we can establish the semantics of the sublinks
        */

        foreach ($SemanticPred as $couple => $sublink) {
            if(!isset($hashes[$couple])){
                // Mention the predicate couple
                $hashes[$couple]['preds'] = array($sublink['preds'][0], $sublink['preds'][1]);
                
                // Calculate the indicators
                // I1: 
                // I2:
                // I3:
                // I4:

                // associate the semantic vector to each predicate
                // $v1 = $predicateSemantics[$sublink['preds'][0]];
                // $v2 = $predicateSemantics[$sublink['preds'][1]];
                
                // initialize cbdID keys for each predicate Couple, we can track them later using array_keys
                
                $hashes[$couple]['semSym'] = cosine($v1, $v2);                                         // Similarity between predicate semantics
                
                // Sublink semantic analysis
                foreach ($sublink as $CBD => $CBDtriples) {
                    if($CBD == 'preds') continue;
                    // Statistically see if there is one value per CBD
                }
                
            }
        }
        
        $NumberOfSemanticLinks = count($SemanticPred);

        $data = array(
            "entitiesAnalysed" => $fileCount,
            "totalTriples0" => $totalTriples0,
            "totalTriples1" => $totalTriples1,
            "totalLinkedNodes0" => $totalLinkedNodes0,
            "totalLinkedNodes1" => $totalLinkedNodes1,
            "hashes" => $hashes,
            "count" => $counts
        );
        echo json_encode($data);

    }

?>