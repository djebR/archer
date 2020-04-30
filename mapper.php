
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
        <p><a class='linkAll' href='#' >Show full results</a></p>
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
        <br>

            <?php
                if(isset($_REQUEST['folder'])){
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
                            
                        echo "<li><a class='linkResult' href='#' data-key='$key'>" . urldecode(basename($indices[$key][1])) . "</a><span class='triple1_{$key} badge badge-primary float-right'>{$c1}</span><span class='triple0_{$key} badge badge-warning float-right'>{$c0}</span></li>";
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

        $('.linkResult').on('click', function(){
            var that = $(this);
            var key = $(this).data("key");

            $.ajax({
                type: "get",
                url: "analyze.php?method="+$('#objSymMethod').val()+"&folder=<?php echo isset($_REQUEST['folder'])?$_REQUEST['folder']:"";?>&key=" + key,
                success: function (response) {
                    $('.main').html(response);
                }
            });
        });

        $('.linkAll').on('click', function(){
            var that = $(this);
            var key = $(this).data("key");

            $.ajax({
                type: "get",
                url: "analyze.php?method="+$('#objSymMethod').val()+"&folder=<?php echo isset($_REQUEST['folder'])?$_REQUEST['folder']:"";?>",
                success: function (response) {
                    $('.main').html(response);
                }
            });
        });

        $('#analyseAll').on('click', function(){

            var keys = $("a[class=analysis]");
            var count0 = 0;
            var count1 = 0;
            var countLinks = 0;

            var y0 = [];
            var y1 = [];
            var y2 = [];

            $.each(keys, function (indexInArray, valueOfElement) { 
                var that = $(this);
                var key = $(this).data("key");
                var firstTriple = $(this).parent().siblings('td').children('a[role]:first');
                var secondTriple = $(this).parent().siblings('td').children('a[role]:last');

                $.ajax({
                    async: false,
                    type: "get",
                    url: "analyze.php?key=" + key,
                    data: "data",
                    dataType: "json",
                    success: function (response) {
                        firstTriple.html(firstTriple.html() + "<span class='badge badge-danger float-right'>"+ response.nodesFrom + "</span>");
                        secondTriple.html(secondTriple.html() + "<span class='badge badge-danger float-right'>"+ response.nodesTo + "</span>");
                        that.html(that.html() + "<span class='badge badge-success float-right'>"+ response.links + "</span>");
                        count0 += response.nodesFrom;
                        count1 += response.nodesTo;
                        countLinks += response.links;

                        triple0 = Number(that.parent().siblings('td').find('.triple0').text());
                        triple1 = Number(that.parent().siblings('td').find('.triple1').text());
                        y0[key] = (triple0 == 0)?0:(response.nodesFrom/triple0);
                        y1[key] = (triple1 == 0)?0:(response.nodesTo/triple1);
                        y2[key] = (triple0 == 0 || triple1 == 0)?0:Math.max(response.nodesFrom,response.nodesTo)/Math.min(triple0, triple1);
                        /* will be update to use with d3
                        
                        response.possibleLinkedPred.forEach(element => {
                            if(!hash.hasOwnProperty(element)){
                                possibleLinks.push(element);
                                hash[element] = 1;
                            } else {
                                hash[element]++;
                            }
                        });*/
                    }
                });
            });

            $('#count0').parent().html($('#count0').parent().html() + "<span class='badge badge-danger float-right'>"+ count0 + "</span>");
            $('#count1').parent().html($('#count1').parent().html() + "<span class='badge badge-danger float-right'>"+ count1 + "</span>");
            $('#count2').parent().html($('#count2').parent().html() + "<span class='badge badge-success float-right'>"+ countLinks + "</span>");

            $.ajax({
                async: false,
                type: "get",
                url: "analyze.php",
                data: "data",
                dataType: "json",
                success: function (response) {
    
                    var content = "<p><a class='btn btn-primary dropdown-toggle' data-toggle='collapse' href='#analyseResults' role='button' aria-expanded='true' aria-controls='analyseResults'>Collapse Analysis</a></p><div id='analyseResults' class='collapse show'>";
                    content += "<div class='row'><div class='col-sm-6'>" +
                        "Number of Triples issued from MusicBrainz : " + response.totalTriples0 + "<br/>"
                        + "Number of Triples issued from DBpedia : " + response.totalTriples1 + "<br/>"
                        + "Number of (MB) Resources linked with (DBP) : " + response.totalLinkedNodes0 + "<br/>"
                        + "Number of (DBP) Resources linked with (MB) : " + response.totalLinkedNodes1 + "<br/>"
                        + "Number of (MB) Resources with zero links : " + response.zeroResources0 + "<br/>"
                        + "Number of (DBP) Resources with zero links : " + response.zeroResources1 + "<br/>"
                        + "Number of Triples of (MB) Resources with zero links : " + response.zeroResourcesTriples0 + "<br/>"
                        + "Number of Triples of (DBP) Resources with zero links : " + response.zeroResourcesTriples1 + "</div>"
                        + "<div class='col-sm-6' id='plotter' style='padding: 0px;'></div></div><br/><table class='table stats'><tr>";

                    content += '<tr><th>Key</th><th>Frequency</th><th>Existance of a Link per SameAs</th></tr>'

                    for (element in response.hashes){
                        content += '<tr>'                        
                        content += '<td>' + element + '</td><td>' + response.hashes[element] + '</td></tr>'
                    };
                    content += "</table></div>";

                    $('.main').append(content);

                    var trace1 = {
                    y: y0,
                    type: 'box',
                    name: 'MusicBrainz'
                    };

                    var trace2 = {
                    y: y1,
                    type: 'box',
                    name: 'DBpedia'
                    };

                    var trace3 = {
                    y: y2,
                    type: 'box',
                    name: 'Total'
                    };

                    var data = [trace1, trace2, trace3];

                    Plotly.newPlot('plotter', data);
                }
            });

        });

    </script>
</body>
</html>



