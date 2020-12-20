<?php
require_once('func.php');
$theme = getSetting('theme');
// Load theme itself
require_once("./themes/$theme/func.php");
require_once("./themes/$theme/index.php");
?>