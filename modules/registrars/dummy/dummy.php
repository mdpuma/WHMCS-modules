<?php
use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use Illuminate\Database\Capsule\Manager as Capsule;

function dummy_getConfigArray() {
	$configarray = array(
		"FriendlyName" => array(
			"Type" => "System",
			"Value" => "Dummy test registrar"
		) ,
		"write_log" => array(
			'FriendlyName' => 'Write Log',
			"Type" => "yesno",
			"Description" => "Write module logs"
		)
	);
	return $configarray;
}

// ############################################################################################################
function dummy_RegisterDomain($params) {
	dummy_DebugLog('register', $params);
	return 'Successful';
}

// ############################################################################################################
function dummy_RenewDomain($params) {
	dummy_DebugLog('renew', $params);
	return 'Successful';
}

// ############################################################################################################
function dummy_GetNameservers($params) {
	dummy_DebugLog('getnameservers', $params);
	return 'Successful';
}

// ############################################################################################################
function dummy_SaveNameservers($params) {
	dummy_DebugLog('savenameservers', $params);
	return 'Successful';
}

// ############################################################################################################

function dummy_DebugLog($action, $params) {
	if ($params['write_log'] !== 'on') {
		return false;
	}
	logModuleCall('dummy', $action, $params, '');
}
