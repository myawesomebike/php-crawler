<html>
<head>
<title>Website Crawler</title>
<link type="text/css" rel="stylesheet" href="/crawler/crawlerstyles.css" />
<link type="text/css" rel="stylesheet" href="/global/global.css" />
<script>
var t;
function ajax(getURL,parameters,callback) {
	if (window.XMLHttpRequest) { var asyncXmlhttp=new XMLHttpRequest(); }
	else { var asyncXmlhttp=new ActiveXObject("Microsoft.XMLHTTP"); }
	asyncXmlhttp.onreadystatechange=function() {
		if (asyncXmlhttp.readyState==4 && asyncXmlhttp.status==200) {
			callback(asyncXmlhttp.responseText);
		}
	}
	if (parameters!="") {
		asyncXmlhttp.open("POST",getURL,true);
		asyncXmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		asyncXmlhttp.send(parameters);
	}
	else {
		asyncXmlhttp.open("GET",getURL,true);
		asyncXmlhttp.send();
	}
}
function ge(obj) {
	return document.getElementById(obj);
}
function refresh(rFunction,rDest,rData) {
	stopRefresh();
	backend(rFunction,rDest,rData);
	refreshFunction = "refresh('"+rFunction+"','"+rDest+"','"+rData+"')";
	t = window.setInterval(refreshFunction, 1000);
}
function refreshModal(rFunction,rData) {
	if(arguments.length > 2) {
		getURL = "be.php?rf=" + rFunction;
		for(i = 1; i<arguments.length; i++) {
			getURL = getURL + "&rd[]="+arguments[i];
		}
	}
	else {
		getURL = "be.php?rf=" + rFunction + "&rd[]=" + rData;
	}
	stopRefresh();
	createModal(getURL);
	refreshFunction = "createModal('"+getURL+"');";
	t = window.setInterval(refreshFunction, 1000);
}
function stopRefresh() {
	t = window.clearInterval(t);
}
function backend(rFunction,rDest,rData) {
	if(arguments.length > 3) {
		getURL = "be.php?rf=" + rFunction;
		for(i = 2; i<arguments.length; i++) {
			getURL = getURL + "&rd[]="+arguments[i];
		}
	}
	else {
		getURL = "be.php?rf=" + rFunction + "&rd[]=" + rData;
	}
	callback = function(response) {
		if(rDest != '') {
			ge(rDest).innerHTML = response;
		}
	}
	ajax(getURL,"",callback);
}
function toggle(rFunction,rDest,rData) {
	if(ge(rDest).innerHTML == '') {
		backend(rFunction,rDest,rData);
	}
	else {
		ge(rDest).innerHTML = '';
	}
}
function createModal(fromURL) {
	ge("ajaxLoader").innerHTML = '<img src="images/close.png" style="position:absolute; top:-8px; left:-8px; cursor:pointer;" onclick="closeModal();"><img src="/images/287.gif" style="height:64px; width:64px;">';
	callback = function(response) {
		ge("ajaxLoader").innerHTML = '<img src="images/close.png" style="position:absolute; top:-8px; left:-8px; cursor:pointer; z-index:90000;" onclick="closeModal();">'+response;
	}
	ajax(fromURL,"",callback);

	ge("ajaxModal").style.display = "block";
}
function closeModal() {
	stopRefresh();
	ge("ajaxLoader").innerHTML = "";
	ge("ajaxModal").style.display = "none";
}
</script>
</head>
<body onload="backend('buildDomains','domainTarget','');">
<div id="ajaxModal"><div id="ajaxLoader"></div></div>
<?
include "../global/header.php";
?>
<div id="domainList">
<div class="scrollHeading bezel">Domains
<input id="domainSearch" type="text" onkeyup="backend('buildDomains','domainTarget',this.value);" style="width:250px;"></div>
<div class="scrollBox" id="domainTarget">

</div>
</div>

<div id="dateList">
<div class="scrollHeading bezel">Domain Data</div>
<div class="scrollBox" id="tabBar">

</div>
</div>

<div id="workarea">

<div id="scrollyarea">

<div style="position:relative; text-align:center; top:40%; color:#666666;">^ &nbsp; Please start a crawl or select an existing one to start &nbsp; ^</div>
</div>
</div>
</body>
</html>