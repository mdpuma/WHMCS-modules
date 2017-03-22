<?php

function bpaybitcoin_config()
{
    $gatewayModuleName = basename(__FILE__, '.php');
    $configarray = array(
        "FriendlyName" => array(
            "Type" => "System",
            "Value" => $gatewayModuleName
        ),
        "merchantid" => array(
            "FriendlyName" => "Merchant ID",
            "Type" => "text",
            "Size" => "20"
        ),
        "signature" => array(
            "FriendlyName" => "Signature",
            "Type" => "text",
            "Size" => "20"
        ),
        "success_url" => array(
            "FriendlyName" => "Success URL",
            "Type" => "text",
            "Size" => "50"
        ),
        "fail_url" => array(
            "FriendlyName" => "Fail URL",
            "Type" => "text",
            "Size" => "50"
        ),
        "button_name" => array(
            "FriendlyName" => "Button Name",
            "Type" => "text",
            "Size" => "20"
        ),
        "testmode" => array(
            "FriendlyName" => "Test Mode",
            "Type" => "dropdown",
            "Options" => "1,0",
            "Description" => "Select (1) to test"
        ),
        "bpaymethod" => array(
            "FriendlyName" => "Bpay method",
            "Type" => "dropdown",
            "Options" => "bpay,card,card_eur,webmoneycat,wmrcat,bitcoin",
            "Description" => "card - card mdl, card_eur - card euro, webmoneycat - webmoney wmz, wmrcat - webmoney wmr"
        ),
        "localapi_user" => array(
            "FriendlyName" => "Username for LocalAPI",
            "Type" => "text",
            "Size" => "50",
            "Description" => "Read more here https://developers.whmcs.com/api/internal-api/"
        )
    );
    return $configarray;
}

function bpaybitcoin_link($params)
{
    
    # Gateway Specific Variables
    $gatewaymerchantid = $params['merchantid'];
    $gatewaytestmode   = $params['testmode'];
    
    # Invoice Variables
    $invoiceid   = $params['invoiceid'];
    $description = $params["description"];
    $amount      = $params['amount']; # Format: ##.##
    $currency    = $params['currency']; # Currency Code
    
    # Client Variables
    $firstname = $params['clientdetails']['firstname'];
    $lastname  = $params['clientdetails']['lastname'];
    $email     = $params['clientdetails']['email'];
    $address1  = $params['clientdetails']['address1'];
    $address2  = $params['clientdetails']['address2'];
    $city      = $params['clientdetails']['city'];
    $state     = $params['clientdetails']['state'];
    $postcode  = $params['clientdetails']['postcode'];
    $country   = $params['clientdetails']['country'];
    $phone     = $params['clientdetails']['phonenumber'];
    
    # System Variables
    $companyname = $params['companyname'];
    $systemurl   = $params['systemurl'];
    $currency    = $params['currency'];
    
    # Enter your code submit to the gateway...
    
    $signature = $params['signature'];
    $xmldata   = "<payment>
                <type>1.2</type>
                <merchantid>" . $gatewaymerchantid . "</merchantid>
                <amount>" . $amount . "</amount>
                <description>" . $description . "</description>
                <method>" . $params['bpaymethod'] . "</method>
                <order_id>" . $invoiceid . "</order_id>
                <success_url>" . htmlspecialchars($params['success_url']) . "</success_url>
                <fail_url>" . htmlspecialchars($params['fail_url']) . "</fail_url>
                <callback_url>" . htmlspecialchars($systemurl . '/modules/gateways/callback/bpaybitcoin.php') . "</callback_url>
                <lang>en</lang>
                <advanced1>" . $email . "</advanced1>
                <advanced2>" . $phone . "</advanced2>
                <istest>" . $gatewaytestmode . "</istest>
              </payment>";
    
    $data      = base64_encode($xmldata);
    $sign      = md5(md5($xmldata) . md5($signature));
    
    $code = '<form method="POST" action="https://www.bpay.md/user-api/payment1">
<input type="hidden" name="data" value="' . $data . '" />
<input type="hidden" name="key" value="' . $sign . '" />
<input type="submit" value="' . $params['button_name'] . '" />
</form>';
    
    return $code;
}

/*

function bpaybitcoin_capture($params) {

# Gateway Specific Variables
$gatewayusername = $params['username'];
$gatewaytestmode = $params['testmode'];

# Invoice Variables
$invoiceid = $params['invoiceid'];
$amount = $params['amount']; # Format: ##.##
$currency = $params['currency']; # Currency Code

# Client Variables
$firstname = $params['clientdetails']['firstname'];
$lastname = $params['clientdetails']['lastname'];
$email = $params['clientdetails']['email'];
$address1 = $params['clientdetails']['address1'];
$address2 = $params['clientdetails']['address2'];
$city = $params['clientdetails']['city'];
$state = $params['clientdetails']['state'];
$postcode = $params['clientdetails']['postcode'];
$country = $params['clientdetails']['country'];
$phone = $params['clientdetails']['phonenumber'];

# Card Details
$cardtype = $params['cardtype'];
$cardnumber = $params['cardnum'];
$cardexpiry = $params['cardexp']; # Format: MMYY
$cardstart = $params['cardstart']; # Format: MMYY
$cardissuenum = $params['cardissuenum'];

# Perform Transaction Here & Generate $results Array, eg:
$results = array();
$results["status"] = "success";
$results["transid"] = "12345";

# Return Results
if ($results["status"]=="success") {
return array("status"=>"success","transid"=>$results["transid"],"rawdata"=>$results);
} elseif ($gatewayresult=="declined") {
return array("status"=>"declined","rawdata"=>$results);
} else {
return array("status"=>"error","rawdata"=>$results);
}

}
*/

/*
function bpaybitcoin_refund($params) {

# Gateway Specific Variables
$gatewayusername = $params['username'];
$gatewaytestmode = $params['testmode'];

# Invoice Variables
$transid = $params['transid']; # Transaction ID of Original Payment
$amount = $params['amount']; # Format: ##.##
$currency = $params['currency']; # Currency Code

# Client Variables
$firstname = $params['clientdetails']['firstname'];
$lastname = $params['clientdetails']['lastname'];
$email = $params['clientdetails']['email'];
$address1 = $params['clientdetails']['address1'];
$address2 = $params['clientdetails']['address2'];
$city = $params['clientdetails']['city'];
$state = $params['clientdetails']['state'];
$postcode = $params['clientdetails']['postcode'];
$country = $params['clientdetails']['country'];
$phone = $params['clientdetails']['phonenumber'];

# Card Details
$cardtype = $params['cardtype'];
$cardnumber = $params['cardnum'];
$cardexpiry = $params['cardexp']; # Format: MMYY
$cardstart = $params['cardstart']; # Format: MMYY
$cardissuenum = $params['cardissuenum'];

# Perform Refund Here & Generate $results Array, eg:
$results = array();
$results["status"] = "success";
$results["transid"] = "12345";

# Return Results
if ($results["status"]=="success") {
return array("status"=>"success","transid"=>$results["transid"],"rawdata"=>$results);
} elseif ($gatewayresult=="declined") {
return array("status"=>"declined","rawdata"=>$results);
} else {
return array("status"=>"error","rawdata"=>$results);
}

}
*/

?>
