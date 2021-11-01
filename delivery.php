<?php

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
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }


// parse parameters
$smsid    = isset($_POST['id'])       ? $_POST['id']         :  ""; // for report
$status   = isset($_POST['status'])   ? $_POST['status']     :  ""; // for report


    switch ($status) {

        case '1': {
                // найти в базе запись с номером ранее отправленной SMS и взять из неё остальные данные
                $q='select date, timeslot, phone from booking where ip=' . $smsid;
                $result = mysqli_query($conn, $q);
                if ( ! $result ) {  echo "error|error #206: " . mysqli_error($conn) . '|0000'; exit; }
                if ( mysqli_num_rows($result) < 1 ) { echo 'error|error #207: there is no sms with this id!|0000'; exit; }
                foreach ($result as $row) { $date = $row['date']; $timeslot = $row['timeslot']; $phone = $row['phone']; }

                $q='insert into booking(timestamp, ip, date, timeslot, phone, status) values ("'
                    . $nowYMDHS . '", '
                    . '"' . $smsid . '", '
                    . '"' . $date  . '", '
                    . $timeslot  . ', '
                    . '"' .   substr(preg_replace('/\D/', '', $phone), -10)    . '", '
                    . 70 . ')'; // 70 = "SMS доставлена"

            $result = mysqli_query($conn, $q);

                if ( ! $result ) {  echo "error|error #205: " . mysqli_error($conn) . '|0000'; exit; }
                break;
        }


            default: {  file_put_contents('/tmp/sms_delivery.log', "get unknown status=" . serialize($_POST)  . "\r\n", FILE_APPEND);
                        break;
                     }
        }
