<?php
require_once('func.php');
header("X-Parsed-By: Resumate $resumate_version - Build your page using JSON. https://resumate.io/");

error_reporting(0);

$file = $_POST['filename'];
$theme = getSetting('theme');
$payload = file_get_contents('./definitions/pages/' . $file);

$usedCSS = [];
$usedJS = [];
$onloadJS = [];
$payload = json_decode($payload, true);
if ($payload['type'] == ''){
    die("File $file error: Unable to parse JSON, or failed to open file.");
}

$definition = [];
$loaddir = ['./', "./themes/$theme/definitions/pages/", "./themes/$theme/definitions/blocks/", "./themes/$theme/definitions/templates/", "./themes/$theme/definitions/html/", './definitions/pages/', './definitions/blocks/', './definitions/templates/', './definitions/html/'];

dieVars(true, true, '', parse($payload));

function parse($payload){
    global $usedCSS;
    global $usedJS;
    global $onloadJS;
    $result = '';
    $langVars = getLangFile(currentLang());
    $html = parse_all($payload, [], $langVars);
    $usedCSS = array_unique($usedCSS);
    $usedJS = array_unique($usedJS);
    $onloadJS = array_unique($onloadJS);
    
    foreach ($usedCSS as $css){
        $result .= "<link rel=\"stylesheet\" href=\"$css\">";
    }
    return ["html"=>$result . $html, "js"=>$usedJS];
}

function parse_all($payload, $attrenv, $varenv){
    global $loaddir;
    global $usedCSS;
    global $usedJS;
    global $definition;
    $attrenv = array_replace($attrenv);
    if (!array_key_exists('style', $attrenv)){
        $attrenv['style'] = [];
    }
    if (!array_key_exists('class', $attrenv)){
        $attrenv['class'] = [];
    }
    $varenv = array_replace([], $varenv);
    $return = '';
    if (gettype($payload) == gettype('')){
        if (($keyname = check_var($payload)) !== false){
            $newPayload = $varenv[$keyname];
            if (gettype($newPayload) == gettype([])){
                foreach ($newPayload as $np){
                    $return .= parse_all($np, $attrenv, $varenv);
                }
            }else{
                $return .= $newPayload;
            }
            return $return;
        }else{
            return $payload;
        }
    }elseif ($payload['name'] == null && !isAssoc($payload)){
        foreach ($payload as $p){
            $return .= parse_all($p, $attrenv, $varenv);
        }
        return $return;
    }elseif ($payload['name'] != null){
        if ($definition[$payload['name']] == null){
            //load definition from JSON file
            $defFromDir = '';
            $result = loadfile($loaddir, $payload['name'] . '.json', $defFromDir);
            if ($result === false){
                die('Failed to open ' . $payload['name'] . '.json for parsing.');
            }
            $definition[$payload['name']] = json_decode($result, true);
            foreach ($definition[$payload['name']]['css'] as $css){
                $cfile = '';
                if (checkAbsPath($css)){
                    $cfile = $css;
                }else{
                    $cfile = $defFromDir . $css;
                }
                array_push($usedCSS, $cfile);
            }
            foreach ($definition[$payload['name']]['js'] as $js){
                $jfile = '';
                if (checkAbsPath($js)){
                    $jfile = $js;
                }else{
                    $jfile = $defFromDir . $js;
                }
                array_push($usedJS, $jfile);
            }
            foreach ($definition[$payload['name']]['onload'] as $js){
                array_push($onloadJS, $js);
            }
            if ($definition[$payload['name']]['default'] == null){
                $definition[$payload['name']]['default'] = [];
            }
            if ($definition[$payload['name']]['default']['attr'] == null){
                $definition[$payload['name']]['default']['attr'] = [];
            }
            if ($definition[$payload['name']]['default']['content'] == null){
                $definition[$payload['name']]['default']['content'] = [];
            }
        }
    
        switch ($definition[$payload['name']]['type']) {
            case "page":
                return parse_page($payload, $attrenv, $varenv);
                break;
            case "def":
                return parse_def($payload, $attrenv, $varenv);
                break;
            case "block":
                return parse_block($payload, $attrenv, $varenv);
                break;
            case "template":
                return parse_template($payload, $attrenv, $varenv);
                break;
        }
    }else{
        die('Malformed JSON.');
    }
}



function resolve_varenv($default, $defvars, $vars, $varenv){
    //default: def['default']['vars']
    //defvars: def['vars']
    //vars:    payload['vars']
    //varenv:  varenv

    //First:  varenv should merge with vars, and varenv will write over vars.
    //Second: For every key in defvars, if varenv does not have it, write it to varenv.
    //Third:  varenv should try to get values again. If something exists in varenv, then read from varenv
    //Fourth: If that is not the case, read from default.
    

    /*
    echo("----------------------------------------\n");
    var_dump($default);
    var_dump($defvars);
    var_dump($vars);
    var_dump($varenv);
    echo("\n");
    */
    
    $varenv = array_replace($vars, $varenv);
    foreach ($varenv as $vk=>$vv){
        if (($keyname = check_var($vv)) !== false){
            $varenv[$vk] = $varenv[$keyname];
        }
    }
    foreach ($defvars as $dk=>$dv){
        if (!array_key_exists($dk, $varenv)){
            $varenv[$dk] = $dv;
        }
    }
    foreach ($varenv as $vk=>$vv){
        if (($keyname = check_var($vv)) !== false){
            $varenv[$vk] = $varenv[$keyname];
        }else{
            if (($keyname = check_var($varenv[$dk])) !== false){
                if (array_key_exists($keyname, $default)){
                    $varenv[$dk] = $dv;
                }
            }
        }
    }

    /*
    echo("\n");
    var_dump($default);
    var_dump($defvars);
    var_dump($vars);
    var_dump($varenv);
    echo("----------------------------------------\n");
    */
    return $varenv;
}

function resolve_attrenv($default, $defattr, $attr, $attrenv, $varenv){

    //default: def['default']['attr']
    //defvars: def['attr']
    //attr:    payload['attr']
    //attrenv: attrenv

    //First:  attrenv should merge with attr, and attrenv will write over attr.
    //Second: For every key in defattr, if attrenv does not have it, write it to attrenv.
    //Third:  attrenv should try to get values again. If something exists in varenv, then read from varenv
    //Fourth: If that is not the case, read from default.

    $attrenv['style'] = array_replace($attr['style'], $attrenv['style']);
    $attrenv['class'] = array_replace($attr['class'], $attrenv['class']);
    $attrenv = array_replace($attr, $attrenv);
    /*
    echo("==============PHASE START==============\n\n");
    echo("\n-----------PHASE 1 START----------\n");
    var_dump($attrenv);
    echo("\n------------PHASE 1 END-----------\n");
    */
    foreach ($defattr as $dk=>$dv){
        if ($dk == 'style'){
            foreach ($defattr['style'] as $sk=>$sv){
                if (!array_key_exists($sk, $attrenv['style'])){
                    $attrenv['style'][$sk] = $sv;
                }
            }
        }elseif($dk == 'class'){
            $attrenv['class'] = array_merge($attrenv['class'], $defattr['class']);
        }else{
            if (!array_key_exists($dk, $attrenv)){
                $attrenv[$dk] = $dv;
            }
        }
    }

    foreach ($attrenv as $ak=>$av){
        if ($ak == 'style'){
            foreach ($attrenv['style'] as $sk=>$sv){
                if (($skeyname = check_var($sv)) !== false){
                    if (array_key_exists($skeyname, $varenv)){
                        $attrenv['style'][$sk] = $varenv[$skeyname];
                    }elseif (array_key_exists($skeyname, $default['style'])){
                        $attrenv['style'][$sk] = $default['style'][$skeyname];
                    }
                }
            }
        }elseif($ak == 'class'){
            foreach ($attrenv['class'] as &$class){
                if (($ckeyname = check_var($class)) !== false){
                    if (array_key_exists($ckeyname, $varenv)){
                        $class = $varenv[$ckeyname];
                    }elseif (count($default['class']) > 0){
                        $class = $default['class'];
                    }
                    
                }
            }
        }else{
            if (($keyname = check_var($av)) !== false){
                if (array_key_exists($keyname, $varenv)){
                    $attrenv[$ak] = $varenv[$keyname];
                }elseif (array_key_exists($keyname, $default)){
                    $attrenv[$ak] = $default[$keyname];
                }
            }
        }
    }
    /*
    echo("\n-----------PHASE 2 START----------\n");
    var_dump($attrenv);
    echo("\n------------PHASE 2 END-----------\n");
    echo("==============PHASE ENDED==============\n\n");*/
    return $attrenv;
}


function parse_page($payload, $attrenv, $varenv){
    global $usedCSS;
    global $usedJS;
    global $definition;
    $def = $definition[$payload['name']];
    
    mergecontentsanicheck($def, $varenv, $payload);
    $varenv = resolve_varenv($def['default']['vars'], $def['vars'], $payload['vars'], $varenv);
    mergeattrsanicheck($def, $attrenv, $payload);
    $attrenv = resolve_attrenv($def['default']['attr'], $def['attr'], $payload['attr'], $attrenv, $varenv);
    $return = '';
    foreach ($def['content'] as $c){
        if (gettype($c) == gettype('')){
            if ($c[0] == '$' && $c[-1] == '$'){
                $keyname = substr($c, 1, -1);
                $return .= $varenv[$keyname];
            }else{
                $return .= $c;
            }
        }elseif (gettype($c) == gettype([])){
            $return .= parse_all($c, $c['attr'], $varenv);
        }
    }
    return $return;
}

function parse_block($payload, $attrenv, $varenv){
    global $definition;
    $def = $definition[$payload['name']];
    mergecontentsanicheck($def, $varenv, $payload);
    $varenv = resolve_varenv($def['default']['vars'], $def['vars'], $payload['vars'], $varenv);
    mergeattrsanicheck($def, $attrenv, $payload);
    $attrenv = resolve_attrenv($def['default']['attr'], $def['attr'], $payload['attr'], $attrenv, $varenv);
    $return = '';
    foreach ($def['content'] as $c){
        $return .= parse_all($c, $c['attr'], $varenv);
    }
    return $return;
}

function parse_def($payload, $attrenv, $varenv){
    global $usedCSS;
    global $usedJS;
    global $definition;
    
    $def = $definition[$payload['name']];
    $start = '<' . $def['html'];
    //echo("BEFORE:\n");
    //var_dump($varenv);
    //echo("AFTER:\n");
    mergecontentsanicheck($def, $varenv, $payload);
    $varenv = resolve_varenv($def['default']['vars'], $def['vars'], $payload['vars'], $varenv);
    mergeattrsanicheck($def, $attrenv, $payload);
    $attrenv = resolve_attrenv($def['default']['attr'], $def['attr'], $payload['attr'], $attrenv, $varenv);
    
    $attrStr = "";
    foreach ($attrenv as $k=>$v){
        if ($k == "style"){
            $tmp = '';
            foreach ($v as $sk=>$sv){
                if ($sv[0] == '$' && $sv[-1] == '$'){
                    $keyname = substr($sv, 1, -1);
                    $sv = $attrenv[$keyname];
                    if ($sv == null) $sv = $varenv[$keyname];
                    if ($sv == null){
                        $sv = $def['default']['attr'][$keyname];
                        if ($sv[0] == '$' && $sv[-1] == '$'){
                            $sv = $varenv[$keyname];
                        }
                    }
                }
                if ($sv != null) $tmp .= "$sk: $sv; ";
            }
            $tmp = substr($tmp, 0, -1);
            if ($tmp != '') $attrStr .= " style=\"$tmp\"";
        }elseif ($k == "class"){
            $d = implode(' ', $v);
            $tmp = 'class="' . $d . '"';
            if ($d != '') $attrStr .= " $tmp";
        }else{
            if ($v[0] == '$' && $v[-1] == '$'){
                $keyname = substr($v, 1, -1);
                $v = $varenv[$keyname];
                if ($v == null){
                    $v = $def['default']['attr'][$keyname];
                    if ($v[0] == '$' && $v[-1] == '$'){
                        $v = $varenv[$keyname];
                    }
                }
            }
            $attrStr .= " $k=\"$v\"";
        }
    }
    $start .= "$attrStr>";
    $end = '';
    if ($def['close']){
        $end = '</' . $def['html'] . '>';
    }
    
    $return = '';
    if (!array_key_exists('content', $payload) || count($payload['content']) == 0){
        $payload['content'] = json_decode(json_encode($varenv['content']), true);
    }
    
    
    if (gettype($payload['content']) == gettype('')){
        if (($keyname = check_var($payload)) !== false){
            $return .= $varenv[$keyname];
        }else{
            $return .= $payload['content'];
        }
    }elseif (gettype($payload['content']) == gettype([])){
        foreach ($payload['content'] as $c){
            //echo("\n====================START===================\n");
            //var_dump($c);
            $varenv['content'] = parse_all($c, $c['attr'], $varenv);
            //echo("\n=================tempresult=================\n");
            //var_dump($tempresult);
            
            foreach ($def['content'] as $kd=>$d){
                if (gettype($d) == gettype('')){
                    //echo("\n======================d======================\n");
                    //echo($d);
                    if (($dkeyname = check_var($d)) !== false){
                        $content = $varenv[$dkeyname];
                        if (($keyname = check_var($content)) !== false){
                            $content = $varenv[$keyname];
                        }
                        $content = parse_all($content, $content['attr'], $varenv);
                        $return .= $content;
                    }else{
                        $return .= $d;
                    }
                }else{
                    //echo("Error");
                }
            }
            //echo("\n=====================END====================\n");
        }
    }
    return $start . $return . $end;
}
function parse_template($payload, $attrenv, $varenv){
    global $usedCSS;
    global $usedJS;
    global $definition;
    $def = $definition[$payload['name']];
    $return = '';
    //echo("Loading template: ");
    //var_dump($payload);
    //echo(" \n");
    
    mergecontentsanicheck($def, $varenv, $payload);
    $varenv = resolve_varenv($def['default']['vars'], $def['vars'], $payload['vars'], $varenv);
    mergeattrsanicheck($def, $attrenv, $payload);
    $attrenv = resolve_attrenv($def['default']['attr'], $def['attr'], $payload['attr'], $attrenv, $varenv);
    if ($payload['name'] == "asdfasdf"){
        echo("\n\n------------------NAME------------------\n\n");
        echo($payload['name']);
        echo("\n\n------------------ATTR------------------\n\n");
        var_dump($attrenv);
        echo("\n\n------------------CONTENT------------------\n\n");
        var_dump($varenv);
        echo("\n\n------------------PAYLOAD------------------\n\n");
        var_dump($payload);
        echo("\n\n------------------=END=------------------\n\n");
    }
    foreach ($def['content'] as $c){
        if (gettype($c) == gettype([])){
            $return .= parse_all($c, $c['attr'], $varenv);
        }
    }
    //print("-------------TEMPLATE START-------------\n");
    //print($return . "\n");
    //print("--------------TEMPLATE END--------------\n");
    return $return;
}
function mergecontentsanicheck(&$def, &$varenv, &$payload){
    if ($varenv == null){
        $varenv = [];
    }
    if ($def['default']['content'] == null){
        $def['default']['content'] = [];
    }
    if ($def['default']['vars'] == null){
        $def['default']['vars'] = [];
    }
    if ($def['vars'] == null){
        $def['vars'] = [];
    }
    if ($def['content'] == null){
        $def['content'] = [];
    }
    if ($payload['content'] == null){
        $payload['content'] = [];
    }
    if ($payload['vars'] == null){
        $payload['vars'] = [];
    }
}

function mergeattrsanicheck(&$def, &$attrenv, &$payload){
    
    if ($def['attr'] == null){
        $def['attr'] = [];
    }
    if ($def['default']['attr'] == null){
        $def['default']['attr'] = [];
    }
    if ($def['default']['attr']['style'] == null){
        $def['default']['attr']['style'] = [];
    }
    if ($def['default']['attr']['class'] == null){
        $def['default']['attr']['class'] = [];
    }
    
    if ($attrenv == null){
        $attrenv = [];
    }
    if ($attrenv['style'] == null){
        $attrenv['style'] = [];
    }
    if ($attrenv['class'] == null){
        $attrenv['class'] = [];
    }

    if ($payload['attr'] == null){
        $payload['attr'] = [];
    }
    if ($payload['attr']['style'] == null){
        $payload['attr']['style'] = [];
    }
    if ($payload['attr']['class'] == null){
        $payload['attr']['class'] = [];
    }
    
}

?>