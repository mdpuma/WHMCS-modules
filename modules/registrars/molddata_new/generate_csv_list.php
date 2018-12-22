<?php

$host = 'epp.nic.md';
$port = 700;
$epp_user = 'asd';
$epp_pass = 'asd';

$user = 'asd';
$pass = 'asd';

// ############################################################################################################

require_once './lib/nicmdEppClient.php';


$list = file_get_contents("list.csv");
$list = explode("\n", $list);

$fp = fopen("results.csv", "w");

$epp = new nicmdEppClient($host, $port, $user, $pass);
$epp->login($epp_user, $epp_pass);

$jj = count($list);
foreach($list as $domain) {
	print "Get info for $domain, remaining $jj\n";
	$data = $epp->getContactDetails($domain);

	$line = '"'.$domain.'"';
	foreach($data as $t) {
		foreach($t as $i) {
			$line.= ',"'.$i.'"';
		}
	}
	$line .="\n";

	fwrite($fp, $line);
	// var_dump($domain);
	$jj--;
}

$epp->logout();
fclose($fp);
