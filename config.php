<?php
// HTTP
define('HTTP_SERVER', 'http://tires.yaktreba.com/');

// HTTPS
define('HTTPS_SERVER', 'https://tires.yaktreba.com/');

// DIR
define('DIR_ROOT',			                  '/var/www/tires.yaktreba.com/');
define('DIR_APPLICATION',	DIR_ROOT        . 'catalog/');
define('DIR_SYSTEM',		DIR_ROOT        . 'system/');
define('DIR_IMAGE',		    DIR_ROOT        . 'image/');
define('DIR_STORAGE',		DIR_ROOT        . 'storage/');
define('DIR_LANGUAGE',		DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE',		DIR_APPLICATION . 'view/theme/');
define('DIR_CONFIG',		DIR_SYSTEM      . 'config/');
define('DIR_CACHE',		    DIR_STORAGE     . 'cache/');
define('DIR_DOWNLOAD',		DIR_STORAGE     . 'download/');
define('DIR_LOGS',		    DIR_STORAGE     . 'logs/');
define('DIR_MODIFICATION',	DIR_STORAGE     . 'modification/');
define('DIR_SESSION',		DIR_STORAGE     . 'session/');
define('DIR_UPLOAD',		DIR_STORAGE     . 'upload/');

// DB
define('DB_DRIVER',   'mysqli');
define('DB_HOSTNAME', 'localhost');
define('DB_USERNAME', 'tires-user');
define('DB_PASSWORD', 'mmeS}p~cB/8A9V7n');
define('DB_DATABASE', 'tires');
define('DB_PORT',     '3306');
define('DB_PREFIX',   '');


// set timezone
date_default_timezone_set('Europe/Kiev');



// read settings from DB and propagate to variables
$conn = mysqli_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }
if ( $result = mysqli_query($conn, 'select * from settings') ) { foreach ($result as $row) {  ${$row['variable']} = $row['value']; } }
/* 
$timeslotsize = 40 * 60; // размер таймслота, в секундах
$work_time_start = 8 * 60 * 60; // время начала рабочего дня (в секундах).
$work_time_end = 20 * 60 * 60 - $timeslotsize;  // время конца рабочего дня - точнее время, когда ещё можно принимать заказ (в секундах). // TODO: учитывать разную продолжительность рабочего дня в разные дни недели и пред-праздничные
$working_days_of_week = 6; // максимальный номер рабочего дня в неделе (сколько рабочих дней в неделе)
$max_workplace = 2; // количество рабочих мест (постов)
*/


