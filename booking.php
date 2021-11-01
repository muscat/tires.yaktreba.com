<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Configuration
if (is_file('config.php')) {
    require_once('config.php');
}

// mySnippets
if (is_file('snippets.php')) {
    require_once('snippets.php');
}


// connect to DB
$conn = mysqli_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>
<!DOCTYPE html>
<html>

<head>
    <title>Шиномонтаж &bull; ЯкТреба &bull; админка </title>

    <link rel="icon" type="image/png" href="favicon-admin.png" />

    <meta http-equiv="cache-control" content="max-age=0" />
    <meta http-equiv="cache-control" content="no-cache" />
    <meta http-equiv="pragma" content="no-cache" />
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, maximum-scale=1, minimum-scale=1" />
    <link rel="stylesheet" type="text/css" href="css/tires.css" media="all" />


</head>
<body>
<table width=100% style='font-family:Tahoma, Geneva, Verdana, sans-serif'>
    <thead>
        <tr>
            <th>#</th>
            <th>date</ht>
            <th>phone</ht>
            <th>status</th>
        </tr>
    </thead>
    <tbody>
<?php


$color1='#fff462'; $color2='#ffffff';

$q="select * from booking where status>=80 and date>='" . date('Y-m-d') . "' order by id limit 100";
if ($result = mysqli_query($conn, $q)) {
    foreach ($result as $row) {
        echo "<tr style='background-color:" . $color1 . "'>"; list($color1,$color2) = array($color2,$color1);
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['date'] . "</td>";
        echo "<td>" . $row['phone'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
        }
}



?>
    </tbody>
</table>
</body>
</html>
