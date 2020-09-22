<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>

    <title>Archer: Analyse <?php echo (isset($_REQUEST['f'])) ? $_REQUEST['f'] : ""; ?></title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <style>
        body {
            margin: 20px;
        }

        li a {
            color: white;
        }

        .col-sm-offset-3 {
            margin-left: 25%;
        }

        .card td,
        .stats td {
            padding: 0px 0px 0px 10px;
            font-size: small;
        }

        .icn {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }

        .progress {
            margin-top: 11px;
        }

        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            padding: 20px;
            display: block;
            overflow-x: hidden;
            overflow-y: auto;
            background-color: #2f3850;
            color: white;
        }

        .topbar {
            padding: 20px;
            overflow-x: hidden;
            overflow-y: auto;
            background-color: #2f3850;
            color: white;
        }

        .linkResult {
            color:black;
        }
    </style>

    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>

</head>

<body>



    <div class="container-fluid">
        <div class="row">
            <div class="sidebar col-sm-3 hidden-xs">
                <h1>Archer</h1>
                <p><a href='query.php'>&lt; Back to queries</a></p>

                <p>Step 2: Analyse focus graphs</p>

                <div class="accordion" id="accordion">
                <div class="card text-dark">
                    <div class="card-header" id="headingOne">
                        <a href="#" class="btn btn-link btn-lg" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">Individual Analysis <span class="badge badge-info" data-toggle="tooltip" data-placement="right" title="Used only on-the-go in the individual comparaison between one couple of linked focus graphs. This parameter will be replaced during the global analysis with the one on the its dashboard.">?</span></a>
                    </div>

                    <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                        <div class="card-body">
                            
                            <div class="form-group row mr-0">
                                <label for="objSymMethod" class="col-sm-4 col-form-label">Object Similarity</label>
                                <select id='objSymMethod' class="col-sm-8 form-control form-control-sm">
                                    <option value='default'>String equality</option>
                                    <option value='jaccard'>Jaccard (chars)</option>
                                    <option value='jaccard-word'>Jaccard (Words)</option>
                                    <option value='jaro-winkler'>Jaro-Winkler</option>
                                </select>
                            </div>
                            <div class="form-group row mb-3 mr-0">
                                <label class="col-sm-4 col-form-label" for="localTau">Local Obj-Tau</label>
                                <input class="col-sm-8 form-control form-control-sm" type="number" id="localTau" value="0.5" min="0" max="1" step="0.001" />
                            </div>

                            <?php

                                if (isset($_REQUEST['f']) && file_exists("results/" . $_REQUEST['f'] . ".json")) {
                                    $resultFile = "results/" . $_REQUEST['f'] . ".json";
                                    $metaFile   = "results/meta_" . $_REQUEST['f'] . ".json";

                                    $indices    = json_decode(file_get_contents($resultFile), true);
                                    $meta       = json_decode(file_get_contents($metaFile), true);

                                    $fileCount  = $meta['realised'];

                                    echo "<p>Total sets: <span class='badge badge-danger float-right'>" . $fileCount . "</span></p>";
                                    echo "<ol>";
                                    for ($key = 0; $key < $fileCount; $key++) {
                                        $c0 = count($indices[$key]['target']);
                                        $c1 = count($indices[$key]['reference']);
                                        
                                        echo "<li data-key='{$key}'><a class='linkResult' href='#{$key}' data-key='{$key}'>" . urldecode(basename($indices[$key]['link'][2])) . "</a><span class='triple1_{$key} badge badge-primary float-right'>{$c1}</span><span class='triple0_{$key} badge badge-warning float-right'>{$c0}</span></li>";
                                    }
                                    echo "</ol>";
                                } else {
                                    echo "<ul><li>Please select a valid folder in the parameters.</li></ul>";
                                }
                                ?>
                        </div>
                    </div>
                </div>
                <div class="card text-dark">
                    <div class="card-header" id="headingTwo">
                        <a href="#" class="btn btn-link btn-lg" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">Complete Analysis <span class="badge badge-info" data-toggle="tooltip" data-placement="right" title="Used only on-the-go in the individual comparaison between one couple of linked focus graphs. This parameter will be replaced during the global analysis with the one on the its dashboard.">?</span></a>
                    </div>

                    <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
                        <div class="card-body">
                            <div class="input-group mb-3 complete">
                                <div class="input-group-prepend">
                                    <label class="input-group-text text-light bg-dark" for="objSymMethodC">Method</label>
                                </div>
                                <select class="custom-select" id="objSymMethodC">
                                    <option value='default'>String equality</option>
                                    <option value='jaccard'>Jaccard (chars)</option>
                                    <option value='jaccard-word'>Jaccard (Words)</option>
                                    <option value='jaro-winkler'>Jaro-Winkler</option>
                                </select>
                            </div>
                            <div class="parameters" style="<?php echo (!file_exists("results/" . $_REQUEST['f'] . "/default/feat.json"))?"display:none;":"";?>">
                                <div class="btn-group d-flex mb-3" role="group" aria-label="Basic example">
                                    <button class='loadResult btn btn-secondary'>load Results <span class="spnn spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none;"></span>
                                        <span class="spnn sr-only" style="display:none;">Loading...</span>
                                    </button>
                                    <button class='loadAnalysis btn btn-info'>load Analysis <span class="spnn spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none;"></span>
                                        <span class="spnn sr-only" style="display:none;">Loading...</span>
                                    </button>
                                </div>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <label class="input-group-text" for="tau_l">Local threshold</label>
                                    </div>
                                    <select class="custom-select up" id="tau_l">
                                        <option value='0'>0</option>
                                        <option value='0.25'>0.25</option>
                                        <option value='0.5'>0.5</option>
                                        <option value='0.75'>0.75</option>
                                    </select>
                                </div>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <label class="input-group-text" for="tau_g">Global threshold</label>
                                    </div>
                                    <select class="custom-select up" id="tau_g">
                                        <option value='0'>0</option>
                                        <option value='0.25'>0.25</option>
                                        <option value='0.5'>0.5</option>
                                        <option value='0.75'>0.75</option>
                                    </select>
                                </div>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <label class="input-group-text" for="w_val">Weight for value</label>
                                    </div>
                                    <select class="custom-select up" id="w_val">
                                        <option value='0'>0</option>
                                        <option value='0.25'>0.25</option>
                                        <option value='0.5'>0.5</option>
                                        <option value='0.75'>0.75</option>
                                        <option value='1'>1</option>
                                    </select>
                                </div>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <label class="input-group-text" for="key">N° of Links</label>
                                    </div>
                                    <select class="custom-select up" id="key">
                                        <?php
                                        for ($i = 0; $i < $fileCount; $i++) {
                                            echo "<option value='{$i}'>" . ($i + 1) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            

                            <button class='localAnalysis btn btn-primary' style="width:100%;<?php echo (file_exists("results/" . $_REQUEST['f'] . "/default/feat.json"))?"display:none;":"";?>" data-toggle="modal" data-target="#localAnalysis">Perform Complete Analysis</button>

                        </div>
                    </div>
                </div>
                </div>

        </div>
        <div class="col-sm-9 col-sm-offset-3">
            <div class="modal fade" id="localAnalysis" tabindex="-1" role="dialog" aria-labelledby="localAnalysisLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="localAnalysisLabel">Complete Analysis</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            ...
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="main"></div>
        </div>
    </div>


        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

        <script>
            var currentAnchor = -1;
            $(function() {
                $('[data-toggle="tooltip"]').tooltip()
            })

            $('.linkResult').on('click', function() {
                var that = $(this);
                var key = $(this).data("key");
                currentAnchor = key;

                $.ajax({
                    type: "get",
                    url: "analyze.php?tauO=" + $('#localTau').val() + "&tauAvg=" + $('#avgTau').val() + "&method=" + $('#objSymMethod').val() + "&f=<?php echo isset($_REQUEST['f']) ? $_REQUEST['f'] : ""; ?>&key=" + key,
                    success: function(response) {
                        $('.main').html(response);
                    }
                });
            });

            $('.localAnalysis').on('click', function() {
                var that = $(this);

                $.ajax({
                    type: "get",
                    url: "localAnalysis.php?method=" + $('#objSymMethodC').val() + "&f=<?php echo isset($_REQUEST['f']) ? $_REQUEST['f'] : ""; ?>",
                    success: function(response) {
                        $('.modal-body').html(response)
                    },
                    error: function(response){
                        $('.modal-body').html('<p>Analysis already done for this method and this set of focus graphs.</p><p>You may already explore it and visualize it using "Load Results" and "Load Analysis" buttons above.</p>')
                    }
                });
            });

            $('.loadResult').on('click', function() {
                var that = $(this);
                that.attr("disabled", true);
                $('.spnn').show();
                location.hash = "";

                $.ajax({
                    type: "get",
                    url: "assets/loader.php?key=" + $('#key').val() + "&tau_l=" + $('#tau_l').val() + "&tau_g=" + $('#tau_g').val() + "&w_val=" + $('#w_val').val() + "&method=" + $('#objSymMethod').val() + "&f=<?php echo isset($_REQUEST['f']) ? $_REQUEST['f'] : ""; ?>",
                    success: function(response) {
                        $('.main').html(response);
                        $('.spnn').hide();
                        that.removeAttr("disabled");
                    }
                });

            });

            $('.loadAnalysis').on('click', function() {
                var that = $(this);
                that.attr("disabled", true);
                $('.spnn').show();
                location.hash = "analyse";
                $.ajax({
                    type: "get",
                    url: "assets/loader.php?analyse=0&tau_l=" + $('#tau_l').val() + "&tau_g=" + $('#tau_g').val() + "&w_val=" + $('#w_val').val() + "&method=" + $('#objSymMethod').val() + "&f=<?php echo isset($_REQUEST['f']) ? $_REQUEST['f'] : ""; ?>",
                    success: function(response) {
                        $('.main').html(response);
                        $('.spnn').hide();
                        that.removeAttr("disabled");
                    }
                });

            });

            $('.up').on('change', function() {
                var that = $(this);
                $('.spnn').show();

                var context = (location.hash.substr(1) == "analyse") ? "analyse=0&" : ("key=" + $('#key').val() + "&");
                $.ajax({
                    type: "get",
                    url: "assets/loader.php?" + context + "tau_l=" + $('#tau_l').val() + "&tau_g=" + $('#tau_g').val() + "&w_val=" + $('#w_val').val() + "&method=" + $('#objSymMethod').val() + "&f=<?php echo isset($_REQUEST['f']) ? $_REQUEST['f'] : ""; ?>",
                    success: function(response) {
                        $('.main').html(response);
                        $('.spnn').hide();
                    }
                });

            });

            $('#objSymMethodC').on('change', function() {
                var that = $(this);

                $.ajax({
                    type: "HEAD",
                    url: "results/<?php echo $_REQUEST['f'];?>/" + that.val() + "/feat.json",
                    success: function(response) {
                        $('.localAnalysis').hide()
                        $('.parameters').show()
                    } ,
                    error: function(response){
                        $('.localAnalysis').show()
                        $('.parameters').hide()
                    }
                });

            });
        </script>




</body>

</html>