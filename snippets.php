<?php


// EOL depends on run method (system EOL or "<br>"
$eol = (isset($_SERVER['SHELL'])) ? PHP_EOL : "<br />";

$nowYMDHS = date("Y/m/d H:i:s");


function ip()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}


?>
