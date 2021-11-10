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

# INIT VARS
$login_from_db = $pass_from_db = '';

# get form's data
$login  = isset($_POST['login'])    ?  $_POST['login']  :  "";
$pass   = isset($_POST['pass'])     ?  $_POST['pass']   :  "";
$action = isset($_POST['action'])  ?  $_POST['action'] :  "";


# get session cookie
$cookie = isset($_COOKIE['dashboard']) ? $_COOKIE['dashboard'] : "";
if ( $cookie == 'Yak***Treba' ) $pass_validated=true; else $pass_validated = false;


# get login/password from DB and compare MD5 hash
if ($result = mysqli_query($conn, "select * from access where login='" . $login . "';")) {
    foreach ($result as $row) {
        if ($row['password'] == md5($pass)) {
            $pass_validated = true;
            setcookie('dashboard', 'Yak***Treba', time()+32400, '' , '', true); // 9 часов
            break;
        }
    }
}

# draw AUTH form
if ($pass_validated != true) { ?>
    <form method="POST">
        <div style="position: fixed; /* or absolute */
                    top: 50%; left: 50%;
                    width: 200px; height:100px;
                    margin: -50px 0 0 -100px;">
            <cpan style='text-align:center; font-family:Tahoma, Geneva, Verdana, sans-serif;'>шиномонтаж ЯкТреба<br>dashboard</cpan>
            <input type="text" name=login placeholder="login" autocomplete="off" autofocus>
            <input type="password" name=pass placeholder="password">
            <input type="submit">
        </div>
    </form>
<?php
exit;
}


?>
<!DOCTYPE html>
<html>

<head>
    <title>Шиномонтаж &bull; ЯкТреба &bull; админка </title>

    <link rel="icon" type="image/png" href="favicon-admin.png" />

    <meta name="robots" content="noindex, nofollow">

    <meta http-equiv="refresh" content="60">

    <meta http-equiv="cache-control" content="max-age=0" />
    <meta http-equiv="cache-control" content="no-cache" />
    <meta http-equiv="pragma" content="no-cache" />
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, maximum-scale=1, minimum-scale=1" />
    <link rel="stylesheet" type="text/css" href="css/tires.css" media="all" />


</head>
<body>
<a href=/settings.php>настройки</a><br>

<?php
$now_epoch=date('U', strtotime('00:00'));

for ($day=0; $day < 10; $day++) {
$day_counter = $now_epoch + $day * 86400;

    // все записи на это число
    $q="select * from booking where status>=80 and date='" . date('Y-m-d', $day_counter) . "' order by id";
    $result = mysqli_query($conn, $q);
    if ( ! $result ) {  echo "error|error #211: " . mysqli_error($conn) . '|0000'; exit; }
    if ( mysqli_num_rows($result) <= 0 ) { continue; } // если записей на этот день нет - пропускаем этот день

    echo "<h4><b>" . $weekdaynames[date('N', $day_counter)] . date(', d.m.Y', $day_counter) . "</b></h4>";
    echo "<div class=content>";

    // перебор таймслотов от начала и до конца рабочего дня
    $horiz_cursor=$work_time_start;
    while ($horiz_cursor < $work_time_end) {
        echo "<div class=item_block>";
        echo "<p class=item_time><b>" . date("H:i", $now_epoch + $horiz_cursor) . "</b></p>";

        # вывод телефонов, записанных на этот таймслот
        foreach ($result as $timeslot) {
            if ( $timeslot['timeslot'] > $horiz_cursor - $timeslotsize && $timeslot['timeslot'] < $horiz_cursor + $timeslotsize) { // если курсор попадает между соседними таймслотами
            $highlight="";
            if ( date('U') - date('U', strtotime($timeslot['timestamp'])) < 600 ) $highlight="background-color:orange;"; // недавние обращение (меньше 600 секунд) - подсвечивать
            echo "<span class=item_phone style='" . $highlight . "; '" .
            " title='заказ поступил " . date('d.m.Y H:i', strtotime($timeslot['timestamp'])) . ' на таймслот ' . date("H:i", $day_counter + $timeslot['timeslot'])  . "'>" . 
            $timeslot['phone'] . "</span><input type=checkbox><br>";
            }
        }

        $horiz_cursor+=$timeslotsize;
        echo "</div>"; /* end div "item" */
    }
    echo "</div>"; /* end div "content " */
    echo "<hr>";
}

?>
</body>
</html>
