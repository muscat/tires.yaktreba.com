<?php

// Configuration
if (is_file('config.php')) {
    require_once('config.php');
}

// mySnippets
if (is_file('snippets.php')) {
    require_once('snippets.php');
}

// access only for self ajax queries
define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
#if(!IS_AJAX) {die('Restricted access');}
$pos = strpos($_SERVER['HTTP_REFERER'],getenv('HTTP_HOST'));
#if($pos===false) die('Restricted access');

// connect to DB
$conn = mysqli_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }

// parse action
$action = isset($_GET['action'])  ?  $_GET['action']  :  "";

switch ($action) {

    case 'get_booked_timeslots':
        // parse parameter
        $date = isset($_GET['date'])  ?  $_GET['date']  :  "";

        $date = preg_replace('/[^0-9]/', '', $date);
        $valid_y = date('Y', intval($date));
        $valid_m = date('m', $date);
        $valid_d = date('d', $date);
        if ( ! checkdate($valid_m, $valid_d, $valid_y) ) die("invalid date passed!");

        $q='select timeslot, status, phone from booking where date="' . date("Y-m-d", $date) . '" and (status > 70 or disabled = 1)';

        $myArray=array();
        if ( $result = mysqli_query($conn, $q) ) {
        // выводить в результат ВСЕ телефоны, которые записаны на этот таймслот: рабочих постов может быть больше чем 1
            foreach ($result as $row) {
                $row['phone'] = preg_replace('/([0-9])([0-9])([0-9])([0-9])([0-9])([0-9])([0-9])([0-9])([0-9])([0-9])$/', '$1$2$3XXXX$8$9$10$11', $row['phone']); // mask phone
                array_push($myArray, $row);
            }
        }
        echo json_encode($myArray);
        exit;






    case 'book_stage_1':
        // parse parameter
        $date  = isset($_GET['date'])   ?  $_GET['date']   :  "";
        $slot  = isset($_GET['slot'])   ?  $_GET['slot']   :  "";
        $phone = isset($_GET['phone'])  ?  $_GET['phone']  :  "";

        // check already booked/scheduled phone on this day/slot
        $q="select * from booking where date='" . date("Y-m-d", $date) . "' and phone like '%" . substr(preg_replace('/\D/', '', $phone), -10) . "' and timeslot='" . $slot . "' and status >= 80";
        $result = mysqli_query($conn, $q);
        if ( ! $result ) {  echo "error|error #202: " . mysqli_error($conn) . '|0000'; exit; }
        if ( mysqli_num_rows($result) >= 1 ) { echo 'alert|Ви вже записані на цей день та цей час!|0000'; exit; }


        // check squatter phone on this day
        $q="select * from booking where date='" . date("Y-m-d", $date) . "' and phone like '%" . substr(preg_replace('/\D/', '', $phone), -10) ."' and status >= 70";
        $result = mysqli_query($conn, $q);
        if ( ! $result ) {  echo "error|error #203: " . mysqli_error($conn) . '|0000'; exit; }
        if ( mysqli_num_rows($result) >= 3 ) { echo 'alert|У Вас занадто багато броньювань на цей день!|0000'; exit; }


        // TODO: проверять, оставлял ли он уже wish на этот день (
        // TODO: т.е. есть status=50(wish) и 60 (sent) и 70 (delivered), но нет status=80 (ввел OTP)
        // TODO: если есть status 60, но нет 70 - как-то сообщить ему

        // TODO: если есть status 50 (wish), но нет 60 (sent), значит есть какая-то ошибка в SMS сервисе. отправить админу сообщение
        //
        // TODO: check когда много status 60, но кол-во сильно не совпадает с status 70. типа не доставляются


        // с одного IP не более 3 запросов в час
        $q="select * from booking where status >= 50 and timestamp > '" . date('Y/m/d H:i:s', date('U') - 3600) . "' and ip='" . ip() . "'";
        // DEBUG: file_put_contents('/tmp/query.log', $q  . "\r\n", FILE_APPEND);
        $result = mysqli_query($conn, $q);
        if ( mysqli_num_rows($result) > 3 ) { echo 'alert|Ви створюєте занадто багато запитів (ip)|0000'; exit; }


        
        // (check for accidental escape)
        // проверить: отправляли мы уже SMS (60) или нет. если да, но нет записи с 80 - достать из базы ранее отправленный пароль и ждать ввода OTP
        // тем временем, по cron-у искать записи, у которых есть 50 и 60, но нет 70. все такие записи, старше ХХ минут - удалять
        $q="select * from booking where date='" . date("Y-m-d", $date) . "' and timeslot=" . $slot . " and phone like '%" . substr(preg_replace('/\D/', '', $phone), -10) ."' and status = 60 and NOT status >= 70";
        $result = mysqli_query($conn, $q);
        if ( ! $result ) {  echo "error|error #206: " . mysqli_error($conn) . '|0000'; exit; }
        if ( mysqli_num_rows($result) > 0 ) { 
        
        // есть запись про отправленную SMS без введенного OTP. берем из базы тот OTP и повторно возвращаем клиенту для ввода
        $data=array(); while($row = $result->fetch_array()) { $data[] = $row; }
        echo 'ok||' . $data[0]['OTP'];
        exit;
        }



        // insert "wish" into DB
        $q='insert into booking(timestamp, ip, date, timeslot, phone, status, OTP) values ("'
            . $nowYMDHS . '", '
            . '"' . ip() . '", '
            . '"' . date("Y-m-d", $date)  . '", '
            . $slot  . ', '
            . '"' .   substr(preg_replace('/\D/', '', $phone), -10)    . '", '
            . 50 . ', "")'; // 50 = "Получен запрос на визит"
        $result = mysqli_query($conn, $q);
        if ( ! $result ) {  echo "error|error #204: " . mysqli_error($conn) . '|0000'; exit; }


        // generate OTP
        $OTP=""; for($i = 0; $i < 4; $i++) $OTP .= mt_rand(0, 9);

        // send SMS and check response
        $ctx = stream_context_create(array('http' => array('timeout' => 30)));
        $url="https://smsc.ua/sys/send.php?fmt=1&login=yaktreba&psw=68AWtBVyX6WsrDt6&phones=" . $phone .  "&mes=Введіть цей код на сайті: " . $OTP;
        # TMP DISABLED:
        $response = file_get_contents($url, 0, $ctx);
        [ $newid, $sendresult ] = explode(',', $response);

        // insert "sms-sent" into DB (with OTP)
        $q='insert into booking(timestamp, ip, date, timeslot, phone, status, OTP) values ("'
            . $nowYMDHS . '", '
            . '"' . $newid . '", '
            . '"' . date("Y-m-d", $date)  . '", '
            . $slot  . ', '
            . '"' .   substr(preg_replace('/\D/', '', $phone), -10)    . '", '
            . 60 . ', "'
            . $OTP . '")'; // 60 = "SMS отправлена"
        $result = mysqli_query($conn, $q);
        if ( ! $result ) {  echo "error|error #205: " . mysqli_error($conn) . '|0000'; exit; }


        // return with OTP
        echo 'ok| |' . $OTP;
        exit;





    case 'book_stage_2':
        // parse parameter
        $date  = isset($_GET['date'])   ?  $_GET['date']   :  "";
        $slot  = isset($_GET['slot'])   ?  $_GET['slot']   :  "";
        $phone = isset($_GET['phone'])  ?  $_GET['phone']  :  "";

        // insert into 'booking'
        $q='insert into booking(timestamp, ip, date, timeslot, phone, status, OTP) values ("'
        . $nowYMDHS . '", '
        . '"' . ip() . '", '
        . '"' . date("Y-m-d", $date)  . '", '
        . $slot  . ', '
        . '"' .   substr(preg_replace('/\D/', '', $phone), -10)   . '", '
        . 80 . ', "")'; // 80 = "OTP введен правильно, заявка на визит принята (сразу создавать запись в списке нарядов и сообщить менеджеру)"

        $result = mysqli_query($conn, $q);
        if ( ! $result ) {
            echo "error|error #201: " . mysqli_error($conn) . "|0000";
            exit;
        }

        // send SMS with approve
        $hour=$slot % 3600; $minute=($slot - ($hour * 3600)) / 60;
        $ctx = stream_context_create(array('http' => array('timeout' => 30)));
        $url="https://smsc.ua/sys/send.php?fmt=1&login=yaktreba&psw=68AWtBVyX6WsrDt6&phones=" . $phone .  "&mes=Чекаємо Ваш автомобіль на шиномонтаж " . date('j.m.Y', $date) . " о " . date("H:i", $date + $slot) . ". Гарного дня!";
        $response = file_get_contents($url, 0, $ctx);
        [ $newid, $sendresult ] = explode(',', $response);
        // TODO: check SMS ISP answer and do something


        // report result
        echo 'ok' . '|' . date("d.m.Y", $date ) . '|' . date("H:i", $date + $slot);

        exit;



    case 'switch_timeslot':

        $date  = isset($_GET['date'])   ?  $_GET['date']   :  "";
        $slot  = isset($_GET['slot'])   ?  $_GET['slot']   :  "";

        $q="select id, disabled from booking where date='" . date("Y-m-d", $date) . "' and timeslot='" . $slot . "'";
        $result = mysqli_query($conn, $q);
        if ( ! $result ) {  echo "error|error #231: " . mysqli_error($conn) . '|0000'; exit; }
        $data=array(); while($row = $result->fetch_array()) { $data[] = $row; }

        # negate value
        $disabled = ( $data[0]['disabled'] == 1 ? 0 : 1 );

        # update value (insert or delete)
        $q = "insert into booking (timestamp, ip, date, timeslot, phone, status, disabled, OTP) values ('" . $nowYMDHS . "', '127.0.0.1', '" . date('Y-m-d', $date) . "','" . $slot . "', 'службове', '80', '" . $disabled . "', '')";
        if ( $data[0]['id'] > 0 ) $q = "delete from booking where id='" . $data[0]['id'] . "'";

        $result = mysqli_query($conn, $q);
        if (!$result) { echo "error|error #232: " . mysqli_error($conn) . '|0000'; exit; }

        echo 'ok' . '||0000';
        exit;




}

// var_dump($_GET);
echo "===DEFAULT END===:" . $action;


mysqli_close($conn);
