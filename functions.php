<?php

$stopWords = array("a","about","above","across","after","again","against","all","almost","alone","along","already","also","although","always","among","an","and","another","any","anybody","anyone","anything","anywhere","are","area","areas","around","as","ask","asked","asking","asks","at","away","b","back","backed","backing","backs","be","became","because","become","becomes","been","before","began","behind","being","beings","best","better","between","big","both","but","by","c","came","can","cannot","case","cases","certain","certainly","clear","clearly","come","could","d","did","differ","different","differently","do","does","done","down","down","downed","downing","downs","during","e","each","early","either","end","ended","ending","ends","enough","even","evenly","ever","every","everybody","everyone","everything","everywhere","f","face","faces","fact","facts","far","felt","few","find","finds","first","for","four","from","full","fully","further","furthered","furthering","furthers","g","gave","general","generally","get","gets","give","given","gives","go","going","good","goods","got","great","greater","greatest","group","grouped","grouping","groups","h","had","has","have","having","he","her","here","herself","high","high","high","higher","highest","him","himself","his","how","however","i","if","important","in","interest","interested","interesting","interests","into","is","it","its","itself","j","just","k","keep","keeps","kind","knew","know","known","knows","l","large","largely","last","later","latest","least","less","let","lets","like","likely","long","longer","longest","m","made","make","making","man","many","may","me","member","members","men","might","more","most","mostly","mr","mrs","much","must","my","myself","n","necessary","need","needed","needing","needs","never","new","new","newer","newest","next","no","nobody","non","noone","not","nothing","now","nowhere","number","numbers","o","of","off","often","old","older","oldest","on","once","one","only","open","opened","opening","opens","or","order","ordered","ordering","orders","other","others","our","out","over","p","part","parted","parting","parts","per","perhaps","place","places","point","pointed","pointing","points","possible","present","presented","presenting","presents","problem","problems","put","puts","q","quite","r","rather","really","right","right","room","rooms","s","said","same","saw","say","says","second","seconds","see","seem","seemed","seeming","seems","sees","several","shall","she","should","show","showed","showing","shows","side","sides","since","small","smaller","smallest","so","some","somebody","someone","something","somewhere","state","states","still","still","such","sure","t","take","taken","than","that","the","their","them","then","there","therefore","these","they","thing","things","think","thinks","this","those","though","thought","thoughts","three","through","thus","to","today","together","too","took","toward","turn","turned","turning","turns","two","u","under","until","up","upon","us","use","used","uses","v","very","w","want","wanted","wanting","wants","was","way","ways","we","well","wells","went","were","what","when","where","whether","which","while","who","whole","whose","why","will","with","within","without","work","worked","working","works","would","x","y","year","years","yet","you","young","younger","youngest","your","yours","z");

function analyzeCrawl($crawlID) {
	$con = connectToDB();
	$sql = "SELECT `url_id` FROM `urls` WHERE `crawl_id` = '$crawlID'";
	$result = mysql_query($sql);
	ob_start();
	while($row = mysql_fetch_array($result)) {
		set_time_limit(45);
		analyzeHTML($row['url_id'],$crawlID);
	}
}
function analyzeHTML($urlID,$crawlID) {
	
	global $stopWords;
	$con = connectToDB();
	$sql = "SELECT `raw_html` FROM `raw_html` WHERE `url_id` = '$urlID' LIMIT 1";

	$result = mysql_query($sql);
	$data = mysql_fetch_array($result);
	$rawHTML = strtolower($data['raw_html']);
	$rawHTML = preg_replace('/((<[\s\/]*script\b[^>]*>)([^>]*)(<\/script>))/','',$rawHTML);
	$rawHTML = preg_replace('/((<[\s\/]*style\b[^>]*>)([^>]*)(<\/style>))/','',$rawHTML);
	$rawHTML = html_entity_decode(strip_tags($rawHTML));
	$rawHTML = preg_replace('/\s+/',' ',$rawHTML);
	$rawSentances = explode(".",strip_tags($rawHTML));
	$rawWords = preg_replace('/([^A-Za-z ]+)/',' ',$rawHTML);
	$words = explode(" ",strip_tags($rawWords));
	$phrases = array();
	foreach($rawSentances as $thisSentance) {
		$rawWords = explode(",",$thisSentance);
		$rawWords = preg_replace('/([^A-Za-z ]+)/',' ',$rawWords);
		foreach($rawWords as $fragment) {
			$rawWords = explode(" ",$fragment);
			$lastStopWord = 0;
			$wordCounter = 0;
			foreach($rawWords as $thisWord) {
				$wordCounter++;
				if(in_array($thisWord,$stopWords)) {
					$tempArray = array_slice($rawWords,$lastStopWord,($wordCounter - $lastStopWord - 1));
					$tempPhrase = trim(implode(" ",$tempArray));
					if($tempPhrase != '' && (($wordCounter - $lastStopWord) > 2)) {
						$phrases[] = $tempPhrase;
					}			
					$lastStopWord = $wordCounter;
				}
			}
		}
	}
	$phrases = array_merge($phrases,$words);
	$phraseDensity = array();
	foreach($phrases as $thisPhrase) {
		if(!in_array($thisPhrase,$stopWords) && $thisPhrase != '') {
			if(array_key_exists($thisPhrase,$phraseDensity)) {
				$density = $phraseDensity[$thisPhrase];
				$density++;
				$phraseDensity[$thisPhrase] = $density;
			}
			else {
				$phraseDensity[$thisPhrase] = 1;
			}
		}
	}
	foreach($phraseDensity as $phrase=>$density) {
		$sql = "SELECT * FROM `keyword_density` WHERE `keyword` = '$phrase' AND `crawl_id` = '$crawlID' LIMIT 1";
		$result = mysql_query($sql);
		if(mysql_num_rows($result) == 1) {
			$data = mysql_fetch_array($result);
			$phraseCount = $data['count'];
			$kwID = $data['id'];
			$conPages = json_decode($data['connected_pages'],true);
			$conPages[] = array($urlID,$density);
			$conPages = json_encode($conPages);
			$urlAnalysis = false;
			foreach($conPages as $thisKeyword) {
				if($urlID == $thisKeyword[0]) {
					$urlAnalysis = true;
				}
			}
			if(!$urlAnalysis) { //refine function to check in array first
				$phraseCount += $density;
				$query = "UPDATE `keyword_density` SET `count` = '$phraseCount',`connected_pages` = '$conPages' WHERE `id` = '$kwID'";
				mysql_query($query);
			}
		}
		else {
			$conPages = array();
			$conPages[] = array($urlID,$density);
			$conPages = json_encode($conPages);
			$query = "INSERT INTO `keyword_density` VALUES ('','$phrase','$density','$crawlID','$conPages')";
			mysql_query($query);
		}	
	}
	$query = "UPDATE `urls` SET `keyword_status` = '1' WHERE `url_id` = '$urlID'";
	mysql_query($query);
}
function getKeywordDataByDomainDate($domainID,$date) {
	$con = connectToDB();
	$sql = "SELECT * FROM `keyword_reports` WHERE `domain_id` = '$domainID' AND `conductor_date` = '$date'";
	$result = mysql_query($sql);
	if(mysql_num_rows($result) > 0) {
		$row = mysql_fetch_array($result);
		if($row['status'] == 1) {
			return getKeywordData($row['id']);
		}
	}
	else {
		return null;
	}
}
function getKeywordData($keywordReportID) {
	$con = connectToDB();
	$sql = "SELECT `url`,`keyword`,`rank`,`volume` FROM `keywords` WHERE `keyword_report_id` = '$keywordReportID'";
	$result = mysql_query($sql);
	if(mysql_num_rows($result) > 0) {
		while($row = mysql_fetch_array($result)) {
			$tempData['Keyword'] = $row['keyword'];
			$tempData['Google'] = $row['rank'];
			$tempData['Volume'] = $row['volume'];
			$urlData[$row['url']][] = $tempData;
		}
		return $urlData;
	}
	else {
		return null;
	}
}
function cleanURL($url,$reference) {
	if(strpos($url,"javascript:") === false && strpos($url,"mailto:") === false && strpos($reference,"javascript:") === false && strpos($reference,"mailto:") === false && $url != '' & $reference != '') {
		$parsedURL = parse_url(trim($url));
		$parsedReference = parse_url(trim($reference));
		if(array_key_exists('scheme',$parsedReference) || array_key_exists('host',$parsedReference)) {
			if(!array_key_exists('scheme',$parsedURL) || !array_key_exists('host',$parsedURL)) {
				return $parsedReference['scheme'].'://'.$parsedReference['host'].trim($url);
			}
			elseif($parsedURL != false && $parsedReference != false) {
				$tldRegex = '/^(?:(?>[a-z0-9-]*\.)+?|)([a-z0-9-]+\.(?>[a-z]*(?>\.[a-z]{2})?))$/';
				if(preg_match($tldRegex,$parsedURL['host'],$urlTLD) &&	preg_match($tldRegex,$parsedReference['host'],$domainTLD)) {
					if($urlTLD[1] == $domainTLD[1]) {
						return rtrim(trim($url),"/");
					}
					else {
						return false;
					}
				}
				else {
					return false;
				}
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}
	else {
		return false;
	}
}
function getDomainID($url) {
	$domain = verifyReturnDomain($url);
	if($domain != -1) {
		$con = connectToDB();
		$sql = "SELECT * FROM `domains` WHERE `domain_name` = '$domain'";
		$result = mysql_query($sql);
		$data = mysql_fetch_array($result);
		if($data == '') {
			return -1;
		}
		else {
			return $data['domain_id'];
		}
	}
	else {
		return -1;
	}
}
function getDomainIDfromURLID($urlID) { //build in error checking to make sure any of this actually exists
	$con = connectToDB();
	$query = mysql_query("SELECT * FROM `urls` WHERE `url_id` = '$urlID' LIMIT 1");
	$urlData = mysql_fetch_array($query);
	$crawlID = $urlData['crawl_id'];
	
	$query = mysql_query("SELECT `domain_id` FROM `crawl_index` WHERE `id` = '$crawlID'");
	$crawlData = mysql_fetch_array($query);
	return $crawlData['domain_id'];
}
function verifyReturnDomain($url) {
	$parsedURL = parse_url($url);
	if(array_key_exists('scheme',$parsedURL) && array_key_exists('host',$parsedURL)) {
		return $parsedURL['scheme']."://".$parsedURL['host'];
	}
	else {
		return -1;
	}
}
function logError($crawlID,$urlID,$error) {
	$rightNow = time();
	$con = connectToDB();
	$query = "INSERT INTO `error_log` VALUES ('$crawlID','$urlID','$error','$rightNow')";
	mysql_query($query);
}
function connectToDB() {
	$con = mysql_connect("localhost","root","");
	$db = mysql_select_db("crawls",$con);
	return $con;
}
?>