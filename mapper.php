<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>

    <title>Archer: Analyse <?php echo (isset($_REQUEST['folder'])) ? $_REQUEST['folder'] : ""; ?></title>
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
    </style>

    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>

</head>

<body>



    <div class="container-fluid">
        <div class="row">
            <div class="sidebar col-sm-3 hidden-xs">
                <h1>Archer</h1>
                <p><a href='query.php'>&lt; Back to queries</a></p>
                <div class="form-group row">
                    <label for="objSymMethod" class="col-sm-2 col-form-label">Object Similarity</label>
                    <div class="col-sm-10">
                        <select id='objSymMethod' class="form-control form-control-sm">
                            <option value='default'>String equality</option>
                            <option value='jaccard'>Jaccard (chars)</option>
                            <option value='jaccard-word'>Jaccard (Words)</option>
                            <option value='jaro-winkler'>Jaro-Winkler</option>
                        </select>
                    </div>
                </div>
                <div class="row p-3">
                    <div class="col-6">
                        <div class="form-group row">
                            <label for="localTau">Local Tau:</label>
                            <input class="form-control" type="text" id="localTau" value="0.5" />
                        </div>
                    </div>
                </div>


                <br>

                <?php
                if (isset($_REQUEST['folder']) && file_exists("results/" . $_REQUEST['folder'] . ".json")) {
                    $folder = "results/" . $_REQUEST['folder'];
                    $fileCount = floor(count(glob($folder . "/*.json")) / 2);
                    $indices = json_decode(file_get_contents($folder . ".json"));

                    echo "<p>Total sets: <span class='badge badge-danger float-right'>" . $fileCount . "</span></p>";
                    echo "<ol>";
                    for ($key = 0; $key < $fileCount; $key++) {

                        // read files into json objects
                        $s0 = json_decode(file_get_contents($folder . "/0_{$key}.json"));
                        $s1 = json_decode(file_get_contents($folder . "/1_{$key}.json"));

                        $c0 = count($s0);
                        $c1 = count($s1);

                        echo "<li data-key='{$key}'><a class='linkResult' href='#{$key}' data-key='{$key}'>" . urldecode(basename($indices[$key][1])) . "</a><span class='triple1_{$key} badge badge-primary float-right'>{$c1}</span><span class='triple0_{$key} badge badge-warning float-right'>{$c0}</span></li>";
                    }
                    echo "</ol>";
                } else {
                    echo "<ul><li>Please set a valid folder in the parameters.</li></ul>";
                }
                ?>

            </div>
            <div class="col-sm-9 col-sm-offset-3">
                <div class="topbar row">
                    <div class="form-group col-2">
                        <label for="tau_l">Local threshold</label>
                        <select id='tau_l' class="up form-control form-control-sm">
                            <option value='0'>0</option>
                            <option value='0.25'>0.25</option>
                            <option value='0.5'>0.5</option>
                            <option value='0.75'>0.75</option>
                        </select>
                    </div>
                    <div class="form-group col-2">
                        <label for="tau_g">Global threshold</label>
                        <select id='tau_g' class="up form-control form-control-sm">
                            <option value='0'>0</option>
                            <option value='0.25'>0.25</option>
                            <option value='0.5'>0.5</option>
                            <option value='0.75'>0.75</option>
                        </select>
                    </div>
                    <div class="form-group col-2">
                        <label for="w_val">Weight for value</label>
                        <select id='w_val' class="up form-control form-control-sm">
                            <option value='0'>0</option>
                            <option value='0.25'>0.25</option>
                            <option value='0.5'>0.5</option>
                            <option value='0.75'>0.75</option>
                            <option value='1'>1</option>
                        </select>
                    </div>
                    <div class="form-group col-2">
                        <label for="key">Analysed resources</label>
                        <select id='key' class="up form-control form-control-sm">
                            <?php
                            for ($i = 0; $i < $fileCount; $i++) {
                                echo "<option value='{$i}'>" . ($i + 1) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-4 p-3" style="padding-top:30px;">
                        <button class='loadResult btn btn-primary'>load Results <span class="spnn spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none;"></span>
                            <span class="spnn sr-only" style="display:none;">Loading...</span>
                        </button>
                        <button class='loadAnalysis btn btn-light'>load Analysis <span class="spnn spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none;"></span>
                            <span class="spnn sr-only" style="display:none;">Loading...</span>
                        </button>
                    </div>
                    <div class="clearfix"></div>
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

            $('.linkResult').on('click', function() {
                var that = $(this);
                var key = $(this).data("key");
                currentAnchor = key;

                $.ajax({
                    type: "get",
                    url: "analyze.php?tauO=" + $('#localTau').val() + "&tauAvg=" + $('#avgTau').val() + "&method=" + $('#objSymMethod').val() + "&folder=<?php echo isset($_REQUEST['folder']) ? $_REQUEST['folder'] : ""; ?>&key=" + key,
                    success: function(response) {
                        $('.main').html(response);
                    }
                });
            });

            $('.linkAll').on('click', function() {
                var that = $(this);
                var key = $(this).data("key");
                that.attr("disabled", true);
                $('.spnn').show();

                $.ajax({
                    type: "get",
                    url: "analyze.php?tauO=" + $('#localTau').val() + "&tauAvg=" + $('#avgTau').val() + "&method=" + $('#objSymMethod').val() + "&folder=<?php echo isset($_REQUEST['folder']) ? $_REQUEST['folder'] : ""; ?>",
                    success: function(response) {
                        $('.main').html(response);
                        $('.spnn').hide();
                        that.removeAttr("disabled");
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
                    url: "assets/loader.php?key=" + $('#key').val() + "&tau_l=" + $('#tau_l').val() + "&tau_g=" + $('#tau_g').val() + "&w_val=" + $('#w_val').val() + "&method=" + $('#objSymMethod').val() + "&folder=<?php echo isset($_REQUEST['folder']) ? $_REQUEST['folder'] : ""; ?>",
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
                    url: "assets/loader.php?analyse=0&tau_l=" + $('#tau_l').val() + "&tau_g=" + $('#tau_g').val() + "&w_val=" + $('#w_val').val() + "&method=" + $('#objSymMethod').val() + "&folder=<?php echo isset($_REQUEST['folder']) ? $_REQUEST['folder'] : ""; ?>",
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
                    url: "assets/loader.php?" + context + "tau_l=" + $('#tau_l').val() + "&tau_g=" + $('#tau_g').val() + "&w_val=" + $('#w_val').val() + "&method=" + $('#objSymMethod').val() + "&folder=<?php echo isset($_REQUEST['folder']) ? $_REQUEST['folder'] : ""; ?>",
                    success: function(response) {
                        $('.main').html(response);
                        $('.spnn').hide();
                    }
                });

            });
        </script>




</body>

</html>