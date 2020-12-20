<?php
session_start();
require_once('func.php');
$paraArray = json_decode($_POST['paraArrayString'],true);


getModal($_POST['file'], $_POST['title'], $_POST['button'], $_POST['onclick'], $_POST['close'], $paraArray);
?>