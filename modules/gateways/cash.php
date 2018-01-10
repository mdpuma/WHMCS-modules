<?php

function cash_config()
{
    $gatewayModuleName = basename(__FILE__, '.php');
    $configarray = array(
        "FriendlyName" => array(
            "Type" => "System",
            "Value" => $gatewayModuleName
        )
    );
    return $configarray;
}

function cash_link($params)
{

    return '';
}

/*

function cash_capture($params) {

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
function cash_refund($params) {

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
