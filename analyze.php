<?php
    $key = $_REQUEST['key'];

    if(file_exists("results/0_{$key}.json") && file_exists("results/1_{$key}.json")){
        // read files into json objects
        $s0 = json_decode(file_get_contents("results/0_{$key}.json"));
        $s1 = json_decode(file_get_contents("results/1_{$key}.json"));

        // analyse 0 -> 1 links
        foreach ($s0 as $value) {
            var_dump($value);
            echo "<br/>";
        }

        // analyse 1 -> 0 links
        foreach ($s1 as $value) {
            var_dump($value);
            echo "<br/>";
        }
    }
    else die();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Results</title>
</head>
<body>
    
</body>
</html>