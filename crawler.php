<?php

/*  =====================  CRAWLING FUNCTIONS  =====================  */

$curlLength = 0;
$curlStream = '';

$urlArray = array();
function startCrawl($crawlID) {
	global $urlArray;
	$con = connectToDB();
	$rightNow = time();
	$query = "UPDATE `crawl_index` SET `status` = '1' WHERE `id` = '$crawlID'";
	mysql_query($query);
	
	$query = "SELECT `crawl_url` FROM `urls` WHERE `crawl_id` = '$crawlID'";
	$result = mysql_query($query);
	while($row = mysql_fetch_array($result)) {
		$urlArray[] = $row['crawl_url'];
	}
	getNextURL($crawlID);
}
function dataThrottle($cHandle,$data) {
	global $curlStream;
	global $curlLength;
	$curlStream .= $data;
	$curlLength += strlen($data);
	if($curlLength > 2000000) {
		return 0;
	}
	else {
		return strlen($data);
	}
}
function findURLsAtID($crawlID,$urlID,$domainID) {
	global $urlArray;
	set_time_limit(30);
	//unset($GLOBALS['curlStream']);
	global $curlLength;
	global $curlStream;
	$curlStream = null;
	$curlLength = 0;
	$rightNow = time();
	$responseStart = microtime(true);
	$con = connectToDB();
	$query = mysql_query("SELECT * FROM `domains` WHERE `domain_id` = '".$domainID."'");
	$domainData = mysql_fetch_array($query);
	$rootDomain = $domainData['domain_name'];
	$query = mysql_query("SELECT `crawl_url`,`redirect_depth` FROM `urls` WHERE `url_id` = '$urlID' LIMIT 1");
	$urlData = mysql_fetch_array($query);
	$redirectLoc = '';
	
	$ch = curl_init();
	$requestHeader = array('User-agent:Mozilla/5.0 (compatible; SEO Crawler)','Accept:*/*','connection:keep-alive','Pragma:no-cache','cache-control:no-cache');
	curl_setopt($ch,CURLOPT_URL,$urlData['crawl_url']);
	curl_setopt($ch,CURLOPT_HEADER,true);
	curl_setopt($ch,CURLOPT_FOLLOWLOCATION,false);
	curl_setopt($ch,CURLOPT_WRITEFUNCTION,'dataThrottle');
	curl_setopt($ch,CURLOPT_HTTPHEADER,$requestHeader);
	curl_setopt($ch,CURLOPT_TIMEOUT,10);

	curl_exec($ch);
	$httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
	$info = curl_getinfo($ch);
	curl_close($ch);
	$header = substr($curlStream,0,$info['header_size']);
	$body = substr($curlStream,$info['header_size']);
	unset($ch);
	if($httpCode == 200) {
		$doc = new DOMDocument();
		@$doc->loadHTML($body);
		$elements = $doc->getElementsByTagName('a');
		$discoveredURLs = array();
		foreach($elements as $element) {
			$thisURL = $element->getAttribute('href');
			$thisURL = explode('#',$thisURL);
			if($thisURL != '') {
				$thisURL = cleanURL($thisURL[0],$urlData['crawl_url']);
				if(!in_array($thisURL,$urlArray)) {
					$discoveredURLs[] = $thisURL;
					$urlArray[] = $thisURL;
				}
			}
		}
		foreach($discoveredURLs as $thisURL) {
			if(cleanURL($thisURL,$rootDomain) !== false) { //incorrectly resolved relative URLs may get excluded at this point
				$query = "INSERT INTO `urls` VALUES ('','$crawlID','$thisURL','','','0','0','','','','','','','')";
				mysql_query($query);
			}
		}
	}
	else {
		if($httpCode == 301 || $httpCode == 302) {
			preg_match('/^Location:(.*)$/mi',$header,$redirectLoc);
			$redirectLoc = cleanURL($redirectLoc[1],$rootDomain);
			if(cleanURL($redirectLoc,$rootDomain) !== false) {
				$depth = $urlData['redirect_depth']++;
				$query = "INSERT INTO `urls` VALUES ('','$crawlID','$redirectLoc','','','0','0','$depth','$urlID','','','','','')";
				mysql_query($query);
			}
		}
	}
	$docHash = md5($body);
	$cacheHTML = addslashes($body);
	$responseTime = microtime(true) - $responseStart;
	$query = "UPDATE `urls` SET `crawl_timestamp` = '$rightNow',`response_time` = '$responseTime',`crawl_status` = 1,`http_status` = '$httpCode',`http_full_header` = '$header',`hash` = '$docHash' WHERE `url_id` = '$urlID'";
	mysql_query($query);
	$query = "INSERT INTO `raw_html` VALUES ('$urlID','$cacheHTML')";
	mysql_query($query);
	usleep(100 + ($responseTime * 1000)); //set up throttling at this point
	getNextURL($crawlID);
}
function getNextURL($crawlID) {
	logError($crawlID,'','Finding next URL');
	
	$con = connectToDB();
	$rightNow = time();
	$query = mysql_query("SELECT * FROM `crawl_index` WHERE `id` = '$crawlID' LIMIT 1");
	$crawlData = mysql_fetch_array($query);
	logError($crawlID,'','fetching new URL for '.$crawlData['entry_url']);
	if($crawlData['status'] == 1) {
		$query = mysql_query("SELECT `url_id` FROM `urls` WHERE `crawl_id` = '$crawlID' AND `crawl_status` = 0 LIMIT 1");
		$urlData = mysql_fetch_array($query);
		if(mysql_num_rows($query) < 1) {
			closeCrawl($crawlID);
		}
		else {
			findURLsAtID($crawlID,$urlData['url_id'],$crawlData['domain_id']);
		}
	}
	else {
		if($crawlData['status'] == 3) {
			logError($crawlID,'','Crawl Was Stopped by user');
		}
		else {
			logError($crawlID,'','Crawl was not started or timed out after 30 minutes');
		}
	}
}
function closeCrawl($crawlID) {
	$con = connectToDB();
	$rightNow = time();
	$query = "UPDATE `crawl_index` SET `end` = '$rightNow', `status` = '2' WHERE `id` = '$crawlID'";
	mysql_query($query);
}
function stopCrawl($crawlID) {
	$con = connectToDB();
	$rightNow = time();
	$query = "UPDATE `crawl_index` SET `end` = '$rightNow', `status` = '3' WHERE `id` = '$crawlID'";
	mysql_query($query);
	logError($crawlID,'','Stopped crawl');
}
?>