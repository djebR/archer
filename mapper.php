
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>

    <title>Archer: Subjective Probability</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <style>
        body {
            margin: 20px;
        }

        .col-sm-offset-3 {
            margin-left: 25%;
        }

        .card td, .stats td {
            padding: 0px 0px 0px 10px;
            font-size:small;
        }

        .icn {
            width:20px;
            height:20px;
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
    </style>

    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
</head>

<body>



<div class="container-fluid">
  <div class="row">
    <div class="sidebar col-sm-3 hidden-xs">
        <h1>Archer</h1>
        <p><a href='query.php' >&lt; Back to queries</a></p>
        <p><button class='linkAll btn btn-primary'>Show full results
            <span class="spnn spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none;"></span>
            <span class="spnn sr-only" style="display:none;">Loading...</span>
        </button></p>
        <div class="form-group row">
            <label for="objSymMethod" class="col-sm-2 col-form-label">Object Similarity</label>
            <div class="col-sm-10">
                <select id='objSymMethod' class="form-control form-control-sm">
                    <option value='default'>String equality</option>
                    <option value='jaccard'>Jaccard</option>
                    <option value='jaro-winkler'>Jaro-Winkler</option>
                </select>
            </div>
        </div>
        <div class="row p-3">
            <div class="col-6">
                <div class="form-group row">
                    <label for="localTau">Local Tau:</label>
                    <input class="form-control" type="text" id="localTau" value="0.5"/>
                </div>  
            </div>
            <div class="col-6">
                <div class="form-group row">
                    <label for="avgTau">Avg. Tau:</label>
                    <input class="form-control" type="text" id="avgTau" value="0.5"/>
                </div>
            </div>
        </div>
        

        <br>

            <?php
                if(isset($_REQUEST['folder']) && file_exists("results/".$_REQUEST['folder'].".json")){
                    $folder = "results/".$_REQUEST['folder'];
                    $fileCount = floor(count(glob($folder."/*.json"))/2);
                    $indices = json_decode(file_get_contents($folder.".json"));

                    echo "<p>Total sets: <span class='badge badge-danger float-right'>".$fileCount."</span></p>";
                    echo "<ul>";
                    for($key = 0; $key < $fileCount; $key++){

                        // read files into json objects
                        $s0 = json_decode(file_get_contents($folder."/0_{$key}.json"));
                        $s1 = json_decode(file_get_contents($folder."/1_{$key}.json"));

                        $c0 = count($s0);
                        $c1 = count($s1);
                            
                        echo "<li><a class='linkResult' href='#{$key}' data-key='{$key}'>" . urldecode(basename($indices[$key][1])) . "</a><span class='triple1_{$key} badge badge-primary float-right'>{$c1}</span><span class='triple0_{$key} badge badge-warning float-right'>{$c0}</span></li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<ul><li>Please set a valid folder in the parameters.</li></ul>";
                }    
            ?>

    </div>
    <div class="main col-sm-9 col-sm-offset-3">

  </div>
</div>


    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

    <script>

        var currentAnchor = -1;

        $('.linkResult').on('click', function(){
            var that = $(this);
            var key = $(this).data("key");
            currentAnchor = key;

            $.ajax({
                type: "get",
                url: "analyze.php?tauO="+$('#localTau').val()+"&tauAvg="+$('#avgTau').val()+"&method="+$('#objSymMethod').val()+"&folder=<?php echo isset($_REQUEST['folder'])?$_REQUEST['folder']:"";?>&key=" + key,
                success: function (response) {
                    $('.main').html(response);
                }
            });
        });

        $('.linkAll').on('click', function(){
            var that = $(this);
            var key = $(this).data("key");
            that.attr("disabled", true);
            $('.spnn').show();

            $.ajax({
                type: "get",
                url: "analyze.php?tauO="+$('#localTau').val()+"&tauAvg="+$('#avgTau').val()+"&method="+$('#objSymMethod').val()+"&folder=<?php echo isset($_REQUEST['folder'])?$_REQUEST['folder']:"";?>",
                success: function (response) {
                    $('.main').html(response);
                    $('.spnn').hide();
                    that.removeAttr("disabled");
                }
            });
        });

    </script>
</body>
</html>



