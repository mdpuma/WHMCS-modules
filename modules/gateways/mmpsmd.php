<?php

function mmpsmd_config()
{
	return array(
		"FriendlyName" => array(
			"Type" => "System",
			"Value" => "MMPS Terminal (MD)"
		) ,
		"ips" => array(
			"FriendlyName" => "MMPS IPs",
			"Type" => "textarea",
			"Size" => "75",
			"Description" => "Enter IP (1 IP per line)",
			'Default' => "217.12.118.190"
		)
	);
}

function mmpsmd_link($params)
{
	global $_LANG;
	return '<form method="post" action="./modules/gateways/callback/mmpsmd_info.php"><input type="hidden" name="invoiceid" value="' . $params['invoiceid'] . '" /><input type="hidden" name="amount" value="' . $params['amount'] . '" /><input type="hidden" name="user" value="' . $params['clientdetails']['firstname'] . " " . $params['clientdetails']['lastname'] . '" /><input type="hidden" name="email" value="' . $params['clientdetails']['email'] . '" /><input type="submit" value="' . $_LANG["invoicespaynow"] . '" /></form>';
}
