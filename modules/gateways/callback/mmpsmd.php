<?php
include_once ("../../../init.php");

include_once ("../../../includes/gatewayfunctions.php");

include_once ("../../../includes/invoicefunctions.php");

$gatewaymodule = "mmpsmd";
$GATEWAY = getGatewayVariables($gatewaymodule);

if (!$GATEWAY["type"]) die("Module Not Activated");
$ips = array_values(explode("\r\n", $GATEWAY['ips']));
$remote_ip = trim($_SERVER['REMOTE_ADDR']);
$invoiceid = (int)$_REQUEST['account'];
$transid = $_REQUEST['txn_id'];
$amount = $_REQUEST['sum']; // amount in MDL from provider
$command = $_REQUEST['command'];

if (!in_array($remote_ip, $ips)) {
	echo '<?xml version="1.0" encoding="UTF-8"?><response><osmp_txn_id>' . $transid . '</osmp_txn_id><result>001</result><comment>Access denied</comment></response>';
	exit();
}

// get invoice data
$postData = array(
	'invoiceid' => $invoiceid,
);
$invoice_data = localAPI('GetInvoice', $postData);

if ($invoice_data['result'] !== 'success') {
	echo '<?xml version="1.0" encoding="UTF-8"?><response><osmp_txn_id>' . $transid . '</osmp_txn_id><result>004</result><comment>Invoice ID not found</comment></response>';
	exit();
}

if (!$transid) {
	echo '<?xml version="1.0" encoding="UTF-8"?><response><osmp_txn_id>' . $transid . '</osmp_txn_id><result>003</result><comment>Transaction not set</comment></response>';
	exit();
}

if ($invoice_data['status'] == 'Paid') {
	logTransaction($GATEWAY["name"], $_REQUEST, "Invoice is already paid");
	echo '<?xml version="1.0" encoding="UTF-8"?><response><osmp_txn_id>' . $transid . '</osmp_txn_id><result>333</result><comment>Invoice is already paid</comment></response>';
	exit();
}

// get client details
$postData = array(
	'clientid' => $invoice_data['userid'],
	'stats' => false,
);
$client_data = localAPI('GetClientsDetails', $postData);

//get transactions
$postData = array(
    'invoiceid' => $invoiceid,
);
$transactions_data = localAPI('GetTransactions', $postData);

// check if transactions exists
foreach($transactions_data['transactions'] as $i) {
	if($i['transid'] == $transid) {
		logTransaction($GATEWAY["name"], $_REQUEST, "Transaction exists");
		echo '<?xml version="1.0" encoding="UTF-8"?><response><osmp_txn_id>' . $transid . '</osmp_txn_id><result>333</result><comment>Transaction already exists</comment></response>';
		exit();
	}
}

switch($command) {
	case 'check': {
		if ($GATEWAY['convertto']) {
			$total_converted = convertCurrency($invoice_data['balance'], $client_data['client']['currency'], $GATEWAY['convertto']);
		} else {
			$total_converted = $invoice_data['balance'];
		}
		
		logTransaction($GATEWAY["name"], $_REQUEST, "Transaction check");
		echo '<?xml version="1.0" encoding="UTF-8"?><response><osmp_txn_id>' . $transid . '</osmp_txn_id><sum>' . $total_converted . '</sum><result>0</result><comment>OK</comment><fields><field name="Name">' . $client_data['client']['fullname'] . '</field></fields></response>';
		exit();
		break;
	}
	case 'pay': {
		// get currencies
		$currencies_data = localAPI('GetCurrencies', array());
		foreach($currencies_data['currencies']['currency'] as $i) {
			if($i['code'] == 'MDL') $required_currency_id = $i['id'];
		}
		
		// required amount to pay for invoice
		if($client_data['client']['currency'] !== $required_currency_id) {
			$converted_amount = convertCurrency($amount, $GATEWAY['convertto'], $client_data['client']['currency']);
		} else {
			$converted_amount = $amount;
		}
		
		// ??????
		if ($invoice_data['balance'] < $converted_amount + 1 && $converted_amount - 1 < $invoice_data['balance']) {
			$converted_amount = $invoice_data['balance'];
		}
		
		if ($converted_amount == 0) {
			logTransaction($GATEWAY["name"], $_REQUEST, "Zero Payment");
			echo '<?xml version="1.0" encoding="UTF-8"?><response><osmp_txn_id>' . $transid . '</osmp_txn_id><result>007</result><comment>Zero payment</comment></response>';
			exit();
		};
		
		$command = 'AddInvoicePayment';
		$postData = array(
			'invoiceid' => $invoiceid,
			'transid' => $transid,
			'gateway' => $gatewaymodule,
			'amount' => $converted_amount,
		);
		$results = localAPI('AddInvoicePayment', $postData);
		
		logTransaction($GATEWAY["name"], $_REQUEST, "Successful");
		echo '<?xml version="1.0" encoding="UTF-8"?><response><osmp_txn_id>' . $transid . '</osmp_txn_id><prv_txn>'.$invoiceid.'-' . time() . '</prv_txn><sum>' . $amount . '</sum><result>000</result></response>';
		exit;
		break;
	}
	default: {
		echo '<?xml version="1.0" encoding="UTF-8"?><response><osmp_txn_id>' . $transid . '</osmp_txn_id><result>300</result><comment>Unknown action</comment></response>';
		break;
	}
	
}
