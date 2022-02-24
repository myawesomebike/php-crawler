<?php

/*  =====================  CRAWLING MANAGING FUNCTIONS  =====================  */

function createNewCrawl($domainID,$entryURL) {
	$parsedURL = parse_url($entryURL);
	if(array_key_exists('scheme',$parsedURL) && array_key_exists('host',$parsedURL)) {
		$con = connectToDB();
		//probably should make sure we are in the same timezone or something
		$rightNow = time();

		$query = "INSERT INTO `crawl_index` VALUES ('','$domainID','$entryURL','$rightNow','','')";
		mysql_query($query);
		$crawlID = mysql_insert_id();
		$query = "INSERT INTO `urls` VALUES ('','$crawlID','$entryURL','$rightNow','',0,'0','','','','','','','')";
		mysql_query($query);
		$urlID = mysql_insert_id();
		echo '<img src="/images/blank.gif" onload="backend(\'startCrawl\',\'\',\''.$crawlID.'\'); backend(\'buildTabs\',\'tabBar\',\''.$domainID.'\',\''.$crawlID.'\'); backend(\'buildCrawl\',\'scrollyarea\',\''.$crawlID.'\'); refresh(\'buildCrawl\',\'scrollyarea\',\''.$crawlID.'\');">';
	}
	else {
		echo 'not valid URL';
	}
}
function createNewDomain($domainURL) {
	$rightNow = time();
	$con = connectToDB();
	$query = "INSERT INTO `domains` VALUES ('','$domainURL','$rightNow',1,'','','')";
	mysql_query($query);
	$domainID = mysql_insert_id();
	echo '<img src="/images/blank.gif" onload="backend(\'buildTabs\',\'tabBar\',\''.$domainID.'\',\'new\'); backend(\'buildNewCrawl\',\'scrollyarea\',\''.$domainID.'\');">';
}
function verifyURL($testURL) { //add real-time URL checking in the future, also check domain against established domain record
	$parsedURL = parse_url($testURL);
	if($parsedURL != '') {
		if(!array_key_exists('scheme',$parsedURL)) {
			echo 'Please include scheme (http or https) in URL<br>';
		}
		if(!array_key_exists('host',$parsedURL)) {
			echo 'Please enter valid domain name<br>';
		}
	}
}

function addDomain($url) {
	$parsedURL = parse_url($url);
	if(array_key_exists('scheme',$parsedURL) && array_key_exists('host',$parsedURL)) {
		$con = connectToDB();
		$rightNow = time();
		$query = "INSERT INTO `domains` VALUES ('','".$parsedURL['scheme']."://".$parsedURL['host']."','$rightNow')";
		mysql_query($query);
		$domainID = mysql_insert_id();
		return $domainID;
	}
	else {
		return -1;
	}
}
?>