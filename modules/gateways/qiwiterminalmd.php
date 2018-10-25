<?php

function qiwiterminalmd_config()
{
	return array(
		"FriendlyName" => array(
			"Type" => "System",
			"Value" => "QIWI Terminal (MD)"
		) ,
		"ips" => array(
			"FriendlyName" => "QIWI IPs",
			"Type" => "textarea",
			"Size" => "75",
			"Description" => "Enter IP (1 IP per line)",
			'Default' => "195.22.228.238\n95.65.95.54\n95.65.95.53"
		)
	);
}

function qiwiterminalmd_link($params)
{
	global $_LANG;
	return '<form method="post" action="./modules/gateways/callback/qiwiterminalmd_info.php"><input type="hidden" name="invoiceid" value="' . $params['invoiceid'] . '" /><input type="hidden" name="amount" value="' . $params['amount'] . '" /><input type="hidden" name="user" value="' . $params['clientdetails']['firstname'] . " " . $params['clientdetails']['lastname'] . '" /><input type="hidden" name="email" value="' . $params['clientdetails']['email'] . '" /><input type="submit" value="' . $_LANG["invoicespaynow"] . '" /></form>';
}
