<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Version
define('VERSION', '1.0.0.0');

// Configuration
if (is_file('config.php')) {
    require_once('config.php');
}

// mySnippets
if (is_file('snippets.php')) {
    require_once('snippets.php');
}


// брать из настроек из базы
// connect to DB
$conn = mysqli_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }
if ( $result = mysqli_query($conn, 'select * from settings') ) { foreach ($result as $row) {  ${$row['variable']} = $row['value']; } }

/* теперь настройки берутся из базы
$timeslotsize = 40 * 60; // размер таймслота, в секундах
$work_time_start = 8 * 60 * 60; // время начала рабочего дня (в секундах).

$work_time_end = 20 * 60 * 60 - $timeslotsize;  // время конца рабочего дня - точнее время, когда ещё можно принимать заказ (в секундах). 
// TODO: учитывать разную продолжительность рабочего дня в разные дни недели и пред-праздничные

$working_days_of_week = 6; // максимальный номер рабочего дня в неделе (сколько рабочих дней в неделе)
$max_workplace = 2; // количество рабочих мест (постов)
*/
echo "<script>var max_workplace=" . $max_workplace . ";</script>";



?>
<!DOCTYPE html>
<html>

<head>
    <title>ЯкТреба &bull; Шиномонтаж</title>

    <link rel="icon" type="image/png" href="favicon.png" />

    <meta http-equiv="cache-control" content="max-age=0" />
    <meta http-equiv="cache-control" content="no-cache" />
    <?php
    // принудительно перезагружать страницу на следующее утро
    echo "<meta http-equiv='expires' content='" . date("D, d M Y 3:33:33", date('U', strtotime('05:00:00')) + 86400) . " GMT' />";
    ?>
    <meta http-equiv="pragma" content="no-cache" />
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, maximum-scale=1, minimum-scale=1" />
    <link rel="stylesheet" type="text/css" href="css/default.css" media="all" />
    <link rel="stylesheet" type="text/css" href="css/tires.css" media="all" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/bxslider/4.2.12/jquery.bxslider.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bPopup/0.11.0/jquery.bpopup.min.js"></script>
    <script src="https://cdn.jsdelivr.net/bxslider/4.2.12/jquery.bxslider.min.js"></script>



    <script>
        function print_r(arr, level) {
            var print_red_text = "";
            if (!level) level = 0;
            var level_padding = "";
            for (var j = 0; j < level + 1; j++) level_padding += "    ";
            if (typeof(arr) == 'object') {
                for (var item in arr) {
                    var value = arr[item];
                    if (typeof(value) == 'object') {
                        print_red_text += level_padding + "'" + item + "' :\n";
                        print_red_text += print_r(value, level + 1);
                    } else
                        print_red_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
                }
            } else print_red_text = "===>" + arr + "<===(" + typeof(arr) + ")";
            return print_red_text;

            // useage:
            // console.log(print_r(JSON.parse(result)));
        }





        function book(slot_in) {
            let date = Number(slot_in.substr(slot_in.indexOf('-day-') + 5, 10));
            let slot = Number(slot_in.substr(slot_in.indexOf('-day-') + 16, 5));

            let phone = "+380";
            phone_validated = false;
            while (!phone_validated) { // validate phone
                if (phone === null) return false; // отмена ввода по esc
                phone = prompt("Введіть, будь-ласка, Ваш номер телефону для запису на шиномонтаж", phone);
                phone_validated = /^(?:\+\d{2})?\d{10}(?:,(?:\+\d{2})?\d{10})*$/gm.test(phone);
            };


            // у нас есть правильный телефон, дергаем хук для записи в базу и получения OTP
            $.ajax({
                url: "timeslot.php?action=book_stage_1&date=" + date + '&slot=' + slot + '&phone=' + phone,
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

                    // DEBUG OTP
                    // console.log(OTP); alert("debug: OTP=" + OTP);

                    // TODO: 2) если ответ "вы уже заебали своими запросами" - выводить соотв. сообщение

                    let user_input = '';
                    OTP_validated = false;
                    while (!OTP_validated) {
                        if (user_input === null) return false; // отмена ввода по esc
                        user_input = prompt("Введіть код з SMS", user_input);
                        if (user_input == OTP) OTP_validated = true;
                    };


                    // OTP введен правильно, дергаем хук для создания наряда, выводим сообщение
                    $.ajax({
                        url: "timeslot.php?action=book_stage_2&date=" + date + '&slot=' + slot + '&phone=' + encodeURIComponent(phone),
                        method: 'get',
                        cache: false,
                        success: function(result) {
                            console.log(result);
                            let [errorlevel, scheduled_date, scheduled_time] = result.split('|');
                            if (errorlevel == 'ok')
                                alert('Ваша заявка прийнята. Очікуємо Ваш автомобіль ' + scheduled_date + ' о ' + scheduled_time + '.\r\nГарного дня!');
                            return true; // STOP execution and exit
                        } // end success function "book_stage_2"
                    }); // end ajax "book_stage_2"



                } // end success function "book_stage_1"
            }); // end ajax "book_stage_1"
        }



        function get_phones_on_timeslot_crlf(arr, timeslot) {
            let result = "";
            for (item = 0; item < arr.length; item++)
                if (arr[item]['timeslot'] == timeslot) result = result + '\r\n' + arr[item]['phone'];
            return result;
        }

        function get_phones_on_timeslot_comma(arr, timeslot) {
            let result = "";
            for (item = 0; item < arr.length; item++)
                if (arr[item]['timeslot'] == timeslot) result = arr[item]['phone'] + ', ' + result;
            return result;
        }


        function draw_timeslots(date) {

            // получить из базы данные этого дня
            $.ajax({
                url: "timeslot.php?action=get_booked_timeslots&date=" + date,
                method: 'get',
                cache: false,
                success: function(result) {

                    let slots = JSON.parse(result); // полученные занятые таймслоты из базы

                    // console.log(print_r(slots));
                    // const counts = {};
                    // slots.forEach(function (x) { counts[x] = (counts[x] || 0) + 1; });
                    //console.log(print_r(counts));


                    // посчитать сколько упоминаний каждого таймслота
                    let qty = [];
                    for (dbslot = 0; dbslot < slots.length; dbslot++) {
                        let timeslot = slots[dbslot]['timeslot'];
                        if (typeof qty[timeslot] === 'undefined') qty[timeslot] = 0;
                        qty[timeslot]++;
                    }


                    // перебор таймслотов (кнопок)
                    buttons = document.getElementById(date).getElementsByClassName('time_button');

                    for (button = 0; button < buttons.length; button++) {
                        occupied = document.getElementById(buttons[button].id);

                        // дефолтный цвет доступных кнопок (зеленый)
                        button_style = "background-color:#11be7b";
                        button_title = "доступно до броньювання";
                        button_handler = "book('" + occupied.id + "');";

                        // если текущее время больше времени таймслота - дизаблить кнопку в серый
                        let time = Math.round(Date.now() / 1000);
                        button_time = Number(occupied.id.substr(occupied.id.indexOf('-day-') + 5, 10)) + Number(occupied.id.substr(occupied.id.indexOf('-day-') + 16, 5));

                        if ((time + 60) > button_time) {
                            // кнопка в прошлом, красим в серый, убираем обработчики
                            button_style = "background-color:grey";
                            button_title = "Це вже в минулому...";
                            button_handler = 'alert("записуватить в минуле неможливо :)");';
                        } else
                            // текущее время не наступило для кнопки. делаем перебор будущих ЗАНЯТЫХ таймслотов (из базы)
                            for (dbslot = 0; dbslot < slots.length; dbslot++) {

                                if (occupied.id == "timeslot-" + date + "-" + slots[dbslot]['timeslot']) {

                                    // посчитать кол-во записей на этот таймслот и красить соответвенно "загрузке постов"
                                    // и если записей больше, чем кол-во доступных постов - красить в красный и вешать хендлер лишь с уведомлением
                                    // иначе - обычный хендлер "записаться"
                                    if (max_workplace <= qty[slots[dbslot]['timeslot']]) {
                                        // MAX capacity
                                        button_style = "background-color:#f85656";
                                        button_title = 'заброньовано на ' + get_phones_on_timeslot_crlf(slots, slots[dbslot]['timeslot']);
                                        button_handler = 'alert("всі місця на цей час заброньовані на ' + get_phones_on_timeslot_comma(slots, slots[dbslot]['timeslot']) + '");';
                                        break;
                                    } else {
                                        // AVAILABLE
                                        button_style = "background-color:#6cbe11";
                                        button_title = 'вже заброньовано на ' + get_phones_on_timeslot_crlf(slots, slots[dbslot]['timeslot']);
                                        button_handler = "book('" + occupied.id + "');";
                                        break;
                                    }
                                }
                            }

                        // применяем стиль для текущей кнопки
                        occupied.style = button_style;
                        occupied.title = button_title;
                        occupied.setAttribute("onClick", button_handler);

                    } // end перебора кнопок
                }
            });
        }



        function main() {
            // перебор всех div-с-днями
            days = document.getElementsByClassName('schedule_column');
            for (day = 0; day < days.length; day++) draw_timeslots(days[day].id);
        }



        $(document).ready(function() {
            $('.slider').bxSlider({
                infiniteLoop: false,
                hideControlOnEnd: true,
                mode: 'horizontal',
                captions: false,
                keyboardEnabled: true,
                touchEnabled: false
            });
            setInterval('main()', 1000);
        });


        // force refresh page after expiring
        const expiresHeader = document.querySelector("meta[http-equiv='expires']").getAttribute('content');
        let time_to_refresh = new Date(expiresHeader).getTime() - (new Date()).getTime();
        console.log("force refresh page at ", document.querySelector("meta[http-equiv='expires']").getAttribute('content'));
        console.log("seconds to refresh:", Math.round(time_to_refresh / 1000));
        if (time_to_refresh > 0) setInterval(function() {
            refreshpage();
        }, time_to_refresh);
        refreshpage = () => {
            window.location.reload(true);
        };
    </script>


</head>

<body>
    <h2 style='color:yellow'>запис на шиномонтаж «Як Треба»<br>Рівне, Костромська, 25</h2>
    <div class=slider>
        <?php



        // init vars
        $weekday_names = array('', 'понеділок', 'вівторок', 'середу', 'четвер', 'п\'ятницю', 'суботу', 'неділю', 'наступний понеділок', 'наступний вівторок', 'наступну середу', 'наступний четвер', 'наступну п\'ятницю', 'наступну суботу', 'наступну неділю');
        $weekday_shift = array('сьогодні, ', 'завтра, ', 'післязавтра, ', '', '', '', '', '', '', '', '', '', '', '');
        $count = 0;
        $days_shown = 0;
        $check_today = 0;
        $nextweek = 0;
        $week_overflow = 0;
        $day_of_week = $pre_day_of_week = 0;

        // текущее состояние
        $tmp_date = new DateTime("00:00:00", new DateTimeZone('Europe/Kiev'));
        $weekday_today = $tmp_date->format('N');
        $epoch_today = $tmp_date->format('U');

        // $weekday_today=date('N'); // номер текущего дня недели (начало перебора), 1 for Monday through 7 for Sunday
        // $epoch_today=date('U', strtotime('00:00:00'));  // 00:00 текущей даты (в виде unix epoch), в качестве точки отсчета

        # TMP DEBUG
        # $weekday_today=date('N', strtotime("30 october 2021")); // номер текущего дня недели (начало перебора), 1 for Monday through 7 for Sunday
        # $epoch_today=date('U', strtotime('30 october 2021 00:00:00'));  // 00:00 текущей даты (в виде unix epoch), в качестве точки отсчета



        // ------------ [ перебираем дни формируя список из 7 рабочих дней ] ------------
        do {

            $check_today = $day_of_week = $weekday_today + $count; // сумма "день недели начала цикла" + "счетчик"
            $day_of_week = $day_of_week - (ceil($day_of_week / 7) * 7) + 7; // день недели цикла от 1 до 7

            $date = $epoch_today + ($count * 86400); // дата предполагаемого дня (в виде unix epoch)
            $shift = intval(round(($date - $epoch_today) / 86400)); // разница между началом цикла и текущей итерацией

            if ($pre_day_of_week > $day_of_week) $nextweek = 1; // если было "переполнение" недели, то есть был переход "воскресение 7 -> понедельник 1" - ставим метку "след.неделя"
            $pre_day_of_week = $day_of_week;

            if (($nextweek == 1) and $weekday_today < 4) $week_overflow = 1;                               // если был переход через воскресение и начало перебора меньше четверга - писать "на следующей неделе"
            if (($nextweek == 1) and abs($weekday_today - $check_today) > 5) $week_overflow = 1;  // если был переход через воскресение и разница между началом и предполагаемым днем больше 5 дней - писать "на следующей неделе"

            // TODO: искать по in_array попадает ли на день праздник (список - из базы)

            // если текущий день цикла - будний день, выводим на экран
            if ($day_of_week <= $working_days_of_week) {
                $days_shown++;
                $timeslot = $work_time_start;

                // ------------ [ вывод таймслотов от начала рабочего дня до конца ] ------------
                $this_day_epoch_begin = $epoch_today + $count * 86400; // unix epoch прогнозируемого дня 00:00:00
                $is_dst = date("I", $this_day_epoch_begin); // 1 - летнее время, 0 - нет

                echo "<div class=schedule_column id='day-" . $this_day_epoch_begin . "'>";
                echo "<h3><b>" . "на " . $weekday_shift[$shift] . $weekday_names[$day_of_week + $week_overflow * 7] . ' ' . date('d.m.Y', $date) . "</b></h3>";

                do {
                    $tmp_a = $this_day_epoch_begin + $timeslot;
                    // умножать счетчик на размер таймслота
                    echo "<button class='time_button' id='timeslot-day-" . $this_day_epoch_begin . '-' . $timeslot . "' >" . date("H:i", $tmp_a) . "</button>" . $eol;
                    $timeslot += $timeslotsize;
                } while ($timeslot <= $work_time_end);

                echo "</div>";
            };
            $count++;
        } while ($days_shown < 7);


        ?>

    </div>
    <h3 center style='color:white'>Бажаєте скасувати або перенести візит ?<br>Телефонуйте менеджерам +380961903030</h3>
</body>

</html>