function checkType(type){
	if (type != "edit" && type != "delete"){
		alert('类型非法！');
		return false;
	}
	return true;
}

function setHash(theHash){
	//$('#lastHash').val(window.location.hash.replace('#', ''));
	
	theHash = theHash.replace('#', '');
	
	var sharpIndex = window.location.href.indexOf("#");
	if (sharpIndex === -1) {
	  window.location.href = window.location.href + "#" + theHash;
	} else {
	  window.location.href = window.location.href.substr(0, sharpIndex) + "#" + theHash;
	}
	if ($("#mainArea").html().trim() == ""){
		getWebpage(settings['index']);
	}
}



function clickCollapseButton(){
	if (document.getElementById('collapsebutton').clientHeight != 0){
		document.getElementById('collapsebutton').click();
	}
}

function getBackHash(){
	var lastHash = '#' + document.getElementById('lastHash').value;
	if (lastHash == "#" || lastHash == "##"){
		setHash('index');
		return;
	}
	if (lastHash == window.location.hash){
		setHash('index');
	}else{
		history.back(-1);
	}
}

function changeHash(theHash){
	window.location.hash = theHash;
}

function getLastHash(){
	return "#" + $('#lastHash').val().replace('#', '');
}

function getHashInfoList(){
	var hashVars = decodeURI(window.location.hash);
	return hashVars.split('?');
}

function getIfFloatIsOnStage(){
	return ($("#modals").html().trim().length == 0) ? false : true;
}

function getIfLastNonFloatHashExists(){
	return ($('#lastNonFloatHash').val() == "" || $('#lastNonFloatHash').val() == "#") ? false : true;
}

function getLastNonFloatHash(){
	return getIfLastNonFloatHashExists() == false ? "#index" : $('#lastNonFloatHash').val();
}

function getToLastNonFloatPage(){
	setHash(getLastNonFloatHash());
}

function changeActiveStatus(listid) {
	var selected = $('li[data-type="topmenubutton"].active');
	var lastID = selected.length ? selected[0].id : '';
	$('#lastNaviID').val(lastID.replace('navid', ''));
	$('li[data-type="topmenubutton"]').removeClass('active');
	$('#navid' + listid).addClass("active");
}

function changeActiveStatusByName(theName) {
	var selected = $('li[data-type="topmenubutton"].active');
	var lastID = selected.length ? selected[0].id : '';
	$('#lastNaviID').val(lastID.replace('navid', ''));
	$('li[data-type="topmenubutton"]').removeClass('active');
	$('li[data-type="topmenubutton"][data-name="' + theName + '"]').addClass('active');
}

jQuery.cachedScript = function( url, options ) {
	// Allow user to set any option except for dataType, cache, and url
	options = $.extend( options || {}, {
		dataType: "script",
		cache: true,
		async: false,
		url: url
	});

	// Use $.ajax() since it is more flexible than $.getScript
	// Return the jqXHR object so we can chain callbacks
	return jQuery.ajax( options );
};


// https://stackoverflow.com/questions/14783046/using-getscript-synchronously
function loadScript(script_url){
    // Unrelated stuff here!!!

    return $.cachedScript(script_url).then(function(){
        //  Unrelated stuff here
        // do something with $element after the script loaded.
    });
}

function getWebpage(str) {
	$('#lastIncludedPage').val(str);
	var getWebpageAsync = true;
	if (arguments[1] == false){
		getWebpageAsync = false;
	}
	$.ajax({
		type: "POST",
		dataType: "json",
		async: getWebpageAsync,
		url: 'render.php',
		data: {
			"filename": str + '.json'
		},
		success: function(data){
			var deflist = [];
			if (data['data']['js'].length > 0){
				
				// https://stackoverflow.com/questions/14783046/using-getscript-synchronously
				/*
				var deferred = new $.Deferred();
				var promise = deferred.promise();
				data['data']['js'].forEach(element => {
					console.log(element);
					promise = promise.then(function() {
						return loadScript(element);
					});
				});
				promise.done(function() {
					// optional: Do something after all scripts have been loaded
					$('#mainArea').html(data['data']['html']);
				});
				
				// Resolve the deferred object and trigger the callbacks
				deferred.resolve();*/

				// https://stackoverflow.com/questions/5627284/pass-in-an-array-of-deferreds-to-when
				data['data']['js'].forEach(element => {
					deflist.push(loadScript(element));
				});
				$.when.apply($, deflist).done(
					function(){
						$('#mainArea').html(data['data']['html']);
					}
				);
			}else{
				$('#mainArea').html(data['data']['html']);
			}
			
			
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) { 
			alert(errorThrown); 
		}
	});
}

function appendZero(s){
	return ("00" + s).substr((s + "").length);
}

function getModal(filename, title, button, onclick, onclose, paraArray){
	$('.modal').modal('hide');
	//获取最后一次没有浮层的页面
	if (!getIfFloatIsOnStage()){
		$('#lastNonFloatHash').val(getLastHash());
	}
	paraArray = paraArray || {};
	
	var paraArrayString = JSON.stringify(paraArray);
	$.ajax({
		type: "POST",
		dataType: "html",
		async: false,
		url: "makemodal.php",
		data: {
			file: filename,
			title: title,
			button: button,
			onclick: onclick,
			close: onclose,
			paraArrayString: paraArrayString
		},
		success: function(data){
			$('#modals').append(data);
			$('#' + title + 'Modal').modal({
				backdrop: 'static',
				keyboard: false
			});
			$('#' + title + 'Modal').on('shown.bs.modal', function (e) {
			    $("#mainBody").removeClass('modal-open');
			})
			$('#' + title + 'Modal').on('hidden.bs.modal', function(e) {
				$(this).remove();
			});
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) { 
			alert('error');
			alert(textStatus + errorThrown); 
		} 
	});
}

function showModal(filename, title, button, onclick, onclose, paraArray){
	getModal(filename,title,button,onclick,onclose, paraArray);
	$("#" + title + "Modal").modal('show');
}

function setLang(lang){
	$.ajax({
		type: "POST",
		dataType: "json",
		async: true,
		url: "action.php",
		data: {
			lang: lang,
			action: "setLang"
		},
		success: function(data){
			if (data['confirmed'] && data['success']){
				location.reload();
			}else{
				alert(data['confirmString']);
			}
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) { 
			alert('error');
			alert(textStatus + errorThrown); 
		} 
	});
}