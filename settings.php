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
$login = isset($_POST['login'])    ?  $_POST['login']  :  "";
$pass  = isset($_POST['pass'])     ?  $_POST['pass']   :  "";
$action = isset($_POST['action'])  ?  $_POST['action'] :  "";

# got params, save to DB
if ($action == 'save') {
    foreach ($_POST as $item => $value) {
        $q = "update settings set value='" . $value . "' where variable='" . $item . "'";
        
        // отдельный костыль для сохранения праздничных дней
        if ( $item == 'holidays' ) { 

            $q = 'delete from holiday';
            $result = mysqli_query($conn, $q);

            $q = "insert into `holiday` (`date`) values "; 
            $dates=explode("\r", rtrim($value)); $count=count($dates);
            foreach ($dates as $holiday) { 
                $q=$q . "('" . $holiday . "')";
                $count--;
                if ( $count > 0 ) $q=$q . ',';  # запятую добавляем только если есть ещё элементы в массиве
            }; 
        $q=$q . ';';  }
        
        
        // echo $q . "<br>"; // DEBUG
        $result = mysqli_query($conn, $q);
        if (!$result) {
            echo "error|error #210: " . mysqli_error($conn) . '|0000';
            exit;
        }
    }
    echo "updated!<br>";
    echo "<a href=/dashboard.php>назад</a>";
    exit;
}

# get session cookie
$cookie = isset($_COOKIE['dashboard']) ? $_COOKIE['dashboard'] : "";
if ( $cookie == 'Yak***Treba' ) $pass_validated=true; else $pass_validated = false;

# AUTH by password
if ($result = mysqli_query($conn, "select * from access where login='" . $login . "';")) {
    foreach ($result as $row) {
        if ($row['password'] == md5($pass)) {
            $pass_validated = true;
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
            <cpan style='text-align:center; font-family:Tahoma, Geneva, Verdana, sans-serif;'>шиномонтаж ЯкТреба<br>настройки</cpan>
            <input type="text" name=login placeholder="login" autocomplete="off" autofocus>
            <input type="password" name=pass placeholder="password">
            <input type="submit">
        </div>
    </form>

<?php
    exit;
}


# edit settings form
echo "<form method=POST>";
echo "<input type=hidden name=action value=save>";
if ($result = mysqli_query($conn, "select * from settings")) {
    foreach ($result as $key => $value) {
        echo "<input type=text name='" . $value['variable'] . "' value='" . $value['value'] . "'>" . $value['comment'] . "<br>";
    }
}


// получить список дат праздничных дней
$q_holiday='select distinct * from holiday order by date';
$result_holiday = mysqli_query($conn, $q_holiday);
if ( ! $result_holiday ) {  echo "error|error #101: " . mysqli_error($conn) . '|0000'; exit; }
$holidays=array(); while($row = $result_holiday->fetch_array()) { $holidays[] = $row[0]; }
echo "<br>список праздничных дней (в формате YYYY-MM-DD, каждая дата - в новой строке)<br>";
echo "<textarea name=holidays rows=" . intval(count($holidays) + 1) . ">"; 
foreach ($holidays as $holiday) { echo $holiday . "\r\n"; }; 
echo "</textarea><br>";


echo "<input type=submit>";
echo "<input type=button onclick='history.back();' value=Cancel>";
echo "</form>";
