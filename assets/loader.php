<?php

if (!isset($_REQUEST['analyse'])) {
    // Specific parameter analysis
    $tau_o = $_REQUEST['tau_l'];
    $tau_avg = $_REQUEST['tau_g'];
    $w_val = $_REQUEST['w_val'];
    $folder = $_REQUEST['f'];
    $method = $_REQUEST['method'];
    $cbdID    = $_REQUEST['key'];

    if (!file_exists("../results/{$folder}/{$method}/heat.json")) {
        die("No such file for the selected parameters.");
    }
    $json = json_decode(file_get_contents("../results/{$folder}/{$method}/heat.json"), true);

    $heat = $json["{$cbdID}_{$tau_o}_{$tau_avg}_{$w_val}"]['data'];
    $focusKeys = json_encode(array_values($json["{$cbdID}_{$tau_o}_{$tau_avg}_{$w_val}"]['foc']));
    $refKeys = json_encode(array_values($json["{$cbdID}_{$tau_o}_{$tau_avg}_{$w_val}"]['ref']));

?>
    <div class='row p-3'>
        <div class='col-4'>
            <div id='sym_p'></div>
        </div>
        <div class='col-8'>
            <div class='card card-body fixedBot'>
                <p>The uncertainty of each focus graph \(PSI_r(G_f)\) is calculated by: averaging the sum of products (object similarity X predicate similarity) for each evidence link. </p>
                <p>The uncertainty of each context is calculated by: averaging the sum of products (object similarity X predicate similarity) for each evidence link. </p>
                <div class="form-group">
                    <label for="sym_p_range">Predicate similarity threshold : <span id="sym_p_val"></span></label>
                    <input type="range" class="form-control-range" id="sym_p_range" min="0" max="1" step="0.001">
                </div>
            </div>
        </div>
    </div>
    <div class='clearfix'></div>
    <div class='card card-body'>
        <a data-toggle='collapse' href='#collapseScore' role='button' aria-expanded='false' aria-controls='collapseScore'>Detailed Scores</a>
        <div class='collapse row' id='collapseScore'>
            <div class='col-6'>
                <div id='MU1_L'></div>
            </div>
            <div class='col-6'>
                <div id='sameURI'></div>
            </div>
            <div class="w-100"></div>
            <div class='col-6'>
                <div id='MU4_L'></div>
            </div>
            <div class='col-6'>
                <div id='MU5_L'></div>
            </div>
        </div>
    </div>

    <script>
        var data_sameURI = [{
            z: [<?php echo $heat['sameURI']; ?>],
            x: <?php echo $focusKeys; ?>,
            y: <?php echo $refKeys; ?>,
            type: 'heatmap',
            hoverongaps: false,
            xgap: 3,
            ygap: 3,
            zauto: false,
            zmin: 0,
            zmax: 1,
            colorscale: [
                [0, '#ee5253'],
                [1, '#10ac84']
            ]
        }];
        var Lay_sameURI = {
            title: 'sameURI <br><span style="font-size:10px;">Sum(AvgSimPerPredCoupleCBD)/Sum(NumberofLinks)</span>',
            margin: {
                l: 150,
                b: 50
            }
        };
        Plotly.newPlot('sameURI', data_sameURI, Lay_sameURI, {
            responsive: true
        });

        var data_MU1_L = [{
            z: [<?php echo $heat['MU1_L']; ?>],
            x: <?php echo $focusKeys; ?>,
            y: <?php echo $refKeys; ?>,
            type: 'heatmap',
            hoverongaps: false,
            xgap: 3,
            ygap: 3,
            zauto: false,
            zmin: <?php echo $tau_avg; ?>,
            zmax: 1,
            colorscale: [
                [0, '#ee5253'],
                [1, '#10ac84']
            ]
        }];
        var Lay_MU1_L = {
            title: 'MU1_L <br><span style="font-size:10px;">Sum(localAvgSimPerPred/localSublink)/TotalNumCBDs</span>',
            margin: {
                l: 150,
                b: 50
            }
        };
        Plotly.newPlot('MU1_L', data_MU1_L, Lay_MU1_L, {
            responsive: true
        });

        var data_MU4_L = [{
            z: [<?php echo $heat['MU4_L']; ?>],
            x: <?php echo $focusKeys; ?>,
            y: <?php echo $refKeys; ?>,
            type: 'heatmap',
            hoverongaps: false,
            xgap: 3,
            ygap: 3,
            colorscale: [
                [0, '#ee5253'],
                [1, '#10ac84']
            ]

        }];
        var Lay_MU4_L = {
            title: 'MU4_L <br><span style="font-size:10px;"></span>',
            margin: {
                l: 150,
                b: 50
            }
        };

        Plotly.newPlot('MU4_L', data_MU4_L, Lay_MU4_L, {
            responsive: true
        });

        var data_MU5_L = [{
            z: [<?php echo $heat['MU5_L']; ?>],
            x: <?php echo $focusKeys; ?>,
            y: <?php echo $refKeys; ?>,
            type: 'heatmap',
            hoverongaps: false,
            xgap: 3,
            ygap: 3,
            colorscale: [
                [0, '#ee5253'],
                [1, '#10ac84']
            ]

        }];
        var Lay_MU5_L = {
            title: 'MU5_L <br><span style="font-size:10px;"></span>',
            margin: {
                l: 150,
                b: 50
            }
        };

        Plotly.newPlot('MU5_L', data_MU5_L, Lay_MU5_L, {
            responsive: true
        });

        var data_sym_p = [{
            z: [<?php echo $heat['sym_p']; ?>],
            x: <?php echo $focusKeys; ?>,
            y: <?php echo $refKeys; ?>,
            type: 'heatmap',
            hoverongaps: false,
            xgap: 3,
            ygap: 3,
            zauto: false,
            zmin: 0,
            zmax: 1,

            colorscale: [
                [0, '#ee5253'],
                [1, '#10ac84']
            ]

        }];
        var Lay_sym_p = {
            title: 'sym_p <br><span style="font-size:10px;"></span>',
            margin: {
                l: 150,
                b: 50
            }
        };

        Plotly.newPlot('sym_p', data_sym_p, Lay_sym_p, {
            responsive: true
        });
        var back = [<?php echo $heat['sym_p']; ?>];

        $('#sym_p_range').on('change', function() {

            var result = [];
            var vald = $('#sym_p_range').val();
            $('#sym_p_val').html(vald);
            console.log(data_sym_p[0]['z']);
            for (var i = 0; i < back.length; i++) {
                result[i] = new Array(back[0].length).fill(null);

                for (var j = 0; j < back[0].length; j++) {
                    if (back[i][j] > vald) result[i][j] = back[i][j]; // Here is the fixed column access using the outter index i.
                }
            }
            data_sym_p[0]['z'] = result;
            console.log(data_sym_p[0]['z']);

            Plotly.newPlot('sym_p', data_sym_p, Lay_sym_p, {
                responsive: true
            });
        });
    </script>

<?php
} else {
    // Show Parameter effect on indicators
    $folder = $_REQUEST['f'];
    $method = $_REQUEST['method'];
    $tau_l  = $_REQUEST['tau_l'];
    $tau_g  = $_REQUEST['tau_g'];
    $w_val  = $_REQUEST['w_val'];

    $methodName = array(
        'jaccard' => 'jaccard (chars)',
        'jaccard-word' => 'jaccard (words)',
        'hamming' => 'hamming',
        'jaro-winkler' => 'jaro-winkler',
        'default' => 'String equality'
    );
    $meta = json_decode(file_get_contents("../results/meta_{$folder}.json"), true);

    $fileCount = $meta['realised'] - 1;
    $linkNumber = array();
    $CoupleNumber = array();
    $dd = array();
    $dd2 = array();
    $json = json_decode(file_get_contents("../results/{$folder}/{$method}/feat.json"), true);
    $jsonPred = json_decode(file_get_contents("../results/{$folder}/{$method}/predBox.json"), true);

    // Prepare data 

    for ($key = 0; $key <= $fileCount; $key++) {
        $linkNumber[0][$key + 1]     = $json["{$key}_0_{$tau_g}_{$w_val}"]['totalLinks'];
        $CoupleNumber[0][$key + 1]   = $json["{$key}_0_{$tau_g}_{$w_val}"]['CoupleNumber'];
        $linkNumber[1][$key + 1]     = $json["{$key}_0.25_{$tau_g}_{$w_val}"]['totalLinks'];
        $CoupleNumber[1][$key + 1]   = $json["{$key}_0.25_{$tau_g}_{$w_val}"]['CoupleNumber'];
        $linkNumber[2][$key + 1]     = $json["{$key}_0.5_{$tau_g}_{$w_val}"]['totalLinks'];
        $CoupleNumber[2][$key + 1]   = $json["{$key}_0.5_{$tau_g}_{$w_val}"]['CoupleNumber'];
        $linkNumber[3][$key + 1]     = $json["{$key}_0.75_{$tau_g}_{$w_val}"]['totalLinks'];
        $CoupleNumber[3][$key + 1]   = $json["{$key}_0.75_{$tau_g}_{$w_val}"]['CoupleNumber'];
    }

    for ($tau_o = 0; $tau_o < 1; $tau_o += 0.25) {
        $row = array();
        $row2 = array();
        for ($tau_avg = 0; $tau_avg < 1; $tau_avg += 0.25) {

            $row[]     = $json["{$fileCount}_{$tau_o}_{$tau_avg}_{$w_val}"]['totalLinks'];
            $row2[]   = $json["{$fileCount}_{$tau_o}_{$tau_avg}_{$w_val}"]['CoupleNumber'];
        }
        $dd[] = $row;
        $dd2[] = $row2;
    }

    $dataPred = array();
    $coupleCounter = count($jsonPred["{$tau_l}_{$tau_g}_{$w_val}"]);
    // Loop through pred-couple indicators
    $i = 1;
    foreach ($jsonPred["{$tau_l}_{$tau_g}_{$w_val}"] as $couple => $predArray) {
        $color1 = round(255 / $coupleCounter * $i, 0);
        $color2 = 255 - $color1;
        $dataPred['R1'][] = "{y: " . json_encode(array_values($predArray['R1'])) . ",type:'box',name:'" . $couple . "',marker:{color:'rgb(" . $color1 . "," . $color2 . ",255)'},boxmean:true}";
        $dataPred['R2'][] = "{y: " . json_encode(array_values($predArray['R2'])) . ",type:'box',name:'" . $couple . "',marker:{color:'rgb(" . $color1 . "," . $color2 . ",255)'},boxmean:true}";
        $dataPred['R3'][] = "{y: " . json_encode(array_values($predArray['R3'])) . ",type:'box',name:'" . $couple . "',marker:{color:'rgb(" . $color1 . "," . $color2 . ",255)'},boxmean:true}";
        $i++;
    }

    // Print results
?>
    <div class='row p-3'>
        <div class='col-6'>
            <div id='cbdEffectLN'></div>
        </div>
        <div class='col-6'>
            <div id='cbdEffectCpN'></div>
        </div>
    </div>
    <div class='row p-3'>
        <div class='col-6'>
            <div id='plot3d'></div>
        </div>
        <div class='col-6'>
            <div id='plot3d2'></div>
        </div>
    </div>
    <div class='row p-3'>
        <div class='col-12'>
            <div id='R1'></div>
        </div>
        <div class='col-12'>
            <div id='R2'></div>
        </div>
        <div class='col-12'>
            <div id='R3'></div>
        </div>
    </div>

    <script>
        var data_cbd0 = {
            x: <?php echo json_encode(array_keys($linkNumber[0])); ?>,
            y: <?php echo json_encode(array_values($linkNumber[0])); ?>,
            mode: 'scatter',
            name: '$\\tau_{obj} = 0$'
        };
        var data_cbd1 = {
            x: <?php echo json_encode(array_keys($linkNumber[1])); ?>,
            y: <?php echo json_encode(array_values($linkNumber[1])); ?>,
            mode: 'scatter',
            name: '$\\tau_{obj} = 0.25$'
        };
        var data_cbd2 = {
            x: <?php echo json_encode(array_keys($linkNumber[2])); ?>,
            y: <?php echo json_encode(array_values($linkNumber[2])); ?>,
            mode: 'scatter',
            name: '$\\tau_{obj} = 0.5$'
        };
        var data_cbd3 = {
            x: <?php echo json_encode(array_keys($linkNumber[3])); ?>,
            y: <?php echo json_encode(array_values($linkNumber[3])); ?>,
            mode: 'scatter',
            name: '$\\tau_{obj} = 0.75$'
        };

        data_cbd0['x'].unshift(0);
        data_cbd0['y'].unshift(0);
        data_cbd1['x'].unshift(0);
        data_cbd1['y'].unshift(0);
        data_cbd2['x'].unshift(0);
        data_cbd2['y'].unshift(0);
        data_cbd3['x'].unshift(0);
        data_cbd3['y'].unshift(0);

        var data_cbdEffectLN = [data_cbd0, data_cbd1, data_cbd2, data_cbd3];

        var Lay_cbdEffectLN = {
            title: 'Number of Total Evidence links/|LS(D_t, D_r)|<br><span style="font-size:10px;">valMatch = <?php echo $methodName[$method]; ?>, tau_{sem} = <?php echo $tau_g; ?>, w_{val} = <?php echo $w_val; ?></span>',
            margin: {
                l: 150,
                b: 50
            },
            xaxis: {
                title: '$|LS(D_t, D_r)|$'
            },
            yaxis: {
                title: '$$\\sum{|E(G_{D_t}(e_t), G_{D_r}(e_r))|}$$'

            }
        };

        Plotly.newPlot('cbdEffectLN', data_cbdEffectLN, Lay_cbdEffectLN, {
            responsive: true
        });

        var data_cpn0 = {
            x: <?php echo json_encode(array_keys($CoupleNumber[0])); ?>,
            y: <?php echo json_encode(array_values($CoupleNumber[0])); ?>,
            mode: 'scatter',
            name: '$\\tau_{obj} = 0$'
        };
        var data_cpn1 = {
            x: <?php echo json_encode(array_keys($CoupleNumber[1])); ?>,
            y: <?php echo json_encode(array_values($CoupleNumber[1])); ?>,
            mode: 'scatter',
            name: '$\\tau_{obj} = 0.25$'
        };
        var data_cpn2 = {
            x: <?php echo json_encode(array_keys($CoupleNumber[2])); ?>,
            y: <?php echo json_encode(array_values($CoupleNumber[2])); ?>,
            mode: 'scatter',
            name: '$\\tau_{obj} = 0.5$'
        };
        var data_cpn3 = {
            x: <?php echo json_encode(array_keys($CoupleNumber[3])); ?>,
            y: <?php echo json_encode(array_values($CoupleNumber[3])); ?>,
            mode: 'scatter',
            name: '$\\tau_{obj} = 0.75$'
        };

        data_cpn0['x'].unshift(0);
        data_cpn0['y'].unshift(0);
        data_cpn1['x'].unshift(0);
        data_cpn1['y'].unshift(0);
        data_cpn2['x'].unshift(0);
        data_cpn2['y'].unshift(0);
        data_cpn3['x'].unshift(0);
        data_cpn3['y'].unshift(0);

        var data_cbdEffectCpN = [data_cpn0, data_cpn1, data_cpn2, data_cpn3];

        var Lay_cbdEffectCpN = {
            title: 'Number of Predicate-Couples/|LS(D_t, D_r)|<br><span style="font-size:10px;">valMatch = <?php echo $methodName[$method]; ?>, tau_{sem} = <?php echo $tau_g; ?>, w_{val} = <?php echo $w_val; ?></span>',
            margin: {
                l: 150,
                b: 50
            },
            xaxis: {
                title: '$|LS(D_t, D_r)|$'
            },
            yaxis: {
                title: 'Distinct Predicate-couple count'
            }
        };

        Plotly.newPlot('cbdEffectCpN', data_cbdEffectCpN, Lay_cbdEffectCpN, {
            responsive: true
        });


        var data = [{
            z: <?php echo json_encode($dd); ?>,
            x: [0, 0.25, 0.5, 0.75],
            y: [0, 0.25, 0.5, 0.75],
            type: 'surface',
        }];

        var layout = {
            title: 'sum(|E(G_{D_t}(e_t), G_{D_r}(e_r))|) to thresholds<br><span style="font-size:10px;">valMatch = <?php echo $methodName[$method]; ?>, |LS(D_t, D_r)| = <?php echo $fileCount; ?>, w_{val} = <?php echo $w_val; ?></span>',
            scene: {
                xaxis: {
                    title: 'tau_avg'
                },
                yaxis: {
                    title: 'tau_local'
                },
                zaxis: {
                    title: 'LinkCount'
                },
            },
            autosize: false,
            width: 500,
            height: 500,
            margin: {
                l: 65,
                r: 50,
                b: 65,
                t: 90,
            }
        };
        Plotly.newPlot('plot3d', data, layout);

        var data2 = [{
            z: <?php echo json_encode($dd2); ?>,
            x: [0, 0.25, 0.5, 0.75],
            y: [0, 0.25, 0.5, 0.75],
            type: 'surface'
        }];

        var layout2 = {
            title: 'Predicate-Couple Count to thresholds<br><span style="font-size:10px;">valMatch = <?php echo $methodName[$method]; ?>, |LS(D_t, D_r)| = <?php echo $fileCount; ?>, w_{val} = <?php echo $w_val; ?></span>',
            scene: {
                xaxis: {
                    title: 'tau_avg'
                },
                yaxis: {
                    title: 'tau_local'
                },
                zaxis: {
                    title: 'PredCoupleCount'
                },
            },
            autosize: false,
            width: 500,
            height: 500,
            margin: {
                l: 65,
                r: 50,
                b: 65,
                t: 90,
            }
        };
        Plotly.newPlot('plot3d2', data2, layout2);

        var data_R1 = <?php echo "[" . implode(",", $dataPred['R1']) . "]"; ?>;

        var layout_R1 = {
            title: 'R1(p1,p2) <br><span style="font-size:10px;">valMatch = <?php echo $methodName[$method]; ?>, |LS(D_t, D_r)| = <?php echo $fileCount; ?>, w_{val} = <?php echo $w_val; ?>, tau_{obj} = <?php echo $tau_l; ?>, tau_{sem} = <?php echo $tau_g; ?>, Pred-Couples = <?php echo $coupleCounter; ?></span>'
        };

        Plotly.newPlot('R1', data_R1, layout_R1);

        var data_R2 = <?php echo "[" . implode(",", $dataPred['R2']) . "]"; ?>;

        var layout_R2 = {
            title: 'R2(p1,p2) <br><span style="font-size:10px;">valMatch = <?php echo $methodName[$method]; ?>, |LS(D_t, D_r)| = <?php echo $fileCount; ?>, w_{val} = <?php echo $w_val; ?>, tau_{obj} = <?php echo $tau_l; ?>, tau_{sem} = <?php echo $tau_g; ?>, Pred-Couples = <?php echo $coupleCounter; ?></span>'
        };

        Plotly.newPlot('R2', data_R2, layout_R2);

        var data_R3 = <?php echo "[" . implode(",", $dataPred['R3']) . "]"; ?>;

        var layout_R3 = {
            title: 'R3(p1,p2) <br><span style="font-size:10px;">valMatch = <?php echo $methodName[$method]; ?>, |LS(D_t, D_r)| = <?php echo $fileCount; ?>, w_{val} = <?php echo $w_val; ?>, tau_{obj} = <?php echo $tau_l; ?>, tau_{sem} = <?php echo $tau_g; ?>, Pred-Couples = <?php echo $coupleCounter; ?></span>'
        };

        Plotly.newPlot('R3', data_R3, layout_R3);
    </script>

<?php
}


?>