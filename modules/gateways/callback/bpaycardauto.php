<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');
// Fetch gateway configuration parameters.
$gatewayParams	 = getGatewayVariables($gatewayModuleName);
// Die if module is not active.
if (!$gatewayParams['type']) {
	die("Module Not Activated");
}

$signatures = ['signature_mdl','signature_eur','signature_usd'];
$right_signature = $gatewayParams['signature_mdl'];

$data = $_POST['data']; 
$key = $_POST['key']; 

foreach($signatures as $signature) {
	$xmldata = base64_decode($data);
	$vrfsign = md5(md5($xmldata) . md5($gatewayParams[$signature]));
	if ($key == $vrfsign) {
		$right_signature = $gatewayParams[$signature];
		break;
	}
}

$xmldata = base64_decode($data);
$vrfsign = md5(md5($xmldata) . md5($right_signature));

if ($key == $vrfsign) {
	$xml = simplexml_load_string($xmldata);
	
	$invoiceid 	= $xml->order_id;
	$transid   	= $xml->transid;
	$amount		= $xml->amount;
	$payed		= (float) $amount;
	
	$array_data = json_decode(json_encode($xml), true);
	
	if ((string) $xml->comand == "check") {
		// проверка существования указанного order_id
		// 100 - номер существует, 50 - номер не существует
		
		$invoice_data = localAPI('GetInvoice', array(
			'invoiceid' => $invoiceid
		), $gatewayParams['localapi_user']);
		
		if (!isset($invoice_data['result']) || $invoice_data['result'] !== 'success') {
			do_xml_result('invoice not exists', 50, $array_data, $gatewayParams['name']);
		} else {
			do_xml_result('success', 100, $array_data, $gatewayParams['name']);
		}
	} elseif ((string) $xml->comand == "pay") {
		$invoice_data = localAPI('GetInvoice', array(
			'invoiceid' => $invoiceid
		), $gatewayParams['localapi_user']);
		
		if (!isset($invoice_data['result']) || $invoice_data['result'] !== 'success')
			do_xml_result('invoice not exists', 30, $array_data, $gatewayParams['name']);
		
		if (!isset($invoice_data['status']) || $invoice_data['status'] !== 'Unpaid')
			do_xml_result('Invoice is already paid', 30, $array_data, $gatewayParams['name']);
		
		$result = localAPI('GetTransactions', array(
			'transid' => $transid
		), $gatewayParams['localapi_user']);
		
		if ($result['totalresults'] > 0)
			do_xml_result('Transaction id is already exists', 30, $array_data, $gatewayParams['name']);
		
		$payed = $invoice_data['total'];
		$fees  = $payed * 0.04;
		
		// здесь осуществляем обработку данного платежа
		localAPI('addInvoicePayment', array(
			'invoiceid' => (int) $invoiceid,
			'transid' => $transid,
			'payed' => $payed,
			'fees' => $fees,
			'gateway' => $gatewayParams['paymentmethod']
		), $gatewayParams['localapi_user']);
		
		$arr = array(
			"module" => $gatewayParams['paymentmethod'],
			"payed" => $payed,
			"fees" => $fees
		);
		$array_data = array_merge($array_data, $arr);
		
		do_xml_result('success', 100, $array_data, $gatewayParams['name']);
	} else {
		do_xml_result('unknown method', 30, $array_data, $gatewayParams['name']);
	}
} else {
	$array_data = array(
		'key' => $key,
		'data' => $data,
		'xmldata' => $xmldata
	);
	$array_data = json_encode($array_data);
	do_xml_result('incorrect signature', 30, $array_data, $gatewayParams['name']);
}

function do_xml_result($reason, $code='50', $array_data, $gatewayname)
{
	if ($code < '100') {
		$reason = 'Unsuccessful, '.$reason;
	}
	logTransaction($gatewayname, $array_data, $reason); # Save to Gateway Log: name, data array, status
	echo '<?xml version=\'1.0\' encoding="utf8"?>';
	echo '<result>';
	echo '<code>'.$code.'</code>';
	echo '<text>'.$reason.'</text>';
	echo '</result>';
	die();
}

?>
