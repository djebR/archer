<?php

$tau_o = $_REQUEST['tau_l'];
$tau_avg = $_REQUEST['tau_g'];
$w_val = $_REQUEST['w_val'];
$folder = $_REQUEST['folder'];
$method = $_REQUEST['method'];

$json = json_decode(file_get_contents("../results/links/{$folder}/{$method}/{$tau_o}_{$tau_avg}_{$w_val}.json"), true);

$heat = $json['data'];
$focusKeys = $json['foc'];
$refKeys = $json['ref'];

?>

    <div class='row'>
        <div class='col-4'>
            <div id='MU1_G'></div>
        </div>
        <div class='col-4'>
            <div id='MU1_E'></div>
        </div>
        <div class='col-4'>
            <div id='MU1_L'></div>
        </div>
    </div>
    <div class='clearfix'></div>
    <div class='row'>
        <div class='col-4'>
            <div id='sameURI'></div>
        </div>
        <div class='col-4'>
            <div id='MU2_E'></div>
        </div>
        <div class='col-4'>
            <div id='MU2_L'></div>
        </div>
    </div>
    <div class='clearfix'></div>
    <div class='row'>
        <div class='col-6'>
            <div id='MU3_E'></div>
        </div>
        <div class='col-6'>
            <div id='MU3_L'></div>
        </div>
    </div>
    <div class='clearfix'></div>
    <div class='row'>
        <div class='col-4'>
            <div id='MU4_G'></div>
        </div>
        <div class='col-4'>
            <div id='MU4_E'></div>
        </div>
        <div class='col-4'>
            <div id='MU4_L'></div>
        </div>
    </div>
    <div class='clearfix'></div>
    <div class='row'>
        <div class='col-4'>
            <div id='MU5_G'></div>
        </div>
        <div class='col-4'>
            <div id='MU5_E'></div>
        </div>
        <div class='col-4'>
            <div id='MU5_L'></div>
        </div>
    </div>
    <div class='clearfix'></div>
    <div class='row'>
        <div class='col-4'>
            <div id='sym_p'></div>
        </div>
        <div class='col-4'></div>
        <div class='col-4'></div>
    </div>
    <div class='clearfix'></div>

    <script>
        var data_sameURI = [{
            z: [<?php echo $heat['sameURI']; ?>],
            x: <?php echo json_encode($focusKeys); ?>,
            y: <?php echo json_encode($refKeys); ?>,
            type: 'heatmap',
            hoverongaps: false,
            xgap: 3,
            ygap: 3,
            zauto: false,
            zmin: 0,
            zmax: 1,
            colorscale: [
                [0, '#ff4757'],
                [<?php echo ($tau_avg - 0.01 * $tau_avg); ?>, '#ffffff'],
                [<?php echo $tau_avg; ?>, '#7bed9f'],
                [1, '#000000']
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

        var data_MU1_G = [{
            z: [<?php echo $heat['MU1_G']; ?>],
            x: <?php echo json_encode($focusKeys); ?>,
            y: <?php echo json_encode($refKeys); ?>,
            type: 'heatmap',
            hoverongaps: false,
            xgap: 3,
            ygap: 3,
            zauto: false,
            zmin: 0,
            zmax: 1,
            colorscale: [
                [0, '#ff4757'],
                [<?php echo ($tau_avg - 0.01 * $tau_avg); ?>, '#ffffff'],
                [<?php echo $tau_avg; ?>, '#7bed9f'],
                [1, '#000000']
            ]
        }];
        var Lay_MU1_G = {
            title: 'MU1_G <br><span style="font-size:10px;">Sum(AvgSimPerPredCoupleCBD)/Sum(NumberofLinks)</span>',
            margin: {
                l: 150,
                b: 50
            }
        };
        Plotly.newPlot('MU1_G', data_MU1_G, Lay_MU1_G, {
            responsive: true
        });

        var data_MU1_E = [{
            z: [<?php echo $heat['MU1_E']; ?>],
            x: <?php echo json_encode($focusKeys); ?>,
            y: <?php echo json_encode($refKeys); ?>,
            type: 'heatmap',
            hoverongaps: false,
            xgap: 3,
            ygap: 3,
            zauto: false,
            zmin: 0,
            zmax: 1,
            colorscale: [
                [0, '#ff4757'],
                [<?php echo ($tau_avg - 0.01 * $tau_avg); ?>, '#ffffff'],
                [<?php echo $tau_avg; ?>, '#7bed9f'],
                [1, '#000000']
            ]
        }];
        var Lay_MU1_E = {
            title: 'MU1_E <br><span style="font-size:10px;">Sum(localAvgSimPerPred/localSublink)/NumCBDwithExistSublink</span>',
            margin: {
                l: 150,
                b: 50
            }
        };
        Plotly.newPlot('MU1_E', data_MU1_E, Lay_MU1_E, {
            responsive: true
        });

        var data_MU1_L = [{
            z: [<?php echo $heat['MU1_L']; ?>],
            x: <?php echo json_encode($focusKeys); ?>,
            y: <?php echo json_encode($refKeys); ?>,
            type: 'heatmap',
            hoverongaps: false,
            xgap: 3,
            ygap: 3,
            zauto: false,
            zmin: 0,
            zmax: 1,
            colorscale: [
                [0, '#ff4757'],
                [<?php echo ($tau_avg - 0.01 * $tau_avg); ?>, '#ffffff'],
                [<?php echo $tau_avg; ?>, '#7bed9f'],
                [1, '#000000']
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

        var data_MU2_E = [{
            z: [<?php echo $heat['MU2_E']; ?>],
            x: <?php echo json_encode($focusKeys); ?>,
            y: <?php echo json_encode($refKeys); ?>,
            type: 'heatmap',
            hoverongaps: false,
            xgap: 3,
            ygap: 3
        }];
        var Lay_MU2_E = {
            title: 'MU2_E <br><span style="font-size:10px;">Sum(PredCoupleCombiCountPerCBD)/NumCBDwithExistSublink</span>',
            margin: {
                l: 150,
                b: 50
            }
        };

        Plotly.newPlot('MU2_E', data_MU2_E, Lay_MU2_E, {
            responsive: true
        });

        var data_MU2_L = [{
            z: [<?php echo $heat['MU2_L']; ?>],
            x: <?php echo json_encode($focusKeys); ?>,
            y: <?php echo json_encode($refKeys); ?>,
            type: 'heatmap',
            hoverongaps: false,
            xgap: 3,
            ygap: 3
        }];
        var Lay_MU2_L = {
            title: 'MU2_L <br><span style="font-size:10px;">Sum(PredCoupleCombiCountPerCBD)/TotalNumCBDs</span>',
            margin: {
                l: 150,
                b: 50
            }
        };

        Plotly.newPlot('MU2_L', data_MU2_L, Lay_MU2_L, {
            responsive: true
        });

        var data_MU3_E = [{
            z: [<?php echo $heat['MU3_E']; ?>],
            x: <?php echo json_encode($focusKeys); ?>,
            y: <?php echo json_encode($refKeys); ?>,
            type: 'heatmap',
            hoverongaps: false,
            xgap: 3,
            ygap: 3
        }];
        var Lay_MU3_E = {
            title: 'MU3_E <br><span style="font-size:10px;">Sum(PredCoupleSublinkCountPerCBD)/NumCBDwithExistSublink</span>',
            margin: {
                l: 150,
                b: 50
            }
        };

        Plotly.newPlot('MU3_E', data_MU3_E, Lay_MU3_E, {
            responsive: true
        });

        var data_MU3_L = [{
            z: [<?php echo $heat['MU3_L']; ?>],
            x: <?php echo json_encode($focusKeys); ?>,
            y: <?php echo json_encode($refKeys); ?>,
            type: 'heatmap',
            hoverongaps: false,
            xgap: 3,
            ygap: 3
        }];
        var Lay_MU3_L = {
            title: 'MU3_L <br><span style="font-size:10px;">Sum(PredCoupleSublinkCountPerCBD)/TotalNumCBDs</span>',
            margin: {
                l: 150,
                b: 50
            }
        };

        Plotly.newPlot('MU3_L', data_MU3_L, Lay_MU3_L, {
            responsive: true
        });

        var data_MU4_G = [{
            z: [<?php echo $heat['MU4_G']; ?>],
            x: <?php echo json_encode($focusKeys); ?>,
            y: <?php echo json_encode($refKeys); ?>,
            type: 'heatmap',
            hoverongaps: false,
            xgap: 3,
            ygap: 3
        }];
        var Lay_MU4_G = {
            title: 'MU4_G <br><span style="font-size:10px;"></span>',
            margin: {
                l: 150,
                b: 50
            }
        };

        Plotly.newPlot('MU4_G', data_MU4_G, Lay_MU4_G, {
            responsive: true
        });

        var data_MU4_E = [{
            z: [<?php echo $heat['MU4_E']; ?>],
            x: <?php echo json_encode($focusKeys); ?>,
            y: <?php echo json_encode($refKeys); ?>,
            type: 'heatmap',
            hoverongaps: false,
            xgap: 3,
            ygap: 3
        }];
        var Lay_MU4_E = {
            title: 'MU4_E <br><span style="font-size:10px;"></span>',
            margin: {
                l: 150,
                b: 50
            }
        };

        Plotly.newPlot('MU4_E', data_MU4_E, Lay_MU4_E, {
            responsive: true
        });

        var data_MU4_L = [{
            z: [<?php echo $heat['MU4_L']; ?>],
            x: <?php echo json_encode($focusKeys); ?>,
            y: <?php echo json_encode($refKeys); ?>,
            type: 'heatmap',
            hoverongaps: false,
            xgap: 3,
            ygap: 3
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

        var data_MU5_G = [{
            z: [<?php echo $heat['MU5_G']; ?>],
            x: <?php echo json_encode($focusKeys); ?>,
            y: <?php echo json_encode($refKeys); ?>,
            type: 'heatmap',
            hoverongaps: false,
            xgap: 3,
            ygap: 3
        }];
        var Lay_MU5_G = {
            title: 'MU5_G <br><span style="font-size:10px;"></span>',
            margin: {
                l: 150,
                b: 50
            }
        };

        Plotly.newPlot('MU5_G', data_MU5_G, Lay_MU5_G, {
            responsive: true
        });

        var data_MU5_E = [{
            z: [<?php echo $heat['MU5_E']; ?>],
            x: <?php echo json_encode($focusKeys); ?>,
            y: <?php echo json_encode($refKeys); ?>,
            type: 'heatmap',
            hoverongaps: false,
            xgap: 3,
            ygap: 3
        }];
        var Lay_MU5_E = {
            title: 'MU5_E <br><span style="font-size:10px;"></span>',
            margin: {
                l: 150,
                b: 50
            }
        };

        Plotly.newPlot('MU5_E', data_MU5_E, Lay_MU5_E, {
            responsive: true
        });

        var data_MU5_L = [{
            z: [<?php echo $heat['MU5_L']; ?>],
            x: <?php echo json_encode($focusKeys); ?>,
            y: <?php echo json_encode($refKeys); ?>,
            type: 'heatmap',
            hoverongaps: false,
            xgap: 3,
            ygap: 3
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
            x: <?php echo json_encode($focusKeys); ?>,
            y: <?php echo json_encode($refKeys); ?>,
            type: 'heatmap',
            hoverongaps: false,
            xgap: 3,
            ygap: 3
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

    <div class='card card-body fixedBot'>
        <a data-toggle='collapse' href='#collapseDiv' role='button' aria-expanded='false' aria-controls='collapseDiv'>Debug information</a>
        <div class='collapse' id='collapseDiv'>
            <?php var_dump(json_encode($data, JSON_PRETTY_PRINT)); ?>
        </div>
    </div>
