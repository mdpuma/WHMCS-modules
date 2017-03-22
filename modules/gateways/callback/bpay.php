<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');
// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);
// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

$data = $_POST['data'];
$key = $_POST['key'];
$xmldata = base64_decode($data);
$vrfsign = md5(md5($xmldata) . md5($gatewayParams['signature']));

if ($key == $vrfsign)
{
    $xml = simplexml_load_string ($xmldata);
    $invoiceid = $xml->order_id;
    $transid = $xml->transid;
    $amount = $xml->amount;
    $payed = (float)$amount;
    
    $array_data = json_decode(json_encode($xml), true);

    if ((string)$xml->comand == "check")
    {
        // проверка существования указанного order_id
        // 100 - номер существует, 50 - номер не существует
        logTransaction($gatewayParams['name'], $array_data, "Unsuccessful, order not exist"); # Save to Gateway Log: name, data array, status
        echo "<?xml version='1.0' encoding=\"utf8\"?>";
        echo "<result>";
        echo "<code>50</code>";
        echo "<text>not exist</text>";
        echo "</result>"; 
        fail_transaction('invoice not exists');

    }
    elseif ((string)$xml->comand=="pay")
    {
        //$invoiceid = checkCbInvoiceID($invoiceid1,$gatewayParams['name']); # Checks invoice ID is a valid invoice number or ends processing

        //checkCbTransID($transid); # Checks transaction number isn't already in the database and ends processing if it does
       
	$invoice_data = localAPI('GetInvoice', array('invoiceid' => $invoiceid), $gatewayParams['localapi_user']);
	if(!isset($invoice_data['result']) || $invoice_data['result']!=='success')
		fail_transaction('invoice not exists');
	
	if(!isset($invoice_data['status']) || $invoice_data['status']!=='Unpaid')
		fail_transaction('Invoice is already paid');
	
	$result = localAPI('GetTransactions', array('transid' => $transid), $gatewayParams['localapi_user']);
	if($result['totalresults'] > 0)
		fail_transaction('Transaction id is already exists');
	
        $payed = $invoice_data['total'];
        $fees = $payed*0.04;

        // здесь осуществляем обработку данного платежа
        localAPI('addInvoicePayment', array(
	  'invoiceid' => (int) $invoiceid,
	  'transid' => (int) $transid,
	  'payed' => $payed,
	  'fees' => $fees,
	  'gateway' => $gatewayParams['paymentmethod']
	), $gatewayParams['localapi_user']);
	
	$arr = array("module" => $gatewayParams['paymentmethod'], "payed"=>$payed, "fees" =>$fees);
	$array_data = array_merge($array_data, $arr);
        logTransaction($gatewayParams['name'], $array_data, "Successful"); # Save to Gateway Log: name, data array, status
	    echo "<?xml version='1.0' encoding=\"utf8\"?>";
        echo "<result>";
        echo "<code>100</code>";
        echo "<text>success</text>";
        echo "</result>"; 
    }
    else
    {
        logTransaction($gatewayParams['name'], $array_data, "Unsuccessful, unknown method"); # Save to Gateway Log: name, data array, status
        echo "<?xml version='1.0' encoding=\"utf8\"?>";
        echo "<result>";
        echo "<code>30</code>";
        echo "<text>unknown method</text>";
        echo "</result>"; 
    }
}
else
{
    logTransaction($gatewayParams['name'], $array_data, "Incorect signature"); # Save to Gateway Log: name, data array, status
    echo "<?xml version='1.0' encoding=\"utf8\"?>";
    echo "<result>";
    echo "<code>30</code>";
    echo "<text>incorrect signature</text>";
    echo "</result>"; 
}

function fail_transaction($reason) {
	global $gatewayParams, $array_data;
	logTransaction($gatewayParams['name'], $array_data, "Unsuccessful, $reason"); # Save to Gateway Log: name, data array, status
	echo "<?xml version='1.0' encoding=\"utf8\"?>";
	echo "<result>";
	echo "<code>50</code>";
	echo "<text>$reason</text>";
	echo "</result>";
	die();
}

?>
