<?php
require_once('func.php');
header("X-Parsed-By: Resumate $resumate_version - Build your page using JSON. https://resumate.io/");

$file = $_POST['filename'];
$theme = getSetting('theme');
$payload = file_get_contents('./definitions/pages/' . $file);

$usedCSS = [];
$usedJS = [];
$onloadJS = [];
$payload = json_decode($payload, true);
if ($payload['type'] == ''){
    dieVars(false, false, '', ['html'=>"File $file error: Unable to parse JSON, or failed to open file.", 'js'=>[]]);
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
    return ['html'=>$result . $html, 'js'=>$usedJS];
}

function parse_all($payload, $attrenv, $varenv){
    global $loaddir;
    global $usedCSS;
    global $usedJS;
    global $definition;
    if ($attrenv == null) $attrenv = [];
    $attrenv = array_replace([], $attrenv);
    if (!array_key_exists('style', $attrenv)){
        $attrenv['style'] = [];
    }
    if (!array_key_exists('class', $attrenv)){
        $attrenv['class'] = [];
    }
    if ($varenv == null) $varenv = [];
    $newvarenv = json_decode(json_encode($varenv), true);
    $return = '';
    if (gettype($payload) == gettype('')){
        if (($keyname = check_var($payload)) !== false){
            $newPayload = $newvarenv[$keyname];
            if (gettype($newPayload) == gettype([])){
                foreach ($newPayload as $np){
                    $return .= parse_all($np, $attrenv, $newvarenv);
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
            $return .= parse_all($p, $attrenv, $newvarenv);
        }
        return $return;
    }elseif ($payload['name'] != null){
        if (gettype($payload['name']) == gettype('')){
            $payload['name'] = [$payload['name']];
        }
        foreach ($payload['name'] as &$ve){
            if ($ve[0] == '$' && $ve[-1] == '$'){
                $keyname = substr($ve, 1, -1);
                $ve = $varenv[$keyname];
                if ($ve == null){
                    $ve = $def['default']['attr'][$keyname];
                    if ($ve[0] == '$' && $ve[-1] == '$'){
                        $ve = $varenv[$keyname];
                    }
                }
            }
        }
        $payload['name'] = implode('', $payload['name']);

        if (!array_key_exists($payload['name'], $definition)){
            //load definition from JSON file
            $defFromDir = '';
            $result = loadfile($loaddir, $payload['name'] . '.json', $defFromDir);
            if ($result === false){
                dieVars(false, false, '', ['html'=>'Failed to open ' . $payload['name'] . '.json for parsing.', 'js'=>[]]);
            }
            $definition[$payload['name']] = json_decode($result, true);
            if (!array_key_exists('css', $definition[$payload['name']])){
                $definition[$payload['name']]['css'] = [];
            }
            foreach ($definition[$payload['name']]['css'] as $css){
                $cfile = '';
                if (checkAbsPath($css)){
                    $cfile = $css;
                }else{
                    $cfile = $defFromDir . $css;
                }
                array_push($usedCSS, $cfile);
            }
            if (!array_key_exists('js', $definition[$payload['name']])){
                $definition[$payload['name']]['js'] = [];
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
            if (!array_key_exists('default', $definition[$payload['name']])){
                $definition[$payload['name']]['default'] = [];
            }
            if (!array_key_exists('attr', $definition[$payload['name']]['default'])){
                $definition[$payload['name']]['default']['attr'] = [];
            }
            if (!array_key_exists('content', $definition[$payload['name']]['default'])){
                $definition[$payload['name']]['default']['content'] = [];
            }
        }
    
        switch ($definition[$payload['name']]['type']) {
            case "page":
                return parse_page($payload, $attrenv, $newvarenv);
                break;
            case "def":
                return parse_def($payload, $attrenv, $newvarenv);
                break;
            case "block":
                return parse_block($payload, $attrenv, $newvarenv);
                break;
            case "template":
                return parse_template($payload, $attrenv, $newvarenv);
                break;
        }
    }else{
        dieVars(false, false, '', ['html' => 'Malformed JSON.', 'js'=>[]]);
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
    $newvarenv = json_decode(json_encode($varenv), true);
    foreach ($defvars as $k=>$v){
        if (array_key_exists($k, $newvarenv)){
            if ($k[0] == '_'){
                $newvarenv[$k] = $v;
            }
        }
    }
    foreach ($vars as $k=>$v){
        if (array_key_exists($k, $newvarenv)){
            if ($k[0] == '_'){
                $newvarenv[$k] = $v;
            }
        }else{
            $newvarenv[$k] = $v;
        }
    }
    //$newvarenv = array_replace($vars, $newvarenv);
    foreach ($newvarenv as $vk=>$vv){
        while (($keyname = check_var($newvarenv[$vk])) !== false){
            $newvarenv[$vk] = $newvarenv[$keyname];
        }
    }
    foreach ($defvars as $dk=>$dv){
        if (!array_key_exists($dk, $newvarenv)){
            $newvarenv[$dk] = $dv;
        }
    }
    foreach ($newvarenv as $vk=>$vv){
        if (($keyname = check_var($vv)) !== false){
            $newvarenv[$vk] = $newvarenv[$keyname];
        }else{
            if (($keyname = check_var($vv)) !== false){
                if (array_key_exists($keyname, $default)){
                    $newvarenv[$vk] = $default[$keyname];
                }
            }
        }
    }

    /*
    echo("\n");
    var_dump($default);
    var_dump($defvars);
    var_dump($vars);
    var_dump($newvarenv);
    echo("----------------------------------------\n");
    */
    return $newvarenv;
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
            if (gettype($attrenv['class']) == gettype('')){
                $attrenv['class'] = [$attrenv['class']];
            }
            if (gettype($defattr['class']) == gettype('')){
                $defattr['class'] = [$defattr['class']];
            }
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
            if (($keyname = check_var($c)) !== false){
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
                if (($keyname = check_var($sv)) !== false){
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
            $varr = $v;
            if (gettype($v) == gettype('')){
                $varr = [$v];
            }
            foreach ($varr as &$ve){
                if ($ve[0] == '$' && $ve[-1] == '$'){
                    $keyname = substr($ve, 1, -1);
                    $ve = $varenv[$keyname];
                    if ($ve == null){
                        $ve = $def['default']['attr'][$keyname];
                        if ($ve[0] == '$' && $ve[-1] == '$'){
                            $ve = $varenv[$keyname];
                        }
                    }
                }
            }
            $v = implode('', $varr);
            $attrStr .= " $k=\"$v\"";
        }
    }
    $start .= "$attrStr>";
    $end = '';
    if ($def['close']){
        $end = '</' . $def['html'] . '>';
    }
    
    $return = '';
    if (!array_key_exists('content', $payload) || (gettype($payload['content']) == gettype([]) && count($payload['content']) == 0) || $payload['content'] == ''){
        //$payload['content'] = json_decode(json_encode($varenv['content']), true);
    }
    
    
    while (gettype($payload['content']) == gettype('')){
        if (($keyname = check_var($payload['content'])) !== false){
            $payload['content'] = $varenv[$keyname];
        }else{
            $return .= $payload['content'];
            break;
        }
    }
    
    if (gettype($payload['content']) == gettype([])){
        foreach ($payload['content'] as &$c){
            //echo("\n====================START===================\n");
            if (gettype($c) == gettype('')){
                $c = [$c];
            }
            $varenv['content'] = parse_all($c, $c['attr'], $varenv);
            //echo("\n=================tempresult=================\n");
            //var_dump($tempresult);
            
            foreach ($def['content'] as $kd=>$d){
                if (gettype($d) == gettype('')){
                    //echo("\n======================d======================\n");
                    //echo($d);
                    if (($dkeyname = check_var($d)) !== false){
                        $content = $varenv[$dkeyname];
                        while (($keyname = check_var($content)) !== false){
                            $content = $varenv[$keyname];
                        }
                        $content = parse_all($content, [], $varenv);
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
    if (!$def['close']){
        return $start;
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
    if (!array_key_exists('content', $def['default'])){
        $def['default']['content'] = [];
    }
    if (!array_key_exists('vars', $def['default'])){
        $def['default']['vars'] = [];
    }
    if (!array_key_exists('vars', $def)){
        $def['vars'] = [];
    }
    if (!array_key_exists('content', $def)){
        $def['content'] = [];
    }
    if (!array_key_exists('content', $payload)){
        $payload['content'] = [];
    }
    if (!array_key_exists('vars', $payload)){
        $payload['vars'] = [];
    }
}

function mergeattrsanicheck(&$def, &$attrenv, &$payload){
    
    if (!array_key_exists('attr', $def)){
        $def['attr'] = [];
    }
    if (!array_key_exists('attr', $def['default'])){
        $def['default']['attr'] = [];
    }
    if (!array_key_exists('style', $def['default']['attr'])){
        $def['default']['attr']['style'] = [];
    }
    if (!array_key_exists('class', $def['default']['attr'])){
        $def['default']['attr']['class'] = [];
    }
    
    if ($attrenv == null){
        $attrenv = [];
    }
    if (!array_key_exists('style', $attrenv)){
        $attrenv['style'] = [];
    }
    if (!array_key_exists('class', $attrenv)){
        $attrenv['class'] = [];
    }

    if (!array_key_exists('attr', $payload)){
        $payload['attr'] = [];
    }
    if (!array_key_exists('style', $payload['attr'])){
        $payload['attr']['style'] = [];
    }
    if (!array_key_exists('class', $payload['attr'])){
        $payload['attr']['class'] = [];
    }
}

?>