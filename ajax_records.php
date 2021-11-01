<?php

$test=array("3", "2021-11-01", "+380673633660", "80");
array_push($test, "4", "2021-11-04", "+380673633670", "40");
echo json_encode($test);
exit;



echo <<<EOL

{
    "draw": 1,
    "recordsTotal": 57,
    "recordsFiltered": 57,

    "main": [
      
    [
        "3",
        "2021-11-01",
        "+380673633660",
        "80"
    ],

    [
        "4",
        "2021-11-04",
        "+380673633670",
        "40"
    ],

    ]
}
EOL;
