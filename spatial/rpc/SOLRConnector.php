<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SOLRManager.php');

$pArr = Array();
$pArr["q"] = (isset($_REQUEST["q"])?$_REQUEST["q"]:'*:*');
if(isset($_REQUEST["fq"])) $pArr["fq"] = $_REQUEST["fq"];
if(isset($_REQUEST["pt"])) $pArr["pt"] = $_REQUEST["pt"];
if(isset($_REQUEST["d"])) $pArr["d"] = $_REQUEST["d"];
if(isset($_REQUEST["rows"])) $pArr["rows"] = $_REQUEST["rows"];
if(isset($_REQUEST["start"])) $pArr["start"] = $_REQUEST["start"];
if(isset($_REQUEST["fl"])) $pArr["fl"] = $_REQUEST["fl"];
if(isset($_REQUEST["wt"])) $pArr["wt"] = $_REQUEST["wt"];

$canReadRareSpp = false;
if($GLOBALS['USER_RIGHTS']){
		if($GLOBALS['IS_ADMIN'] || array_key_exists("CollAdmin", $GLOBALS['USER_RIGHTS']) || array_key_exists("RareSppAdmin", $GLOBALS['USER_RIGHTS']) || array_key_exists("RareSppReadAll", $GLOBALS['USER_RIGHTS'])){
				$canReadRareSpp = true;
		}
}

$solrManager = new SOLRManager();
$origQ = $pArr["q"];
$secureQ = $solrManager->checkQuerySecurity($pArr["q"]);

#get protected results
$pArr["q"] = $secureQ;

if($pArr["wt"] == 'geojson'){
		$pArr["geojson.field"] = 'geo';
		$pArr["omitHeader"] = 'true';
}

$headers = array(
		'Content-Type: application/x-www-form-urlencoded',
		'Accept: application/json',
		'Cache-Control: no-cache',
		'Pragma: no-cache',
		'Content-Length: '.strlen(http_build_query($pArr))
);

$ch = curl_init();

$options = array(
		CURLOPT_URL => $SOLR_URL.'/select',
		CURLOPT_POST => true,
		CURLOPT_HTTPHEADER => $headers,
		CURLOPT_TIMEOUT => 90,
		CURLOPT_POSTFIELDS => http_build_query($pArr),
		CURLOPT_RETURNTRANSFER => true
);
curl_setopt_array($ch, $options);
$secureJSON = curl_exec($ch);
curl_close($ch);
$JSON = $secureJSON;


if (!$canReadRareSpp) {
	$pArr["q"] = $origQ;

	if($pArr["wt"] == 'geojson'){
			$pArr["geojson.field"] = 'geo';
			$pArr["omitHeader"] = 'true';
	}


	$headers = array(
			'Content-Type: application/x-www-form-urlencoded',
			'Accept: application/json',
			'Cache-Control: no-cache',
			'Pragma: no-cache',
			'Content-Length: '.strlen(http_build_query($pArr))
	);

	$ch = curl_init();

	$options = array(
			CURLOPT_URL => $SOLR_URL.'/select',
			CURLOPT_POST => true,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_TIMEOUT => 90,
			CURLOPT_POSTFIELDS => http_build_query($pArr),
			CURLOPT_RETURNTRANSFER => true
	);
	curl_setopt_array($ch, $options);
	$partialJSON = curl_exec($ch);
	curl_close($ch);
	
	$secure = json_decode($secureJSON);
	$partial = json_decode($partialJSON);
	if ($secure->response->numFound > $partial->response->numFound) {#some results have been suppressed
		$partial->response->hiddenFound = ($secure->response->numFound - $partial->response->numFound);
	}
	$partialJSON = json_encode($partial);
	$JSON = $partialJSON;
}

echo $JSON;

/*
object(stdClass)#5 (2) {
  ["responseHeader"]=>
  object(stdClass)#3 (3) {
    ["status"]=>
    int(0)
    ["QTime"]=>
    int(1)
    ["params"]=>
    object(stdClass)#4 (4) {
      ["q"]=>
      string(203) "(((sciname:Howellia\ aquatilis) OR (sciname:Howellia\ aquatilis\ *)) OR (tidinterpreted:(5665))) AND (decimalLatitude:[* TO *] AND decimalLongitude:[* TO *] AND sciname:[* TO *]) AND (localitySecurity:0)"
      ["start"]=>
      string(1) "0"
      ["rows"]=>
      string(1) "0"
      ["wt"]=>
      string(4) "json"
    }
  }
  ["response"]=>
  object(stdClass)#6 (3) {
    ["numFound"]=>
    int(13)
    ["start"]=>
    int(0)
    ["docs"]=>
    array(0) {
    }
  }
}
*/
?>