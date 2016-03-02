<?php
	$apiURL=			"https://us12.api.mailchimp.com/3.0/";
	$apiKey=			"*************************************";
	$listID=			"**********";
	$apiCall=			$apiURL."lists/".$listID;
	$historyFilePath=	"./newsletterhistory/";
	$daysBack= 			7;		// determines how many days back the history will be shown
	$removeOldHistory=	true; 	// remove files older than $daysBack
	$oneDayInSeconds=	86400;	// don't change this ;)

	
	// Save current value
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
	
	// Get member_count for the whole week
	$result = array();
	for ($x = 0; $x <= $daysBack; $x++) {
		$xDaysAgo=date("Y-m-d",(strtotime(gmdate('Y-m-d'))-$oneDayInSeconds*$x));	
		$xDaysAgoFilePath=$historyFilePath.$xDaysAgo.".php";

		if($x===$daysBack){
			if($removeOldHistory && file_exists($xDaysAgoFilePath)){
				unlink($xDaysAgoFilePath);
			}
		}elseif(file_exists($xDaysAgoFilePath)){
			$memberCountXDaysAgo = json_decode(file_get_contents($xDaysAgoFilePath))->member_count;
			$subresult = array('date' => $xDaysAgo, 'member_count' => $memberCountXDaysAgo, 'diff' => null);	
		}else{
			$subresult = array('date' => $xDaysAgo, 'member_count' => null, 'diff' => null);	
		}
		array_push($result, $subresult);
	}
	// Calculate Diff's
	for ($x = 0; $x <= $daysBack-2 ; $x++) {
		$next=$result[$x+1]['member_count'];
		if($next===null){
			$result[$x]['diff']=0;
		}else{
			$result[$x]['diff']=$result[$x]['member_count']-$next;
		}
	}
	// Output json
	header	('Content-Type: application/json; charset=utf-8');
	echo(json_encode($result));
?>
