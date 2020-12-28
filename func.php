<?php
$resumate_version = "0.0.1";
$currentLang = currentLang();

function dieVars($confirmed, $success, $message, $extra=null){
	$returnData = array("confirmed" => $confirmed, 'success' => $success, 'confirmString' => $message, 'data' => $extra);
	dieJSON($returnData);
}

function dieJSON($array){
	$echoJSON = json_encode($array);
	if ($echoJSON != NULL){
		die($echoJSON);
	}else{
		die(json_encode(array('confirmed' => false, 'success' => false, 'confirmString' => 'dieJSON()传入参数错误!')));
	}
}

function loadfile($dirlist, $filename, &$defFromDir){
    foreach ($dirlist as $dir){
        $fullname = $dir . $filename;
        if (file_exists($fullname) && ($fp = fopen($fullname, "r")) !== false){
            $payload = file_get_contents($fullname);
            fclose($fp);
            $defFromDir = $dir;
            return $payload;
        }
    }
    return false;
}

function isAssoc(array $arr){
    if (array() === $arr) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}

function checkAbsPath($path){
    if (substr($path, 0, 2) == "//" || substr($path, 0, 7) == "http://" || substr($path, 0, 8) == "https://"){
        return true;
    }
    return false;
}

function loadSettings($dir='./'){
    if (!array_key_exists('resumate_settings', $GLOBALS)){
        $data = loadfile([$dir], "settings.json", $dir);
        if ($data === false){
            return false;
        }
        $GLOBALS['resumate_settings'] = json_decode($data, true);
    }
    return $GLOBALS['resumate_settings'];
}

function getSetting($key){
    $settings = loadSettings();
    return $settings[$key];
}

function getLocalizedSetting($key){
    return getLocalizedString(getSetting($key));
}

function getLangFile($lang){
    $langFile = getSetting('lang')[$lang]['file'];
    $lang = json_decode(file_get_contents('./definitions/lang/' . $langFile), true);
    if ($lang['type'] == ''){
        die("File $langFile error: Unable to parse JSON, or failed to open file.");
    }
    if (!array_key_exists('vars', $lang)){
        $lang['vars'] = [];
    }
    return $lang['vars'];
}

function check_var($input){
    if (gettype($input) != gettype('')) return false;
    if (strlen($input) > 0 && $input[0] == '$' && $input[-1] == '$'){
        return substr($input, 1, -1);
    }
    return false;
}

function getLocalizedString($str){
    if (($keyname = check_var($str)) !== false){
        $langVars = getLangFile(currentLang());
        if (array_key_exists($keyname, $langVars)){
            return $langVars[$keyname];
        }else{
            return $str;
        }
    }else{
        return $str;
    }
}

function getLangList(){
    return array_keys(getSetting('lang'));
}

function getCookieTimeYears($year){
    return time()+60*60*24*365*$year;
}

function getLongCookieTime(){
    return getCookieTimeYears(100);
}

function currentLang(){
    $defaultLang = getSetting('defaultlang');
    $allLang = getLangList();
    $cookieLife = getLongCookieTime();
    if (!array_key_exists('lang', $_COOKIE)){
        $acceptLang = explode(',', str_replace(' ', '', strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'])));
        foreach ($acceptLang as $entry){
            $tempLang = explode(';', $entry)[0];
            foreach($allLang as $curLang){
                if ($tempLang == $curLang){
                    setcookie("lang", $tempLang, $cookieLife);
                    return $tempLang;
                }
            }
            //fuzzy search
            //try the main language
            $mainLang = explode('-', $tempLang)[0];

            foreach($allLang as $curLang){
                $allMainLang = explode('-', $curLang)[0];
                if ($mainLang == $allMainLang){
                    setcookie("lang", $mainLang, $cookieLife);
                    return $mainLang;
                }
            }
        }
        setcookie("lang", $defaultLang, $cookieLife);
        return $defaultLang;
    }else{
        $gotcookie = $_COOKIE['lang'];
        if (in_array($gotcookie, $allLang)){
            return $gotcookie;
        }else{
            setcookie("lang", $defaultLang, $cookieLife);
            return $defaultLang;
        }
    }
}

?>