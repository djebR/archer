<?php

if (!isset($_REQUEST['analyse'])) {
    // Specific parameter analysis
    $tau_o = $_REQUEST['tau_l'];
    $tau_avg = $_REQUEST['tau_g'];
    $w_val = $_REQUEST['w_val'];
    $folder = $_REQUEST['folder'];
    $method = $_REQUEST['method'];
    $cbdID    = $_REQUEST['key'];

    if (!file_exists("../results/links/{$folder}/{$method}/heat_{$cbdID}_{$tau_o}_{$tau_avg}_{$w_val}.json")) {
        die("No such file for the selected parameters.");
    }
    $json = json_decode(file_get_contents("../results/links/{$folder}/{$method}/heat_{$cbdID}_{$tau_o}_{$tau_avg}_{$w_val}.json"), true);

    $heat = $json['data'];
    $focusKeys = json_encode(array_values($json['foc']));
    $refKeys = json_encode(array_values($json['ref']));

?>
    <div class='row p-3'>
        <div class='col-4'>
            <div id='sym_p'></div>
        </div>
        <div class='col-8'>
            <div class='card card-body fixedBot'>
                <p>The uncertainty of each focus graph \(PSI_r(G_f)\) is calculated by: averaging the sum of products (object similarity X predicate similarity) for each evidence link. </p>
                <p>The uncertainty of each context is calculated by: averaging the sum of products (object similarity X predicate similarity) for each evidence link. </p>
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
    </script>

<?php
} else {
    // Show Parameter effect on indicators
    $folder = $_REQUEST['folder'];
    $method = $_REQUEST['method'];
    $tau_l  = $_REQUEST['tau_l'];
    $tau_g  = $_REQUEST['tau_g'];
    $w_val  = $_REQUEST['w_val'];

    $fileCount = floor(count(glob("../results/" . $folder . "/*.json")) / 2) - 1;
    $linkNumber = array();
    $CoupleNumber = array();
    $dd = array();
    $dd2 = array();

    for ($key = 0; $key <= $fileCount; $key++) {
        $json = json_decode(file_get_contents("../results/links/{$folder}/{$method}/feat_{$key}_{$tau_l}_{$tau_g}_{$w_val}.json"), true);

        $linkNumber[$key + 1]     = $json['totalLinks'];
        $CoupleNumber[$key + 1]   = $json['CoupleNumber'];
    }

    for ($tau_o = 0; $tau_o < 1; $tau_o += 0.25) {
        $row = array();
        $row2 = array();
        for ($tau_avg = 0; $tau_avg < 1; $tau_avg += 0.25) {
            $json = json_decode(file_get_contents("../results/links/{$folder}/{$method}/feat_{$fileCount}_{$tau_o}_{$tau_avg}_1.json"), true);

            $row[]     = $json['totalLinks'];
            $row2[]   = $json['CoupleNumber'];
        }
        $dd[] = $row;
        $dd2[] = $row2;
    }

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

    <script>
        var data_cbdEffectLN = [{
            x: <?php echo json_encode(array_keys($linkNumber)); ?>,
            y: <?php echo json_encode(array_values($linkNumber)); ?>,
            mode: 'lines+markers',
            marker: {
                color: 'rgb(128, 0, 128)',
                size: 8
            },
            line: {
                color: 'rgb(128, 0, 128)',
                width: 1
            }
        }];
        data_cbdEffectLN[0]['x'].unshift(0);
        data_cbdEffectLN[0]['y'].unshift(0);
        var Lay_cbdEffectLN = {
            title: 'Number of Evidence links per Analysed Resources<br><span style="font-size:10px;"></span>',
            margin: {
                l: 150,
                b: 50
            }
        };

        Plotly.newPlot('cbdEffectLN', data_cbdEffectLN, Lay_cbdEffectLN, {
            responsive: true
        });

        var data_cbdEffectCpN = [{
            x: <?php echo json_encode(array_keys($CoupleNumber)); ?>,
            y: <?php echo json_encode(array_values($CoupleNumber)); ?>,
            mode: 'lines+markers',
            marker: {
                color: 'rgb(128, 0, 128)',
                size: 8
            },
            line: {
                color: 'rgb(128, 0, 128)',
                width: 1
            }
        }];
        data_cbdEffectCpN[0]['x'].unshift(0);
        data_cbdEffectCpN[0]['y'].unshift(0);
        var Lay_cbdEffectCpN = {
            title: 'Number of Predicate-Couples per Analysed Resources <br><span style="font-size:10px;"></span>',
            margin: {
                l: 150,
                b: 50
            }
        };

        Plotly.newPlot('cbdEffectCpN', data_cbdEffectCpN, Lay_cbdEffectCpN, {
            responsive: true
        });


        var data = [{
            z: <?php echo json_encode($dd); ?>,
            x: [0, 0.25, 0.5, 0.75],
            y: [0, 0.25, 0.5, 0.75],
            type: 'surface'
        }];

        var layout = {
            title: 'LinkCount to local and avg thresholds',
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
            title: 'PredCoupleCount to local and avg thresholds',
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
    </script>

<?php
}


?>