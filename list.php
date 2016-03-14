<?php
	$apiURL=			"https://us12.api.mailchimp.com/3.0/";
	$apiKey=			"*************************************";
	$listID=			"**********";
	$secret=			"*********************************************************";
	$secretCheck=			true; // if true, a URL parameter secret has to be defined which is equal to $secret
	$apiCall=			$apiURL."lists/".$listID;
	$historyFilePath=		"./newsletterhistory/";
	$daysBack= 			7	;g// determines how many days back the history will be shown
	$removeOldHistory=		true; 	// remove files older than $daysBack
	$oneDayInSeconds=		86400;	// don't change this ;)
	
		
	if(($secretCheck && isset($_GET['secret']) && hash_equals($_GET['secret'],$secret)) || !$secretCheck){	

		$currentMemberCount= saveCurrent();
		
		if($removeOldHistory && file_exists($historyFilePath.xDaysAgo($daysBack).".php")){
			unlink($historyFilePath.xDaysAgo($daysBack).".php");
		}
		
		$result=getResults();
		
		$result=(isset($_GET['asc']))?array_reverse($result):$result;
		
		$totalDiff=0;
		$elementsExist=(isset($_GET['asc']))?count($result)-1:count($result)-2;
		$start=(isset($_GET['asc']))?1:0;
		$lookaheadDirection=(isset($_GET['asc']))?-1:1;
		for ($x = $start; $x <= $elementsExist ; $x++) {
			$next=$result[$x+(1*$lookaheadDirection)]['member_count'];
			$result[$x]['diff']=($result[$x]['member_count']-$next);
			$totalDiff=$totalDiff+$result[$x]['diff'];
		}
		
		addTotalDiffIndicator($totalDiff);
		
		if(isset($_GET['render']) || isset($_GET['mail'])){		
			$body=getBody($result,$totalDiff,$currentMemberCount);
			if(isset($_GET['render'])){
				echo($body);
			}
			if(isset($_GET['mail'])){
				if(!hash_equals($_GET['secret'],$secret)){
					die();
				}
				// USE YOUR MAIL METHOD HERE
			}
		}else{
			header	('Content-Type: application/json; charset=utf-8');
			echo(json_encode($result));		
		}
	}
	
	
	function getBody($result,$totalDiff,$currentMemberCount){
		$factor=getHighestMemberCount($result)/10;
		$body="Trend for the last ".count($result)." Days: ".$totalDiff."<br><br>";
		$body=$body."Current Subscriber count: $currentMemberCount<br><br>";
		$body=$body.'<table border="0"><tr>';
		foreach($result as $k=>$v){
			foreach($v as $ki=>$vi){
				if($ki==='date'){
					$body=$body."<td>&nbsp;&nbsp;<b>".$vi."</b>&nbsp;&nbsp;</td>";
				}
			}
		}
		$body=$body."</tr><tr>";
		foreach($result as $k=>$v){
			foreach($v as $ki=>$vi){
				if($ki==='member_count'){
					if($vi/$factor < 1.0){
						if($vi===null){
							$bar="?";
						}else{
							$bar="&#x25FB;";
						}
					}else{
						$bar="";
						$fractionPart = fmod($vi/$factor, 1);
						if($fractionPart>=0.5){
							$bar="&#x25FB;<br>";
						}
						$bar=$bar.str_repeat("&#x25A7;<br>",$vi/$factor);	
					}
					$body=$body."<td valign=bottom align=center >".$bar."</td>";
				}
			}
		}
		$body=$body."</tr><tr>";
		foreach($result as $k=>$v){
			foreach($v as $ki=>$vi){
				if($ki==='member_count'){
					$body=$body."<td align=center >$vi</td>";
				}
			}
		}
		$body=$body."</tr></table>";
		return $body;
	}
	
	function addTotalDiffIndicator(&$totalDiff){
		if($totalDiff>0){
			$totalDiff="&uarr;".$totalDiff;
		}elseif($totalDiff<0){
			$totalDiff="&darr;".abs($totalDiff);
		}
	}
	
	function getResults(){
		global $daysBack;
		$result = array();
		for ($x = 0; $x < $daysBack; $x++) {
				if(!fillResult($x,$result)){
					break;
				}
		}
		return $result;
	}
	
	function fillResult($x,&$result){
		global $historyFilePath;
		$xDaysAgo=xDaysAgo($x);
		$xDaysAgoFilePath=$historyFilePath.$xDaysAgo.".php";
		if(file_exists($xDaysAgoFilePath)){
			$memberCountXDaysAgo = json_decode(file_get_contents($xDaysAgoFilePath))->member_count;
			$subresult = array('date' => $xDaysAgo, 'member_count' => $memberCountXDaysAgo, 'diff' => 0);	
		}else{
			return false;
		}
		array_push($result, $subresult);
		return true;
	}
	
	function getHighestMemberCount($result){
		$highestMemberCount=1;
		foreach($result as $k=>$v){
			foreach($v as $ki=>$vi){
				if($ki==='member_count'){
					$highestMemberCount=($vi>$highestMemberCount)?$vi:$highestMemberCount;
				}
			}
		}
		return $highestMemberCount;
	}
	
	function saveCurrent(){
		global $apiCall; global $apiKey; global $historyFilePath;
		$opts = array(
		  'http'=>array('method'=>"GET", 'header' => "Authorization: Basic " . base64_encode("anystring:$apiKey")),
		  'ssl'=>array( 'verify_peer' => false, /*"cafile" => "cacert.pem" */)
		);
		$apiCallContent = file_get_contents($apiCall, false, stream_context_create($opts));
		$currentMemberCount = json_decode($apiCallContent)->stats->member_count;
		file_put_contents(
			$historyFilePath.date("Y-m-d").".php",
			json_encode(array("member_count" => $currentMemberCount))
		);
		return $currentMemberCount;
	}
	
	function xDaysAgo($x){
		global $oneDayInSeconds;
		return date("Y-m-d",(strtotime(gmdate('Y-m-d'))-$oneDayInSeconds*$x));	
	}
?>
