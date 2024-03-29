<?php
use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use Illuminate\Database\Capsule\Manager as Capsule;

require_once 'lib/nicmdEppClient.php';

function molddata_getConfigArray() {
	$configarray = array(
		"FriendlyName" => array(
			"Type" => "System",
			"Value" => "MoldData"
		) ,
		"EPPUser" => array(
			"Type" => "text",
			"Size" => "40",
			"Description" => "EPP Username"
		) ,
		"EPPPassword" => array(
			"Type" => "password",
			"Size" => "40",
			"Description" => "EPP Password"
		) ,
		"EPPHost" => array(
			"Type" => "text",
			"Size" => "40",
			"Description" => "EPP host",
			"Value" => ""
		) ,
		"EPPPort" => array(
			"Type" => "text",
			"Size" => "40",
			"Description" => "EPP port",
			"Value" => ""
		) ,
		"Login" => array(
			"Type" => "text",
			"Size" => "40",
			"Description" => "nic.md account login",
			"Value" => ""
		) ,
		"Password" => array(
			"Type" => "password",
			"Size" => "40",
			"Description" => "nic.md password",
			"Value" => ""
		) ,
		"write_log" => array(
			'FriendlyName' => 'Write Log',
			"Type" => "yesno",
			"Description" => "Write module logs"
		) ,
		"write_log_info" => array(
			'FriendlyName' => 'Write Log DomainInfo',
			"Type" => "yesno",
			"Description" => "Write module log even for EPP:DomainInfo"
		) ,
		"force_def_contact" => array(
			'FriendlyName' => 'Force default contact',
			"Type" => "yesno",
			"Description" => "Use default contact details for Technical and Billing"
		) ,

		"def_firstname" => array(
			'FriendlyName' => 'Default First Name',
			"Type" => "text",
			"Size" => "40",
			"Description" => "First Name for billing and technical"
		) ,
		"def_lastname" => array(
			'FriendlyName' => 'Default Last Name',
			"Type" => "text",
			"Size" => "40",
			"Description" => "Last Name for billing and technical"
		) ,
		"def_email" => array(
			'FriendlyName' => 'Default Email',
			"Type" => "text",
			"Size" => "40",
			"Description" => "Email for billing and technical"
		) ,
		"def_address1" => array(
			'FriendlyName' => 'Default Address',
			"Type" => "text",
			"Size" => "40",
			"Description" => "Address for billing and technical"
		) ,
		"def_city" => array(
			'FriendlyName' => 'Default City',
			"Type" => "text",
			"Size" => "40",
			"Description" => "City for billing and technical"
		) ,
		"def_state" => array(
			'FriendlyName' => 'Default State',
			"Type" => "text",
			"Size" => "40",
			"Description" => "State for billing and technical"
		) ,
		"def_postcode" => array(
			'FriendlyName' => 'Default Postcode',
			"Type" => "text",
			"Size" => "40",
			"Description" => "Postcode for billing and technical"
		) ,
		"def_country" => array(
			'FriendlyName' => 'Default Country',
			"Type" => "text",
			"Size" => "40",
			"Description" => "Country for billing and technical"
		) ,
		"def_phone" => array(
			'FriendlyName' => 'Default Phone',
			"Type" => "text",
			"Size" => "40",
			"Description" => "Phone for billing and technical"
		) ,
	);
	return $configarray;
}

// ############################################################################################################
function molddata_RegisterDomain($params) {
	if($params['tld'] !== 'md') {
		return array('error' => "Cant register domain with different tld than .md");
	}
	
	$epp = new nicmdEppClient($params['EPPHost'], $params['EPPPort'], $params['Login'], $params['Password']); // last argument debug=1
	$epp->login($params['EPPUser'], $params['EPPPassword']);

	$contacts = array(
		'companyname' => $params['company'],

		'adm_firstname' => $params['firstname'],
		'adm_lastname' => $params['lastname'],
		'adm_str' => $params['address1'],
		'adm_city' => $params['city'],
		'adm_postc' => $params['postcode'],
		'adm_state' => $params['state'],
		'adm_country' => $params['countrycode'],
		'adm_phone' => $params['phonenumber'],
		'adm_email' => $params['email'],

		'teh_firstname' => $params['def_firstname'],
		'teh_lastname' => $params['def_lastname'],
		'teh_str' => $params['def_address1'],
		'teh_city' => $params['def_city'],
		'teh_postc' => $params['def_postcode'],
		'teh_state' => $params['def_state'],
		'teh_country' => $params['def_countrycode'],
		'teh_phone' => $params['def_phonenumber'],
		'teh_email' => $params['def_email'],

		'bil_firstname' => $params['def_firstname'],
		'bil_lastname' => $params['def_lastname'],
		'bil_str' => $params['def_address1'],
		'bil_city' => $params['def_city'],
		'bil_postc' => $params['def_postcode'],
		'bil_state' => $params['def_state'],
		'bil_country' => $params['def_countrycode'],
		'bil_phone' => $params['def_phonenumber'],
		'bil_email' => $params['def_email'],
	);

	$nameservers = array(
		'ns1' => $params['ns1'],
		'ns2' => $params['ns2'],
	);

	$epp_result = $epp->registerDomain($params['sld'], $params['regperiod'], $contacts, $nameservers);
	$epp->logout();

	molddata_DebugLog('register', $params, $epp);
	molddata_writeSqlLog('register', $params['domainid'], $nameservers, $params);
	
	return $epp_result;
}

// ############################################################################################################
function molddata_RenewDomain($params) {
	if($params['tld'] !== 'md') {
		return array('error' => "Cant renew domain with different tld than .md");
	}
	
	$rdata = Capsule::table('tbldomains')->where('id', $params['domainid'])->first();
	if ($rdata) {
		$domainExpDate = $rdata->expirydate;
	}

	$epp = new nicmdEppClient($params['EPPHost'], $params['EPPPort'], $params['Login'], $params['Password']); // last argument debug=1
	$epp->login($params['EPPUser'], $params['EPPPassword']);

	$epp_result = $epp->renewDomain($params['sld'], $params['regperiod'], $domainExpDate);

	$epp->logout();
	
	molddata_DebugLog('renew', $params, $epp);
	logModuleCall('molddata', 'renew-result', $epp_result, '');
	molddata_writeSqlLog('renew', $params['domainid'], '', $params);

	return $epp_result;
}

// ############################################################################################################
function molddata_GetNameservers($params) {
	if($params['tld'] !== 'md') {
		return array('error' => "Cant get domain info for this domain");
	}
	
	$epp = new nicmdEppClient($params['EPPHost'], $params['EPPPort'], $params['Login'], $params['Password']); // last argument debug=1
	$epp->login($params['EPPUser'], $params['EPPPassword']);

	$epp_result = $epp->getNameservers($params['sld'], $params['regperiod']);

	$epp->logout();

	if ($params['write_log_info'] == 'on') molddata_DebugLog('getnameservers', $params, $epp);
	return $epp_result;
}

// ############################################################################################################
function molddata_SaveNameservers($params) {
	if($params['tld'] !== 'md') {
		return array('error' => "Cant manage contactDetails for domain which aren't .md");
	}
	
	$epp = new nicmdEppClient($params['EPPHost'], $params['EPPPort'], $params['Login'], $params['Password']); // last argument debug=1
	$epp->login($params['EPPUser'], $params['EPPPassword']);

	$nameservers = array(
		'ns1' => $params['ns1'],
		'ns2' => $params['ns2'],
	);

	$epp_result = $epp->saveNameservers($params['sld'], $nameservers);

	$epp->logout();

	molddata_DebugLog('savenameservers', $params, $epp);
	molddata_writeSqlLog('savenameservers', $params['domainid'], $nameservers, '');

	return $epp_result;
}

// ############################################################################################################
function molddata_Sync($params) {
	// 	 /**
	//      * Available parameters include the following.
	//      * Any settings defined in your Config Options method will also be available.
	//      */
	//
	//     $params['domainid'];
	//     $params['domain'];
	//     $params['sld'];
	//     $params['tld'];
	//     $params['registrar'];
	//     $params['regperiod'];
	//     $params['status'];
	//     $params['dnsmanagement'];
	//     $params['emailforwarding'];
	//     $params['idprotection'];
	//
	//     // Perform code to fetch domain status here
	//
	//     // Return your result.
	//     // If 'error' is returned, all other values will be ignored. It is important to ensure 'error' is not returned in this array
	//     // unless the sync should not be completed. The error message will be provided in the "Domain Synchronisation Report" email.
	//
	//     return array(
	//         'active' => true, // Return true if the domain is active
	//         'cancelled' => false, // Return true if the domain has been cancelled
	//         'transferredAway' => false, // Return true if the domain has been transferred away from this registrar
	//         'expirydate' => '2018-09-28', // Return the current expiry date for the domain
	//         'error' => 'Error message goes here.' // The error message returned here will be returned within the Domain Synchronisation Report Email
	//     );
	$result = array(
		'active' => true, // Return true if the domain is active
		'cancelled' => false, // Return true if the domain has been cancelled
		'transferredAway' => false, // Return true if the domain has been transferred away from this registrar
		// 		'expirydate' => '2018-09-28', // Return the current expiry date for the domain
		// 		'error' => 'Error message goes here.' // The error message returned here will be returned within the Domain Synchronisation Report Email
		
	);

	$epp = new nicmdEppClient($params['EPPHost'], $params['EPPPort'], $params['Login'], $params['Password']); // last argument debug=1
	$epp->login($params['EPPUser'], $params['EPPPassword']);

	$epp_result = $epp->getContactDetails($params['sld']);

	if (isset($epp_result['error'])) {
		if (preg_match("/domain not exists/", $epp_result['error'])) {
			$result['active'] = false;
		}
		elseif (preg_match("/domain not in account/", $epp_result['error'])) {
			$result['transferredAway'] = true;
		}
		else {
			$result['error'] = $epp_result['error'];
		}
	}
	else {
		$result['expirydate'] = $epp_result['Domain']['Expiration Date'];
		$expiryEpoch = strtotime($result['expirydate']);
		if ($expiryEpoch !== false) {
			if (time() < $expiryEpoch) {
				$result['active'] = true;
			}
			else {
				$result['active'] = false;
			}
		}
	}

	$epp->logout();
	molddata_DebugLog('domainsync', $params, $epp);
	return $result;
}

// ############################################################################################################
function molddata_GetContactDetails($params) {
	if($params['tld'] !== 'md') {
		return array('error' => "Cant manage contactDetails for domain which aren't .md");
	}
	
	$epp = new nicmdEppClient($params['EPPHost'], $params['EPPPort'], $params['Login'], $params['Password']); // last argument debug=1
	$res = $epp->login($params['EPPUser'], $params['EPPPassword']);
	$epp_result = $epp->getContactDetails($params['sld']);
	unset($epp_result['Domain']);
	// 	echo '<textarea>'.$epp->get_dialog().'</textarea>';
	$epp->logout();
	
	molddata_DebugLog('getcontactdetails', $params, $epp);
	return $epp_result;
}

// ############################################################################################################
function molddata_SaveContactDetails($params) {
	if($params['tld'] !== 'md') {
		return array('error' => "Cant manage contactDetails for domain which aren't .md");
	}
	
	$epp = new nicmdEppClient($params['EPPHost'], $params['EPPPort'], $params['Login'], $params['Password']); // last argument debug=1
	$res = $epp->login($params['EPPUser'], $params['EPPPassword']);

	if ($params['force_def_contact'] == 'on') {
		$default_contact = array(
			'Technical' => array(
				'First Name' => $params['def_firstname'],
				'Last Name' => $params['def_lastname'],
				'Email' => $params['def_email'],
				'Address' => $params['def_address1'],
				'City' => $params['def_city'],
				'State' => $params['def_state'],
				'Postcode' => $params['def_postcode'],
				'Country' => $params['def_country'],
				'Phone' => $params['def_phone'],
			) ,
			'Billing' => array(
				'First Name' => $params['def_firstname'],
				'Last Name' => $params['def_lastname'],
				'Email' => $params['def_email'],
				'Address' => $params['def_address1'],
				'City' => $params['def_city'],
				'State' => $params['def_state'],
				'Postcode' => $params['def_postcode'],
				'Country' => $params['def_country'],
				'Phone' => $params['def_phone'],
			) ,
		);
		$params['contactdetails'] = array_merge($params['contactdetails'], $default_contact);
	}

	$epp_result = $epp->saveContactDetails($params['sld'], $params['contactdetails']);
	$epp->logout();

	molddata_DebugLog('savecontactdetails', $params, $epp);
	molddata_writeSqlLog('savecontactdetails', $params['domainid'], '', array_merge($params['contactdetails'], $params['original']));

	if (isset($epp_result['error'])) {
		return $epp_result;
	}
	return 'Successful';
}

function molddata_DebugLog($action, $params, $epp) {
	if ($params['write_log'] !== 'on') {
		return false;
	}
	$result = $epp->get_dialog();

	foreach ($result as $i) {
		logModuleCall('molddata', $action, $i['request'], $i['response']);
	}
}

function molddata_writeSqlLog($action, $domainid, $nameservers = array(), $add = array()) {
	$add = json_encode($add);
	foreach($add as $i => $j) {
		if(preg_match("/^(EPP|Login|Password)/", $i)) {
			unset($add[$i]);
		}
	}
	
	$pdo = Capsule::connection()->getPdo();
	$pdo->beginTransaction();

	try {
		$statement = $pdo->prepare('insert into mod_molddata_domain_log (domainid, action, ns, additional, time) values (:domainid, :action, :ns, :additional, :time)');
		
		$statement->execute([
			':domainid' => $domainid,
			':action' => $action,
			':ns' => implode("\n", $nameservers),
			':additional' => $add,
			':time' => date('U')
		]);
		$pdo->commit();
	}
	catch(\Exception $e) {
		die( "Uh oh! {$e->getMessage() }");
		$pdo->rollBack();
	}
}
