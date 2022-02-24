<?php
/*  =====================  INTERFACE BUILDING FUNCTIONS  =====================  */

//builds drop down domain and search menu
function buildDomains($search) {
	$con = connectToDB();
	$domains = array();
	if($search != '') {
		$query = mysql_query("SELECT * FROM `domains` WHERE `domain_name` LIKE '%$search%' GROUP BY `domain_name` ASC");
	}
	else {
		$query = mysql_query("SELECT * FROM `domains` WHERE `domain_name` != '' GROUP BY `domain_name` ASC");
	}
	if($search != '') {
		$parsedURL = parse_url($search);
		if(array_key_exists('scheme',$parsedURL)) {
			echo '<div class="scrollListing" onclick="backend(\'createNewDomain\',\'scrollyarea\',\''.$search.'\');">Add "<b>'.$search.'</b>"</div>';
		}
		else {
			echo '<div class="scrollListing" onclick="backend(\'createNewDomain\',\'scrollyarea\',\'http://'.$search.'\'); event.stopPropagation();">Add "http://<b>'.$search.'</b>"</div>';
		}
	}
	while($row = mysql_fetch_array($query)) {
		$domains[] = $row;
	}
	if(count($domains) == 0) {
		echo '<div class="dataRow">Please enter a domain above to get started</div>';
	}
	else {
		foreach($domains as $key=>$thisDomain) {
			$parsedD = parse_url($thisDomain['domain_name']);
			$query = mysql_query("SELECT * FROM `crawl_index` WHERE `domain_id` = '".$thisDomain['domain_id']."' GROUP BY `start` DESC LIMIT 1");
			$latestCrawl = mysql_fetch_array($query);
			$query = mysql_query("SELECT * FROM `keyword_reports` WHERE `domain_id` = '".$thisDomain['domain_id']."'");
			$totalReports = mysql_num_rows($query);
			$query = mysql_query("SELECT * FROM `crawl_index` WHERE `domain_id` = '".$thisDomain['domain_id']."'");
			$thisDomainSQL = mysql_fetch_array($query);
			echo '<div class="scrollListing" onclick="backend(\'buildTabs\',\'tabBar\',\''.$thisDomain['domain_id'].'\',\''.$latestCrawl['id'].'\'); backend(\'buildCrawl\',\'scrollyarea\',\''.$latestCrawl['id'].'\');">'.$parsedD['host'].'<span class="listingInfo">'.mysql_num_rows($query).' crawls - '.$totalReports.' weeks of rankings</span>';
			echo '</div>';
		}
	}
}

//builds tab interface based on selected domain and crawl
function buildTabs($domainID,$selectedTab,$compared1 = null,$compared2 = null) {
	
	$con = connectToDB();
	$query = mysql_query("SELECT * FROM `crawl_index` WHERE `domain_id` = '$domainID'");
	$totalRecords = mysql_num_rows($query);
	
	$thisTab = 1;
	echo '<div class="scrollListing';
	$enableRefresh = false;
	if($selectedTab == 'setting') { echo ' selected'; }
	echo '" onclick="backend(\'buildTabs\',\'tabBar\',\''.$domainID.'\',\'setting\'); backend(\'buildSettings\',\'scrollyarea\',\''.$domainID.'\');">Settings</div>';
	
	echo '<div class="bezel">Crawls</div>';
	echo '<div class="scrollListing';
	if($selectedTab == 'new') { echo ' selected'; }
	echo '" onclick="backend(\'buildTabs\',\'tabBar\',\''.$domainID.'\',\'new\'); backend(\'buildNewCrawl\',\'scrollyarea\',\''.$domainID.'\');">New Crawl</div>';
	while($row = mysql_fetch_array($query)) {
		echo '<div class="scrollListing';
		if(($selectedTab == $row['id'] || ($thisTab == $totalRecords && $selectedTab == -1)) && !$compared1) {
			echo ' selected';
			if($row['status'] == 1) {
				$enableRefresh = true;
				$refreshPane = $row['id'];
			}
		}
		if($compared1 == $row['id'] || $compared2 == $row['id']) {
			echo ' compared';
		}
		echo '" onclick="backend(\'buildTabs\',\'tabBar\',\''.$row['domain_id'].'\',\''.$row['id'].'\'); backend(\'buildCrawl\',\'scrollyarea\',\''.$row['id'].'\');">';
		if($row['status'] == 0) { echo '&nbsp;<div class="error">!</div>'; }
		if($row['status'] == 1) { echo '&nbsp;<img src="/images/5.gif">'; }
		if($row['status'] == 2) { echo '&nbsp;<div class="complete">&#10003;</div>'; }
		if($row['status'] == 3) { echo '&nbsp;<div class="error">x</div>'; }
		echo date('m/d',$row['start']);
		if($selectedTab != "new" && $row['status'] == 2) {
			echo '<span class="listingInfo" onclick="backend(\'buildCompare\',\'scrollyarea\',\''.$domainID.'\',\''.$row['id'].'\',\''.$selectedTab.'\'); backend(\'buildTabs\',\'tabBar\',\''.$row['domain_id'].'\',\''.$selectedTab.'\',\''.$row['id'].'\',\''.$selectedTab.'\'); event.stopPropagation();">Compare</span>';
		}
		echo '</div>';
		$thisTab++;
	}
	if(!$enableRefresh) { echo '<img src="/images/blank.gif" onload="stopRefresh();">'; }
}

function buildCrawl($crawlID) {
	$con = connectToDB();
	$query = mysql_query("SELECT * FROM `crawl_index` WHERE `id` = '$crawlID'");
	$crawlData = mysql_fetch_array($query);
	$query = mysql_query("SELECT * FROM `domains` WHERE `domain_id` = '".$crawlData['domain_id']."'");
	$domainData = mysql_fetch_array($query);
	$end = date('m/d h:m a',$crawlData['end']);
	if($crawlData['status'] == 1) {
		$end = '<i>In Progress</i>';
	}
	
	$query = mysql_query("SELECT `url_id`,`crawl_status`,`crawl_url`,`http_status`,`response_time`,`keyword_status` FROM `urls` WHERE `crawl_id` = '$crawlID'");
	$complete = true;
	$incompleteURLs = 0;
	$completeURLs = 0;
	$urlDetail = '';
	$keywordComplete = true;
	while($row = mysql_fetch_array($query)) {
		if($row['crawl_status'] == 0) {
			$urlDetail .= '<div class="dataRow inprogress"><a href="'.$row['crawl_url'].'" target="_blank"><img src="images/open_new_window.png"></a>'.$row['crawl_url'].'</div>';
			$complete = false;
			$incompleteURLs++;
		}
		else {
			$urlDetail .= '<div class="dataRow"><a href="'.$row['crawl_url'].'" target="_blank"><img src="images/open_new_window.png"></a> - <a href="#" onclick="createModal(\'be.php?rf=buildURLDetail&rd[]='.$row['url_id'].'\');" class="urlInfo"><img src="images/glass.png">'.$row['crawl_url'].'</a>'.$row['http_status'].' ('.$row['response_time'].')</div>';
			$completeURLs++;
		}
		if($row['keyword_status'] == 0) {
			$keywordComplete = false;
		}
	}
	echo '<div id="status">'.$domainData['domain_name'].' - <b>'.date('m/d h:m a',$crawlData['start']).'</b> - <b>'.$end.'</b><br>Crawl Entry URL:'.$crawlData['entry_url'].'<br>URLs:'.$completeURLs.' of '.($completeURLs + $incompleteURLs).'<br>';
	if($crawlData['status'] == 0) { echo '<div class="error">!</div> There were errors during this crawl'; }
	if($crawlData['status'] == 1) { echo 'In Progress <input type="button" value="Stop" onclick="backend(\'stopCrawl\',\'\',\''.$crawlID.'\');">'; }
	if($crawlData['status'] == 2) { echo '<div class="complete">&#10003;</div> Complete <input type="button" value="Keyword Density" onclick="createModal(\'be.php?rf=buildKeywordDensity&rd[]='.$crawlID.'\');">'; }
	if($crawlData['status'] == 3) { echo '<div class="error">x</div> Crawl was stopped <input type="button" value="Resume" onclick="backend(\'startCrawl\',\'urlError\',\''.$crawlID.'\');">'; }
	if($keywordComplete) { echo '<input type="button" value="Keyword Search" onclick="createModal(\'be.php?rf=buildKeywordSearch&rd[]='.$crawlID.'\');">'; }
	echo '</div>';
	echo $urlDetail;
	if($complete) { echo '<img src="/images/blank.gif" onload="stopRefresh();">'; }
}
function buildNewCrawl($domainID) {
	$con = connectToDB();
	$query = mysql_query("SELECT * FROM `domains` WHERE `domain_id` = '".$domainID."'");
	$domainData = mysql_fetch_array($query);
	echo '<div id="status">Create new crawl for the domain <b>'.$domainData['domain_name'].'<b></div>';
	echo '<div class="dataRow">Entry URL:<br><input id="urlCrawl" style="width:400px;" type="text" value="'.$domainData['domain_name'].'" onkeyup="backend(\'verifyURL\',\'urlError\',ge(\'urlCrawl\').value);"></div>';
	echo '<div class="dataRow" id="urlError"></div>';
	echo '<div class="dataRow"><input type="button" value="Start Crawl" onclick="backend(\'createNewCrawl\',\'urlError\',\''.$domainID.'\',ge(\'urlCrawl\').value);"></div>';
}
function buildKeywordDensity($crawlID) {
	$con = connectToDB();
	$sql = "SELECT `keyword_status` FROM `urls` WHERE `keyword_status` = 0 AND `crawl_id` = '$crawlID'";
	$result = mysql_query($sql);
	if(mysql_num_rows($result) > 0) {
		echo "Keyword Density has not been calculated for this specific crawl<br>";
		echo '<input type="button" value="Analyze Keyword Density" onclick="backend(\'analyzeCrawl\',\'\',\''.$crawlID.'\'); refreshModal(\'buildKeywordDensityProgress\',\''.$crawlID.'\');">';
	}
	else {
		$sql =  "SELECT * FROM `keyword_density` WHERE `crawl_id` = '$crawlID' ORDER BY `count` DESC";
		$result = mysql_query($sql);
		echo '<div class="scrollArea" style="text-align:left; background-color:#EEEEEE;"><div class="status">Keyword Density Report</div>';
		while($row = mysql_fetch_array($result)) {
			$jdata = json_decode($row['connected_pages']);
			$max = 0;
			foreach($jdata as $page) {
				if($page[1] > $max) {
					$max = $page[1];
				}
			}
			echo '<div class="keywordFrame" onclick="backend(\'buildKWDURL\',\'kwd'.$row['id'].'\',\''.$row['id'].'\');"><span class="keyword">'.$row['keyword'].'</span> ('.$row['count'].' total occurences) - Max Density Page:'.$max.' - Average Per Page:'.($row['count']/count($jdata)).'</div><div id="kwd'.$row['id'].'"></div>';
		}
		echo '</div>';
	}
}
function buildKWDURL($kwdID) {
	$con = connectToDB();
	$sql = "SELECT * FROM `keyword_density` WHERE `id` = '$kwdID'";
	$result = mysql_query($sql);
	$data = mysql_fetch_array($result);
	$jdata = json_decode($data['connected_pages']);
	foreach($jdata as $page) {
		$sql = "SELECT `crawl_url` FROM `urls` WHERE `url_id` = '$page[0]'";
		$result = mysql_query($sql);
		$data = mysql_fetch_array($result);
		echo '<div class="dataRow"><a href="'.$data['crawl_url'].'" target="_blank"><img src="images/open_new_window.png"></a> - <a href="#" class="urlInfo"><img src="images/glass.png">'.$data['crawl_url'].'</a>'.$page[1].'</div>';
	}
}
function buildKeywordSearch($crawlID,$search = '') {
	echo '<div class="scrollArea" style="text-align:left; background-color:#EEEEEE;"><div class="status">Keyword <input type="text" onkeyup="backend(\'buildKeywordSearchResults\',\'kwSearch\',\''.$crawlID.'\',this.value);" value="'.$search.'"></div>';
	echo '<div id="kwSearch">';
	echo '</div></div>';

}
function buildKeywordSearchResults($crawlID,$search = '') {
	$con = connectToDB();
	if($search != '') {
		$sql = "SELECT * FROM `keyword_density` WHERE `crawl_id` = '$crawlID' AND `keyword` LIKE '$search%' ORDER BY `keyword` ASC";
		$result = mysql_query($sql);
		while($row = mysql_fetch_array($result)) {
			echo '<div class="dataRow" onclick="toggle(\'buildKeywordNetwork\',\'kwdURL'.$row['id'].'\',\''.$row['id'].'\');"><span class="urlInfo">'.$row['keyword'].'</span>'.count(json_decode($row['connected_pages'])).' pages</div><div class="indentbox" id="kwdURL'.$row['id'].'"></div>';
		
		}
	}
}
function buildKeywordNetwork($kwdID) {
	$con = connectToDB();
	$sql = "SELECT * FROM `keyword_density` WHERE `id` = '$kwdID'";
	$result = mysql_query($sql);
	$data = mysql_fetch_array($result);
	$jdata = json_decode($data['connected_pages']);
	
	$urlGroups = array();
	foreach($jdata as $page) {
		$query = mysql_query("SELECT `links_in` FROM `urls` WHERE `url_id` = '".$page[0]."'");
		$linkedURLs = mysql_fetch_array($query);
		$lURLs = json_decode($linkedURLs['links_in']);
		
		if($lURLs != '') {
			foreach($lURLs as $thisURL) {
				if(array_key_exists($thisURL,$urlGroups)) {
					$urlCount = $urlGroups[$thisURL];
					$urlCount++;
					$urlGroups[$thisURL] = $urlCount;
				}
				else {
					$urlGroups[$thisURL] = 1;
					$urlGroups[$thisURL] = 1;
				}
			}
		}
	}
	$urlGroups = array_unique($urlGroups);
	arsort($urlGroups);
	$matchMatrix = array();
	
	
	foreach($urlGroups as $key=>$thisUrlGroup) {
		foreach($jdata as $page) {
			$matchMatrix[$key][$page[0]] = false;
		}
	}
	
	foreach($matchMatrix as $key=>$thisGroup) {
		$query = mysql_query("SELECT `crawl_url`,`links_out` FROM `urls` WHERE `url_id` = '$key'");
		$mainURL = mysql_fetch_array($query);
		$lURLs = json_decode($mainURL['links_out']);
		foreach($lURLs as $thisURL) {
			foreach($jdata as $page) {
				if($page[0] == $thisURL) {
					$matchMatrix[$key][$page[0]] = true;
				}
			}
		}
	}
	
	foreach($matchMatrix as $key=>$thisGroup) {
		echo '<div class="groupFrame">';
		$query = mysql_query("SELECT `crawl_url` FROM `urls` WHERE `url_id` = '$key'");
		$mainURL = mysql_fetch_array($query);
		echo '<div class="parentURL"><a href="'.$mainURL['crawl_url'].'" target="_blank">'.$mainURL['crawl_url'].'</a> links to</div>';
		foreach($thisGroup as $pageID=>$linked) {
			$query = mysql_query("SELECT `crawl_url` FROM `urls` WHERE `url_id` = '$pageID'");
			$thisURL = mysql_fetch_array($query);
			echo '<a href="'.$thisURL['crawl_url'].'" target="_blank"';
			if(!$linked) {
				echo ' class="notIncluded"';
			}
			echo '>'.$thisURL['crawl_url'].'</a>';
		}
		echo '</div>';
	}
}
function buildKeywordDensityProgress($crawlID) {
	$con = connectToDB();
	$sql = "SELECT `keyword_status` FROM `urls` WHERE `crawl_id` = '$crawlID' AND `keyword_status` = 0";
	$result = mysql_query($sql);
	$unfinishedURLs = mysql_num_rows($result);
	
	$sql = "SELECT `keyword_status` FROM `urls` WHERE `crawl_id` = '$crawlID' AND `keyword_status` = 1";
	$result = mysql_query($sql);
	$finishedURLs = mysql_num_rows($result);
	

	if($unfinishedURLs != $finishedURLs) {
		echo 'Analyzing '.$finishedURLs.' pages out of '.($unfinishedURLs + $finishedURLs);
	}
	else {
		echo '<img src="/images/blank.gif" onload="stopRefresh();">';
		buildKeywordDensity($crawlID);
	}
}
function buildReport($crawlID) {
	global $startTime;
	$con = connectToDB();
	$query = mysql_query("SELECT * FROM `crawl_index` WHERE `id` = '$crawlID' LIMIT 1");
	$crawlData = mysql_fetch_array($query);
	$rootDomain = parse_url($crawlData['domain']);
	
	echo 'Crawl report for '.$rootDomain['scheme'].'://'.$rootDomain['host'].' @ '.date('Y-m-d H:i:s e',$crawlData['start']).'<br>';
	
	$query = mysql_query("SELECT * FROM `urls` WHERE `crawl_id` = '$crawlID'");
	while($row = mysql_fetch_array($query)) {
		echo $row['crawl_url'].' ['.$row['http_status'].'] @ '.date('Y-m-d H:i:s e',$row['crawl_timestamp']).'<br>';
	}
	if($crawlData['end'] == 0) {
		echo 'Elapsed Crawl Time:'.date('i:s',time() - $crawlData['start']);
	}
	else {
		echo 'Total Crawl Time:'.date('i:s',$crawlData['end'] - $crawlData['start']);
	}
}
function buildCompare($domainID,$crawl1,$crawl2) {
	$con = connectToDB();
	$url1 = array();
	$url2 = array();
	$query = mysql_query("SELECT * FROM `crawl_index` WHERE `id` = '$crawl1' LIMIT 1");
	$cData1 = mysql_fetch_array($query);
	$query = mysql_query("SELECT * FROM `crawl_index` WHERE `id` = '$crawl2' LIMIT 1");
	$cData2 = mysql_fetch_array($query);
	$query = mysql_query("SELECT * FROM `domains` WHERE `domain_id` = '".$domainID."'");
	$domainData = mysql_fetch_array($query);
	echo '<div id="status">';
	if($cData1['status'] != 2) {
		echo 'crawl 1 was not completed</div>';
		echo 'crawl1 ID:'.$crawl1.'<br>crawl2 ID:'.$crawl2;
	}
	elseif($cData2['status'] !=2) {
		echo 'crawl 2 was not completed</div>';
		echo 'crawl1 ID:'.$crawl1.'<br>crawl2 ID:'.$crawl2;
	}
	else {
		if($cData1['start'] > $cData2['start']) {
			$tempData = $cData1;
			$cData1 = $cData2;
			$cData2 = $cData1;
			$tempCrawl = $crawl1;
			$crawl1 = $crawl2;
			$crawl2 = $tempCrawl;
		}
		echo 'Comparing crawls for '.$domainData['domain_name'].'<br>';
		echo 'From '.date('Y/m/d H:s a',$cData1['start']).' to '.date('Y/m/d H:s a',$cData2['start']);
		echo '</div>';
		$query = mysql_query("SELECT `url_id`,`crawl_url`,`http_status`,`hash` FROM `urls` WHERE `crawl_id` = '$crawl1' GROUP BY `crawl_url` ASC");
		while($row = mysql_fetch_array($query)) {
			$url1[$row['crawl_url']] = array('url_id' => $row['url_id'],'http_status' => $row['http_status'],'hash' => $row['hash']);
		}
		$query = mysql_query("SELECT `url_id`,`crawl_url`,`http_status`,`hash` FROM `urls` WHERE `crawl_id` = '$crawl2' GROUP BY `crawl_url` ASC");
		while($row = mysql_fetch_array($query)) {
			$url2[$row['crawl_url']] = array('url_id' => $row['url_id'],'http_status' => $row['http_status'],'hash' => $row['hash']);
		}
		$removedURLs = array_diff_key($url1,$url2);
		$addedURLs = array_diff_key($url2,$url1);
		$common = array_intersect_key($url1,$url2);
		echo '<div class="row header">Common URLs ('.count($common).')</div>';
		foreach($common as $urlKey=>$record) {
			echo '<div class="row">';
			if($url1[$urlKey] === $url2[$urlKey]) {
				echo '<a href="'.$urlKey.'" target="_blank" class="urlInfo"><img src="images/open_new_window.png">'.$urlKey.'</a><br>';
			}
			else {
				echo '<a href="'.$urlKey.'" target="_blank" class="urlInfo"><img src="images/open_new_window.png">'.$urlKey.'</a>';
				if($url1[$urlKey]['http_status'] !== $url2[$urlKey]['http_status']) {
					echo ' - HTTP Status Changed';
				}
				if($url1[$urlKey]['hash'] !== $url2[$urlKey]['hash']) {
					//echo ' - Content Changed';
					echo '<a href="#" onclick="createModal(\'be.php?rf=buildURLCompare&rd[]='.$url1[$urlKey]['url_id'].'&rd[]='.$url2[$urlKey]['url_id'].'\');" class="urlInfo"><img src="images/glass.png">View Revisions</a>';
				}
			}
			echo '</div>';
		}
		echo '<div class="row header">New URLs ('.count($addedURLs).')</div>';
		foreach($addedURLs as $urlKey=>$record) {
			echo '<div class="row"><a href="'.$urlKey.'" target="_blank" class="urlInfo"><img src="images/open_new_window.png">'.$urlKey.'</a></div>';
		}
		echo '<div class="row header">Removed URLs ('.count($removedURLs).')</div>';
		foreach($removedURLs as $urlKey=>$record) {
			echo '<div class="row"><a href="'.$urlKey.'" target="_blank" class="urlInfo"><img src="images/open_new_window.png">'.$urlKey.'</a></div>';
		}
	}
}
function buildURLDetail($urlID) {
	$con = connectToDB();
	$query = mysql_query("SELECT * FROM `urls` WHERE `url_id` = '$urlID' LIMIT 1");
	$urlData = mysql_fetch_array($query);
	
	$thisURL = $urlData['crawl_url'];
	
	
	
	echo '<div class="scrollArea"><div class="status"><a href="'.$thisURL.'" class="title" target="_blank"><img src="images/open_new_window.png">'.$thisURL.'</a>';
	echo '<span class="split">'.nl2br($urlData['http_full_header']).'</span><span class="split"><b>Crawl History for this URL</b><br>';
	$query = mysql_query("SELECT `url_id`,`crawl_id`,`crawl_timestamp`,`hash` FROM `urls` WHERE `crawl_url` = '$thisURL'");
	$lastHash = -1;
	$lastCrawlID = -1;
	$domainID = getDomainIDfromURLID($urlID);
	while($row = mysql_fetch_array($query)) {
		if($lastHash == -1) {
			$lastHash = $row['hash'];
			$lastCrawlID = $row['crawl_id'];
		}
		if($row['crawl_timestamp'] == $urlData['crawl_timestamp']) {
			echo '<b>'.date('m/d h:s a',$row['crawl_timestamp']).' - Currently Viewing</b><br>';		
		}
		else {
			echo date('m/d h:s a',$row['crawl_timestamp']).' - <span class="fLink" onclick="closeModal(); backend(\'buildTabs\',\'tabBar\',\''.$domainID.'\',\''.$row['crawl_id'].'\'); backend(\'buildCrawl\',\'scrollyarea\',\''.$row['crawl_id'].'\');">View Crawl</span> | <span class="fLink" onclick="createModal(\'be.php?rf=buildURLDetail&rd[]='.$row['url_id'].'\');">View URL Data</span><br>';
		}
		if($row['hash'] != $lastHash) {
			echo '<span class="note">Content Changed (<span class="fLink" onclick="closeModal(); backend(\'buildCompare\',\'scrollyarea\',\''.$domainID.'\',\''.$lastCrawlID.'\',\''.$row['crawl_id'].'\'); backend(\'buildTabs\',\'tabBar\',\''.$domainID.'\',\''.$row['crawl_id'].'\',\''.$lastCrawlID.'\',\''.$row['crawl_id'].'\');">Compare Crawls</span> | <span class="fLink" onclick="closeModal(); createModal(\'be.php?rf=buildURLCompare&rd[]='.$lastCrawlID.'&rd[]='.$row['url_id'].'\');" class="urlInfo">View Revisions</span>)</span>';
			$lastHash = $row['hash'];
			$lastCrawlID = $row['crawl_id'];
		}
	}
	
	$query = mysql_query("SELECT `raw_html` FROM `raw_html` WHERE `url_id` = '$urlID'");
	$data = mysql_fetch_array($query);
	echo '</div><div class="dataView">'.nl2br(htmlentities($data['raw_html'])).'</div></div>';
}
function buildURLCompare($oldURLID,$newURLID) {
	$con = connectToDB();
	$query = mysql_query("SELECT `raw_html`,`crawl_id`,`crawl_timestamp` FROM `urls` WHERE `url_id` = '$oldURLID'");
	$oldUrlData = mysql_fetch_array($query);
	$query = mysql_query("SELECT `raw_html` FROM `raw_html` WHERE `url_id` = '$oldURLID'");
	$htmlA = mysql_fetch_array($query);
	$dataA = $htmlA['raw_html'];
	$query = mysql_query("SELECT `raw_html`,`crawl_url`,`crawl_id`,`crawl_timestamp` FROM `urls` WHERE `url_id` = '$newURLID'");
	$newUrlData = mysql_fetch_array($query);
	$query = mysql_query("SELECT `raw_html` FROM `raw_html` WHERE `url_id` = '$newURLID'");
	$htmlB = mysql_fetch_array($query);
	$dataB = $htmlB['raw_html'];
	
	$arrayA = explode("\n",$dataA);
	$arrayB = explode("\n",$dataB);
	$flipB = array_flip($arrayB);
	$pairs = array(array(0,0));
	$data = array();

	foreach($arrayA as $key=>$item) {
		if(isset($flipB[$item])) {
			$pairs[] = array($key,$flipB[$item]);
			$newKey = $flipB[$item];
			$flipB = array_flip($flipB);
			$flipB[$newKey] = '';
			$flipB = array_flip($flipB);
		}
	}

	$pLen = count($pairs);

	if($pLen > 1) {
		foreach($pairs as $key=>$thisPair) {
			if($key != 0) {
				for($i = $pairs[$key-1][0]+1; $i < $thisPair[0]; $i++) { $data[] = array($arrayA[$i],'',''); }
				for($i = $pairs[$key-1][1]+1; $i < $thisPair[1]; $i++) { $data[] = array('',$arrayB[$i],''); }
				$data[] = array('','',$arrayA[$thisPair[0]]);
			}
		}
		$output = '';
		$totalEdits = 0;
		foreach($data as $thisItem) {
			if($thisItem[0] != '') {
				$output .= '<span class="editStrike">'.nl2br(htmlentities($thisItem[0])).'</span>';
				$totalEdits++;
			}
			if($thisItem[1] != '') {
				$output .= '<span class="editAdd">'.nl2br(htmlentities($thisItem[1])).'</span>';
				$totalEdits++;
			}
			if($thisItem[2] != '') { $output .= nl2br(htmlentities($thisItem[2])); }
		}
		similar_text($dataA,$dataB,$percSim);
		echo '<div class="scrollArea"><div class="status"><a href="'.$newUrlData['crawl_url'].'" class="title" target="_blank"><img src="images/open_new_window.png">'.$newUrlData['crawl_url'].'</a>Comparing URL data from:<br>';
		echo '<span class="split"><b>'.date('m/d h:s a',$oldUrlData['crawl_timestamp']).'</b><br><span class="fLink" onclick="createModal(\'be.php?rf=buildURLDetail&rd[]='.$oldURLID.'\');">View URL Data</span> | <span class="fLink" onclick="closeModal(); backend(\'buildTabs\',\'tabBar\',\''.getDomainIDfromURLID($oldURLID).'\',\''.$oldUrlData['crawl_id'].'\'); backend(\'buildCrawl\',\'scrollyarea\',\''.$oldUrlData['crawl_id'].'\');">View Crawl</span></span>';
		echo '<span class="split"><b>'.date('m/d h:s a',$newUrlData['crawl_timestamp']).'</b><br><span class="fLink" onclick="createModal(\'be.php?rf=buildURLDetail&rd[]='.$newURLID.'\');">View URL Data</span> | <span class="fLink" onclick="closeModal(); backend(\'buildTabs\',\'tabBar\',\''.getDomainIDfromURLID($newURLID).'\',\''.$newUrlData['crawl_id'].'\'); backend(\'buildCrawl\',\'scrollyarea\',\''.$newUrlData['crawl_id'].'\');">View Crawl</span></span><br><br>';
		echo 'Edits:'.$totalEdits.' - Similiarity:'.round($percSim).'%</div>';
		echo '<div class="dataView">';
		echo $output;
		echo '</div></div>';
	}
	else {
		echo 'nothing similar';
	}
}
?>