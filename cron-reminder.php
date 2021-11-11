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

$start_of_today_epoch=date('U', strtotime('00:00'));
$now_epoch=date('U');


// все записи на сегодня
$q="select * from booking where status>=80 and date='" . date('Y-m-d', $now_epoch) . "' order by id";

$result = mysqli_query($conn, $q);
if ( ! $result ) {  echo "error|error #221: " . mysqli_error($conn) . '|0000'; exit; }
if ( mysqli_num_rows($result) <= 0 ) { exit; } // если записей на этот день нет - пропускаем этот день

foreach ($result as $timeslot) {
#echo $now_epoch - $start_of_today_epoch . $eol; 
#echo $timeslot['timeslot'] . $eol;
#echo $timeslot['timeslot'] + $start_of_today_epoch - $now_epoch - $timeslotsize . $eol;

if ( $timeslot['timeslot'] + $start_of_today_epoch - $now_epoch - $timeslotsize < 61 && $timeslot['timeslot'] + $start_of_today_epoch - $now_epoch - $timeslotsize > 0 ) 
    { 
        $ctx = stream_context_create(array('http' => array('timeout' => 30)));
        $url="https://smsc.ua/sys/send.php?fmt=1&login=yaktreba&psw=68AWtBVyX6WsrDt6&phones=" . $timeslot['phone'] .  "&mes=Нагадування:%0a- шиномонтаж%0a- на " . date("H:i", $start_of_today_epoch + $timeslot['timeslot']) . "%0a- Костромська, 25%0a%0a0961903030";
        $response = file_get_contents($url, 0, $ctx);
        // TODO: check response
        // [ $newid, $sendresult ] = explode(',', $response);
    
    }
}
