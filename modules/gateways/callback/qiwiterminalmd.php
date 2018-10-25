<?php
include_once ("../../../init.php");

include_once ("../../../includes/gatewayfunctions.php");

include_once ("../../../includes/invoicefunctions.php");

$gatewaymodule = "qiwiterminalmd";
$GATEWAY = getGatewayVariables($gatewaymodule);

if (!$GATEWAY["type"]) die("Module Not Activated");
$ips = array_values(explode("\r\n", $GATEWAY['ips']));
$remote_ip = trim($_SERVER['REMOTE_ADDR']);
$invoiceid = $_REQUEST['account'];
$transid = $_REQUEST['txn_id'];
$amount = $_REQUEST['sum'];
$command = $_REQUEST['command'];

if (!in_array($remote_ip, $ips)) {
	echo '<?xml version="1.0" encoding="UTF-8"?><response><osmp_txn_id>' . $transid . '</osmp_txn_id><result>1</result><comment>Access denied</comment></response>';
	exit();
}

$result = select_query("tblinvoices", "id", array(
	"id" => $invoiceid
));
$data = mysql_fetch_array($result);
$id = $data["id"];

if (!$id) {
	echo '<?xml version="1.0" encoding="UTF-8"?><response><osmp_txn_id>' . $transid . '</osmp_txn_id><result>5</result><comment>Invoice ID not found</comment></response>';
	exit();
}

if (!$transid) {
	echo '<?xml version="1.0" encoding="UTF-8"?><response><osmp_txn_id>' . $transid . '</osmp_txn_id><result>7</result><comment>Transaction not set</comment></response>';
	exit();
}

$result = select_query("tblinvoices", "userid,total", array(
	"id" => $invoiceid
));
$data = mysql_fetch_array($result);
$result = select_query('tblinvoices', "tblinvoices.*,(SELECT value FROM tblpaymentgateways WHERE gateway=tblinvoices.paymentmethod AND setting='name' LIMIT 1) AS gateway,IFNULL((SELECT SUM(amountin-amountout) FROM tblaccounts WHERE invoiceid=tblinvoices.id),0) as amountpaid", array(
	'id' => $invoiceid
));
$data = mysql_fetch_assoc($result);
$userid = $data['userid'];
$total = $data['total'] - $data['amountpaid'];
$currency = getCurrency($userid);
$total_converted = $total;
$userdata = get_query_vals("tblclients", "email,firstname,lastname", array(
	"id" => $userid
));

if ($GATEWAY['convertto']) {
	$amount = convertCurrency($amount, $GATEWAY['convertto'], $currency['id']);
	$total_converted = convertCurrency($total, $currency['id'], $GATEWAY['convertto']);
}

$result = select_query("tblaccounts", "id", array(
	"transid" => $transid
));
$num_rows = mysql_num_rows($result);

if ($num_rows) {
	if ($command == 'check') {
		logTransaction($GATEWAY["name"], $_REQUEST['params'], "Transaction exists");
		echo '<?xml version="1.0" encoding="UTF-8"?><response><osmp_txn_id>' . $transid . '</osmp_txn_id><result>7</result><comment>Transaction already exists</comment></response>';
		exit();
	}
	else {
		logTransaction($GATEWAY["name"], $_REQUEST['params'], "Transaction exists");
		echo '<?xml version="1.0" encoding="UTF-8"?><response><osmp_txn_id>' . $transid . '</osmp_txn_id><prv_txn>' . time() . '</prv_txn><sum>' . $amount . '</sum><result>0</result></response>';
		exit();
	}
}

if ($command == 'check') {
	logTransaction($GATEWAY["name"], $_REQUEST['params'], "Transaction check");
	echo '<?xml version="1.0" encoding="UTF-8"?><response><osmp_txn_id>' . $transid . '</osmp_txn_id><result>0</result><fields><field1 name="Amount">' . $total_converted . '</field1><field2 name="You pay">' . $sum . '</field2><field3 name="User">' . $userdata['firstname'] . ' ' . $userdata['lastname'] . '</field3><field4 name="Email">' . $userdata['email'] . '</field4></fields></response>';
	exit();
}
elseif ($command == 'pay') {
	if ($total < $amount + 1 && $amount - 1 < $total) {
		$amount = $total;
	};
	if ($amount == 0) {
		logTransaction($GATEWAY["name"], $POST, "Zero Payment");
		echo '<?xml version="1.0" encoding="UTF-8"?><response><osmp_txn_id>' . $transid . '</osmp_txn_id><result>7</result><comment>Zero payment</comment></response>';
		exit();
	};
	addInvoicePayment($invoiceid, $transid, $amount, 0, $gatewaymodule);
	logTransaction($GATEWAY["name"], $_REQUEST['params'], "Successful");
	echo '<?xml version="1.0" encoding="UTF-8"?><response><osmp_txn_id>' . $transid . '</osmp_txn_id><prv_txn>' . time() . '</prv_txn><sum>' . $amount . '</sum><result>0</result></response>';
	exit;
}

echo '<?xml version="1.0" encoding="UTF-8"?><response><osmp_txn_id>' . $transid . '</osmp_txn_id><result>7</result><comment>Unknown action</comment></response>';
