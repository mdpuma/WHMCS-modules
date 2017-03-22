<?php

# Required File Includes
include("../../../dbconnect.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

$gatewaymodule = "bpay"; # Enter your gateway module name here replacing template

$GATEWAY = getGatewayVariables($gatewaymodule);
if (!$GATEWAY["type"]) die("Module Not Activated"); # Checks gateway module is active before accepting callback

# Get Returned Variables - Adjust for Post Variable Names from your Gateway's Documentation
//$status = $_POST["x_response_code"];
//$invoiceid = $_POST["x_invoice_num"];
//$transid = $_POST["x_trans_id"];
//$amount = $_POST["x_amount"];
//$fee = $_POST["x_fee"];
/*
$invoiceid = checkCbInvoiceID($invoiceid,$GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing

checkCbTransID($transid); # Checks transaction number isn't already in the database and ends processing if it does

if ($status=="1") {
    # Successful
    addInvoicePayment($invoiceid,$transid,$amount,$fee,$gatewaymodule); # Apply Payment to Invoice: invoiceid, transactionid, amount paid, fees, modulename
	logTransaction($GATEWAY["name"],$_POST,"Successful"); # Save to Gateway Log: name, data array, status
} else {
	# Unsuccessful
    logTransaction($GATEWAY["name"],$_POST,"Unsuccessful"); # Save to Gateway Log: name, data array, status
}
*/
$signature = "xK2qT14h5"; // строка, для подписи, указанная при регистрации мерчанта
$data = $_POST['data'];
$key = $_POST['key'];
$xmldata = base64_decode($data);
$vrfsign = md5(md5($xmldata) . md5($signature));
if ($key == $vrfsign)
{

    $xml = simplexml_load_string ($xmldata);
    $invoiceid1 = $xml->order_id;
    $transid = $xml->transid;
    $amount = $xml->amount;
    $payed = (float)$amount;
    
    $arr_data = json_decode(json_encode($xml), true);

    if ((string)$xml->comand == "check")
    {
        // проверка существования указанного order_id
        // 100 - номер существует, 50 - номер не существует
        logTransaction($GATEWAY["name"],$arr_data ,"Unsuccessful, order not exist"); # Save to Gateway Log: name, data array, status
        echo "<?xml version='1.0' encoding=\"utf8\"?>";
        echo "<result>";
        echo "<code>50</code>";
        echo "<text>not exist</text>";
        echo "</result>"; 

    }
    elseif ((string)$xml->comand=="pay")
    {
        $invoiceid = checkCbInvoiceID($invoiceid1,$GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing

        checkCbTransID($transid); # Checks transaction number isn't already in the database and ends processing if it does

        // здесь осуществляем обработку данного платежа
        addInvoicePayment((int)$invoiceid, (int)$arr_data['transid'], $payed, 0, $gatewaymodule); # Apply Payment to Invoice: invoiceid, transactionid, amount paid, fees, modulename
        $result = mysql_query("UPDATE `tblinvoices` SET `datepaid`=NOW(), `status`='Paid' WHERE `id`=".$invoiceid);
	    $arr = array("invoiceid1" => $invoiceid1,"invoiceid2" => $invoiceid, "transid1" => $transid, "transid2" => $arr_data['transid'], "module" => $gatewaymodule, "amount1"=>$amount, "payed"=>$payed, "addi" =>$add, "resultUpdate" => $result);
	    $arr_data = array_merge($arr_data, $arr);
        logTransaction($GATEWAY["name"],$arr_data, "Successful"); # Save to Gateway Log: name, data array, status
	    echo "<?xml version='1.0' encoding=\"utf8\"?>";
        echo "<result>";
        echo "<code>100</code>";
        echo "<text>success</text>";
        echo "</result>"; 
    }
    else
    {
        logTransaction($GATEWAY["name"],$arr_data ,"Unsuccessful, unknown method"); # Save to Gateway Log: name, data array, status
        echo "<?xml version='1.0' encoding=\"utf8\"?>";
        echo "<result>";
        echo "<code>30</code>";
        echo "<text>unknown method</text>";
        echo "</result>"; 
    }
}
else
{
    logTransaction($GATEWAY["name"],$arr_data ,"Incorect signature"); # Save to Gateway Log: name, data array, status
    echo "<?xml version='1.0' encoding=\"utf8\"?>";
    echo "<result>";
    echo "<code>30</code>";
    echo "<text>incorrect signature</text>";
    echo "</result>"; 
}

?>
