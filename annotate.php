<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>

    <title>Archer: Annotate data from <?php echo (isset($_REQUEST['f'])) ? $_REQUEST['f'] : ""; ?></title>
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
  <script type="text/javascript" async src="https://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_SVG">
</script>

    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>

</head>

<body>



    <div class="container-fluid">
        <div class="row">
            <div class="sidebar col-sm-3 hidden-xs">
                <h1>Archer</h1>
                <p><a href='mapper.php?f=<?php echo (isset($_REQUEST['f'])) ? $_REQUEST['f'] : ""; ?>'>&lt; Back to analysis</a></p>

                <p>Step 3: Annotate data</p>

                <div class="accordion" id="accordion">
                <div class="card text-dark">
                    <div class="card-header" id="headingOne">
                        <a href="#" class="btn btn-link btn-lg" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">Parameters <span class="badge badge-info" data-toggle="tooltip" data-placement="right" title="Please select the different weights and threshold you want to evaluate the contextual uncertainty. Please keep in mind that the analysis is cached and you can still annotate your data using different uncertainty outcome.">?</span></a>
                    </div>

                    <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                        <div class="card-body">
                            <div class="form-group row mr-0">
                                <label for="objSymMethod" class="col-sm-4 col-form-label">Obj. Sim.</label>
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
                            <div class="form-group row mb-3 mr-0">
                                <label class="col-sm-4 col-form-label" for="r1Tau">R1 Obj-Tau</label>
                                <input class="col-sm-8 form-control form-control-sm" type="number" id="r1Tau" value="0.5" min="0" max="1" step="0.001" />
                            </div>
                            <div class="form-group row mb-3 mr-0">
                                <label class="col-sm-4 col-form-label" for="localTau">Avg. Sem-Tau</label>
                                <input class="col-sm-8 form-control form-control-sm" type="number" id="localTau" value="0.5" min="0" max="1" step="0.001" />
                            </div>
                            <div class="form-group row mb-3 mr-0">
                                <label class="col-sm-4 col-form-label" for="localTau">Weights</label>
                                <div class="form-row col-sm-8">
                                    <div class="form-group col-md-6">
                                        <label for="inputEmail4">R0</label>
                                        <input class="form-control form-control-sm" type="number" id="weight0" value="0.25" min="0" max="1" step="0.001" />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="inputPassword4">R1</label>
                                        <input class="form-control form-control-sm" type="number" id="weight1" value="0.25" min="0" max="1" step="0.001" />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="inputPassword4">R2</label>
                                        <input class="form-control form-control-sm" type="number" id="weight2" value="0.25" min="0" max="1" step="0.001" />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="inputPassword4">R3</label>
                                        <input class="form-control form-control-sm" type="number" id="weight3" value="0.25" min="0" max="1" step="0.001" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card text-dark">
                    <div class="card-header" id="headingTwo">
                        <a href="#" class="btn btn-link btn-lg" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">Export format <span class="badge badge-info" data-toggle="tooltip" data-placement="right" title="Choose your export format. Whether you want your data in RDF/XML, JSON-LD, TURTLE/TRIG, etc.">?</span></a>
                    </div>

                    <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
                        <div class="card-body">
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <label class="input-group-text text-light bg-dark" for="objSymMethodC">Format</label>
                                </div>
                                <select class="custom-select" id="objSymMethodC">
                                    <option value='n3'>N3 (reified)</option>
                                    <option value='turtle'>Turtle (named graphs) </option>
                                    <option value='rdfxml'>RDF/XML (reified)</option>
                                </select>
                            </div>
                            <div class="format">
                                <div class="btn-group d-flex mb-3" role="group" aria-label="Export format">
                                    <button class='exportResult btn btn-secondary'>Export data</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>

        </div>
        <div class="col-sm-9 col-sm-offset-3">
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

            $('.generateSample').on('click', function() {
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

            $('.exportResult').on('click', function() {
                var that = $(this);

                $.ajax({
                    type: "get",
                    url: "localAnalysis.php?method=" + $('#objSymMethodC').val() + "&f=<?php echo isset($_REQUEST['f']) ? $_REQUEST['f'] : ""; ?>",
                    success: function(response) {
                        $('.modal-body').html(response)
                        $('.localAnalysis').hide()
                        $('.parameters').show()
                    },
                    error: function(response){
                        $('.modal-body').html('<p>Analysis already done for this method and this set of focus graphs.</p><p>You may already explore it and visualize it using "Load Results" and "Load Analysis" buttons above.</p>')
                    }
                });
            });
        </script>

</body>

</html>