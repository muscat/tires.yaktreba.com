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

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <script src="js/webAudioApiForDesigners.js"></script>


    <script>
        function timeslot_switch(date, timeslot) {

        $.ajax({
                url: "timeslot.php?action=switch_timeslot&date=" + date + '&slot=' + timeslot,
                method: 'get',
                cache: false,
                success: function(result) {
                    console.log('result=' + result);

                    // результат делить на токены, проверять errorlevel
                    let [errorlevel, message, OTP] = result.split('|');
                    if (errorlevel == 'error') {
                        alert('внутрішня помилка');
                        console.log(message);
                        return false;
                    }
                    if (errorlevel == 'alert') {
                        alert('' + message);
                        return false;
                    }
                    if (errorlevel == 'ok') {
                        window.location.reload();
                    }
                }
            });
        }
    </script>


</head>
<body>
<a href=/settings.php>настройки</a><br>

<?php
$now_epoch=date('U', strtotime('00:00'));

for ($day=0; $day < 10; $day++) {
$day_counter = $now_epoch + $day * 86400;

    // получить из базы все записи на это число
    $q="select * from booking where status>=80 and date='" . date('Y-m-d', $day_counter) . "' order by id";
    $result = mysqli_query($conn, $q);
    if ( ! $result ) {  echo "error|error #211: " . mysqli_error($conn) . '|0000'; exit; }

    // отделяем недели
    if ( date('N', $day_counter) > $working_days_of_week) { echo "<br><br>"; continue; } 

    // данные из базы - в массив
    $data=array(); while($row = $result->fetch_array()) { $data[] = $row; }

    // заголовок дня и div
    echo "<h4><b>" . $weekdaynames[date('N', $day_counter)] . date(', d.m.Y', $day_counter) . "</b></h4>";
    echo "<div class=content>";

    
    // перебор таймслотов от начала и до конца рабочего дня
    $horiz_cursor=$work_time_start;
    while ($horiz_cursor < $work_time_end) {
     
        $is_timeslot_disabled=0; $timeslot_highlight=''; $timeslot_checked='';
        foreach ($data as $tmp) if ( $tmp['timeslot'] == $horiz_cursor && $tmp['disabled'] == 1 ) { $is_timeslot_disabled=1; $timeslot_highlight='background-color:grey;'; $timeslot_checked='checked'; break; }

        // timeslot div block
        echo "<div class=item_block name=" . date("H:i", $day_counter + $horiz_cursor) . ">";

        // timeslot header
        echo "<p class=item_time style='" . $timeslot_highlight . "'><b>" . date("H:i", $now_epoch + $horiz_cursor) . 
        "</b><input type=checkbox class=check_styled id=" . $day_counter . '-' . $horiz_cursor . " onclick=timeslot_switch('" . $day_counter . "','" . $horiz_cursor . "'); " . $timeslot_checked . "></p>";

        # вывод телефонов, записанных на этот таймслот
        foreach ($data as $phones) {
            // приходится выкручиваться и ловить записи по таймслотам, которые "съехали" из-за смены размера таймслота или изменения времени начала дня
            if ( $phones['timeslot'] > $horiz_cursor - $timeslotsize && $phones['timeslot'] < $horiz_cursor + $timeslotsize) {
            // недавние обращение (меньше 600 секунд) - подсвечивать
            $phone_highlight=""; if ( date('U') - date('U', strtotime($phones['timestamp'])) < 600 ) $phone_highlight="background-color:orange;"; 
            
            // недавние обращения - озвучивать (90 меньше чем 2*60 секунд, то есть звук сыграет 1 раз)
            if ( date('U') - date('U', strtotime($phones['timestamp'])) < 90 ) echo "<script> var context = initializeNewWebAudioContext();context.loadSound('sounds/fresh.ogg', 'fresh'); context.playSound('fresh');  </script>";
            
            echo "<span class=item_phone style='" . $phone_highlight . "; '" .
            " title='заказ поступил " . date('d.m.Y H:i', strtotime($phones['timestamp'])) . ' на таймслот ' . date("H:i", $day_counter + $phones['timeslot'])  . "'>" . 
            $phones['phone'] . "</span><input type=checkbox class=check_styled><br>";
            }
        }

        $horiz_cursor=$horiz_cursor + $timeslotsize;
        echo "</div>"; /* end div "item" */
    }
    echo "</div>"; /* end div "content " */
    echo "<hr>";
}

?>
</body>
</html>
