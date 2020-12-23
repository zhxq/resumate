<?php
function makeMenu(){
    $i = 0;
    $menuSettings = getSetting('menu');
    $left = '<ul id="leftnav" class="nav navbar-nav">';
    $right = '<ul id="rightnav" class="nav navbar-nav navbar-right" style="margin-right:1%;">';
    foreach ($menuSettings as $k=>$v){
        $opt = '<li data-name="' . $k . '" data-type="topmenubutton" id="navid' . $i . '"';
        $dropdown = '';
        if (array_key_exists('dropdown', $v)){
            $opt .= ' class="dropdown">';
            $dropdown = '<ul class="dropdown-menu">';
            foreach ($v['dropdown'] as $dk=>$dv){
                $dropdown .= '<li data-name="' . $k . '"><a href="javascript:void(0);" onclick="setHash(\'' . $dk . '\');clickCollapseButton();">' . getLocalizedString($dv['readable']) . '</a></li>';
            }
            $dropdown .= '</ul>';
            $opt .= '<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="true">' . getLocalizedString($v['readable']) . '<span class="caret"></span></a>' . $dropdown;
        }else{
            $opt .= ' onmousedown="changeActiveStatus(\'' . $i . '\');">';
            $opt .= '<a href="javascript:void(0);" onclick="setHash(\'' . $k . '\');clickCollapseButton();">' . getLocalizedString($v['readable']) . '</a>';
        }
        $opt .= "</li>";
        $i++;
        if ($v['right']){
            $right .= $opt;
        }else{
            $left .= $opt;
        }
    }

    $langMenu = '<li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Language<span class="caret"></span></a>
        <ul class="dropdown-menu">';
    $langSettings = getSetting('lang');
    foreach(getLangList() as $lang){
        $langMenu .= '<li><a href="javascript:void(0);" onclick="setLang(\'' . $lang . '\');">' . $langSettings[$lang]['name'] . '</a></li>';
    }
    $langMenu .= '    </ul>
    </li>';

    $left .= '</ul>';
    $right .= $langMenu . '</ul>';
    return $left . $right;
}
?>