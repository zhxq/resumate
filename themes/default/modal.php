<?php
ini_set('display_errors', 'Off');
require_once('func.php');
$includeFile = $file . '.php';
$parameter = '';
$otherKeys = array_keys($paraArray);
for ($i = 0; $i < count($otherKeys); $i++){
    $_GET[$otherKeys[$i]] = $paraArray[$otherKeys[$i]];
    $_POST[$otherKeys[$i]] = $paraArray[$otherKeys[$i]];
    $_REQUEST[$otherKeys[$i]] = $paraArray[$otherKeys[$i]];
}

$modalSize = '';
if ($_REQUEST['__modalsize__'] == 'large'){
	$modalSize = ' modal-lg';
}elseif ($_REQUEST['__modalsize__'] == 'small'){
	$modalSize = ' modal-sm';
}

$keyDisplay = '';
if ($button == '' && $onclick == ''){
    $keyDisplay = "display:none;";
}

if ($close == ''){
    $close = 'getBackHash();';
}else{
    
}

?>
<div class="modal fade" id="<?=$title?>Modal" tabindex="-1" role="dialog" aria-labelledby="<?=$title?>ModalLabel" aria-hidden="true">
    <div class="modal-dialog<?=$modalSize?>">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-belonging="<?=$title?>CloseButton" data-dismiss="modal" aria-label="Close" onclick="<?=$close?>"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="<?=$title?>ModalLabel"><?=__($title);?></h4>
            </div>
        <div class="modal-body" id="<?=$title?>ModalBody">
            <?php require_once($includeFile);?>
        </div>
        <div class="modal-footer">
            <button type="button" id="<?=$title?>CloseButton" data-belonging="<?=$title?>CloseButton" class="btn btn-default" data-dismiss="modal" onclick="<?=$close?>"><?=__('close');?></button>
            <button type="button" data-buttontype="savedata" id="<?=$title?>SubmitButton" class="btn btn-primary" onclick="<?=$onclick?>" style="<?=$keyDisplay?>"><?=__($button)?></button>
            </div>
        </div>
    </div>
</div>