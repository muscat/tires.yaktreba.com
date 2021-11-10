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
?>
<!DOCTYPE html>
<html>

<head>
    <title>Шиномонтаж &bull; ЯкТреба &bull; админка </title>

    <link rel="icon" type="image/png" href="favicon-admin.png" />

    <meta http-equiv="refresh" content="60">

    <meta http-equiv="cache-control" content="max-age=0" />
    <meta http-equiv="cache-control" content="no-cache" />
    <meta http-equiv="pragma" content="no-cache" />
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, maximum-scale=1, minimum-scale=1" />
    <link rel="stylesheet" type="text/css" href="css/tires.css" media="all" />


</head>
<body>
<?php

$color1='#55aa55'; $color2='#88cc88'; // list($color1,$color2) = array($color2,$color1);


// брать ближайшие 10 дней и выводить таймслоты в ширину (горизонтально, таймслот и под ним записанные телефоны), а дни - сверху вниз

$now_epoch=date('U', strtotime('00:00'));

for ($day=0; $day < 10; $day++) {
$day_counter = $now_epoch + $day * 86400;

    // все записи на это число
    $q="select * from booking where status>=80 and date='" . date('Y-m-d', $day_counter) . "' order by id";
    $result = mysqli_query($conn, $q);
    if ( ! $result ) {  echo "error|error #211: " . mysqli_error($conn) . '|0000'; exit; }
    if ( mysqli_num_rows($result) <= 0 ) { continue; } // если записей на этот день нет - пропускаем этот день

    echo "<h3 class=admin_booking_day style='border:solid 2px white; border-top-color:" . $color1 . "'><b>" . $weekdaynames[date('N', $day_counter)] . date(', d.m.Y', $day_counter) . "</b></h3>";

    // перебор таймслотов от начала и до конца рабочего дня
    $horiz_cursor=$work_time_start;
    while ($horiz_cursor < $work_time_end) {
        echo "<span class=admin_booking_timeslot>";

        echo "<div style='font-family: Tahoma; font-size:0.9em; width:100%; background-color:#88cc88'><b>";
        echo date("H:i", $now_epoch + $horiz_cursor);
        echo "</b></div>";

        # вывод телефонов, записанных на этот таймслот
        # DEBUG echo "<small>". $horiz_cursor . $eol . "</small>";
        foreach ($result as $timeslot) {
            if ( $timeslot['timeslot'] > $horiz_cursor - $timeslotsize && $timeslot['timeslot'] < $horiz_cursor + $timeslotsize) {
             if ( date('U') - date('U', strtotime($timeslot['timestamp'])) < 600 ) $highlight="style='background-color:orange;'"; else $highlight=""; // недавние обращение - подсвечивать
             echo "<span " . $highlight . " title='заказ поступил " . date('d.m.Y H:i', strtotime($timeslot['timestamp'])) . ' на таймслот ' . date("H:i", $day_counter + $timeslot['timeslot'])  . "'>" . $timeslot['phone'] . "</span>" . $eol;
             }
        }


        $horiz_cursor+=$timeslotsize;
        echo "</span>";
    }
    echo "<br class=admin_booking_timeslot_end />";

    /* $row['id']         $row['date']          $row['phone']          $row['status']          echo "</tr>"; */
}

?>
</body>
</html>
