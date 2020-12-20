<?php
// This is a general, system-wide action handler.
// If you want to add functions to your theme, 
//   then please consider adding them in your theme-specific action handler,
//   located at "./themes/$theme/action.php".

require_once("func.php");
$theme = getSetting('theme');
require_once("./themes/$theme/action.php");

$action = $_POST['action'];

switch ($action) {
    case 'setLang':
        setLang();
        break;
    default:
       dieVars(false, false, 'Invalid request.');
}

function setLang(){
    $cookieLife = getLongCookieTime();
    $lang = $_POST['lang'];
    $langList = getLangList();
    if (!in_array($lang, $langList)){
        dieVars(true, false, 'Language invalid.');
    }else{
        $cookieLife = getLongCookieTime();
        setcookie("lang", $lang, $cookieLife);
        dieVars(true, true, '');
    }
}



?>