<?php

require_once('interface.php');
require_once('manager.php');
require_once('crawler.php');
require_once('functions.php');

date_default_timezone_set('America/New_York');

$requestedData = array();
$requestedFunction = @$_GET['rf'];
$requestedData = @$_GET['rd'];

$allowedFunctions = array(
	"createNewCrawl",
	"createReport",
	"startCrawl",
	"buildCompare",
	"buildTabs",
	"buildDomains",
	"buildCrawl",
	"buildNewCrawl",
	"verifyURL",
	"createNewDomain",
	"stopCrawl",
	"buildURLDetail",
	"buildURLCompare",
	"buildRankings",
	"buildRankingsExcel",
	"buildKeywordDensity",
	"buildKeywordDensityProgress",
	"analyzeCrawl",
	"buildKWDURL",
	"buildKeywordSearch",
	"buildKeywordSearchResults",
	"buildKeywordNetwork"
	);
if(in_array($requestedFunction,$allowedFunctions)) {
	call_user_func_array($requestedFunction,$requestedData);
}


?>