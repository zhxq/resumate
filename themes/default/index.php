<?php
require_once('func.php');
?>
<!DOCTYPE html>
<html>
<head>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/js/bootstrap.min.js"></script>
<script type="text/javascript" src="/themes/<?=getSetting('theme')?>/js/defaulttheme.js"></script>
<script type="text/javascript" src="/themes/<?=getSetting('theme')?>/js/onload.js"></script>
<title><?=getLocalizedSetting('title')?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="HandheldFriendly" content="True">
<meta content="telephone=no" name="format-detection" /> 
<meta name="Title" Content="<?=getLocalizedSetting('title')?>">
<meta name="Keywords" content="<?=getLocalizedSetting('keywords')?>">
<meta name="Description" content="<?=getLocalizedSetting('description')?>">
<meta name="Content" Content="<?=getLocalizedSetting('content')?>">
<link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/css/bootstrap.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker3.min.css" rel="stylesheet">
<link href="/themes/<?=getLocalizedSetting('theme')?>/css/styles.css" rel="stylesheet">
<link id="favicon" href="favicon.ico" rel="icon" type="image/x-icon">
<link rel="shortcut icon" href="favicon.ico" >
<style>
.navbar-fixed-top, .navbar-fixed-bottom {
    position: fixed;
}
.navbar-brand {
    padding-left: 20px;
    -webkit-padding-left: 20px;
}
.navbar-toggle {
    margin-right: 20px;
    -webkit-margin-right: 20px;
}
body { -webkit-text-size-adjust: 100%; }
</style>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>

<body id="mainBody">
<input type="hidden" id="currentHash" value="" />
<input type="hidden" id="lastHash" value="" />
<input type="hidden" id="lastIncludedPage" value="" />
    
<nav id="navigationBar" class="navbar navbar-inverse navbar-fixed-top" role="navigation">
    <div class="container-fluid">
        <div class="navbar-header">
            <button id="collapsebutton" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" type="button">
                <span class="icon-bar">
                </span>
                <span class="icon-bar">
                </span>
                <span class="icon-bar">
                </span>
            </button>
            <a style="margin-left:1%; white-space:nowrap;" class="navbar-brand">
            <?=getLocalizedSetting('menutitle')?>
            </a>
        </div>
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <?=makeMenu()?>
        </div>
    </div>
</nav>
<span id="standardSpan"></span>
<div id="modals"></div>
<input type="hidden" id="lastNonFloatHash" value="">
<div style="text-align:center;">
<input type="hidden" id="naviNum" value="11">
<input type="hidden" id="lastNaviID" value="navid0">
<div id="pageTop" style="text-align:left; float:left;"></div>


<span id="tempArea"></span>
<span id="uploadArea"></span>
<span id="logoutArea"></span>

<div style="display: block;"><br /></div>
<div id="mainArea" style="text-align:center;" class="normal-form">
</div>

</div>


</body>
</html>
