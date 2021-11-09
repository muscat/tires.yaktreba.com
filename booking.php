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
<?php

$color1='#55aa55'; $color2='#88cc88';

// TODO: брать ближайшие 7 дней и выводить таймслоты в ширину (горизонтально, таймслот и под ним записанные телефоны), а дни - сверху вниз

$now_epoch=date('U', strtotime('00:00'));
for ($day=0; $day < 7; $day++) {

$q="select * from booking where status>=80 and date='" . date('Y-m-d', $now_epoch + $day * 86400) . "' order by date,id limit 100";
if ($result = mysqli_query($conn, $q)) {
    $prev_day=$today='';

    foreach ($result as $row) {
        $today = $row['date'];

        if ( $prev_day != $today ) { // новый день
            echo "<br class=admin_booking_timeslot_end />";
            echo "<h3 class=admin_booking_day style='border:solid 2px white; border-bottom-color:" . $color1 . "'><b>" . $weekdaynames[date('N', strtotime($today))] . date(', d.m.Y', strtotime($today)) . "</b></h3><br>"; 
            $prev_day=$today; /* list($color1,$color2) = array($color2,$color1);  */
        }

        // перебор таймслотов от начала и до конца рабочего дня
        $horiz_cursor=$work_time_start;
        while ($horiz_cursor < $work_time_end) {
            echo "<span class=admin_booking_timeslot>";

            echo "<div style='font-family: Tahoma; font-size:0.9em; width:100%; background-color:#88cc88'><b>";
            echo date("H:i", $now_epoch + $horiz_cursor);
            echo "</b></div><br>";
            
            echo "0673633660";
            $horiz_cursor+=$timeslotsize;
            echo "</span>";
        }
        echo "<br class=admin_booking_timeslot_end />";
        

    }
    
    /* $row['id']         $row['date']          $row['phone']          $row['status']          echo "</tr>"; */

}

}

?>
</body>
</html>
