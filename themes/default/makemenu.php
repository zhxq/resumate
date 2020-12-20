<?php
session_start();
require_once('func.php');
makeMenu($_POST['side'], $_POST['i']);
?>