<?php

function bpaycardauto_config()
{
    $gatewayModuleName = 'Bpay.md credit card (AUTO)';
    $configarray = array(
        "FriendlyName" => array(
            "Type" => "System",
            "Value" => $gatewayModuleName
        ),
        "merchantid_mdl" => array(
            "FriendlyName" => "MDL Merchant ID",
            "Type" => "text",
            "Size" => "20"
        ),
        "signature_mdl" => array(
            "FriendlyName" => "MDL Signature",
            "Type" => "text",
            "Size" => "20"
        ),
        "merchantid_eur" => array(
            "FriendlyName" => "EUR Merchant ID",
            "Type" => "text",
            "Size" => "20"
        ),
        "signature_eur" => array(
            "FriendlyName" => "EUR Signature",
            "Type" => "text",
            "Size" => "20"
        ),
        "merchantid_usd" => array(
            "FriendlyName" => "USD Merchant ID",
            "Type" => "text",
            "Size" => "20"
        ),
        "signature_usd" => array(
            "FriendlyName" => "USD Signature",
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
        "localapi_user" => array(
            "FriendlyName" => "Username for LocalAPI",
            "Type" => "text",
            "Size" => "50",
            "Description" => "Read more here https://developers.whmcs.com/api/internal-api/"
        )
    );
    return $configarray;
}

function bpaycardauto_link($params)
{
    
    # Gateway Specific Variables
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
    
    # Enter your code submit to the gateway...
    // bpaymethod:
    // card - card mdl, card_eur - card euro, webmoneycat - webmoney wmz, wmrcat - webmoney wmr
    switch($currency) {
		case 'MDL': {
			$signature = $params['signature_mdl'];
			$gatewaymerchantid = $params['merchantid_mdl'];
			$bpaymethod = 'card'; // or card_omd
			break;
		}
		case 'EUR': {
			$signature = $params['signature_eur'];
			$gatewaymerchantid = $params['merchantid_eur'];
			$bpaymethod = 'card_eur';
			break;
		}
		default: {
			$signature = $params['signature_usd'];
			$gatewaymerchantid = $params['merchantid_usd'];
			$bpaymethod = 'card_usd';
			break;
		}
    }
    $xmldata   = "<payment>
                <type>1.2</type>
                <merchantid>" . $gatewaymerchantid . "</merchantid>
                <amount>" . $amount . "</amount>
                <description>" . $description . "</description>
                <method>" . $bpaymethod . "</method>
                <order_id>" . $invoiceid . "</order_id>
                <success_url>" . htmlspecialchars($params['success_url']) . "</success_url>
                <fail_url>" . htmlspecialchars($params['fail_url']) . "</fail_url>
                <callback_url>" . htmlspecialchars($systemurl . '/modules/gateways/callback/bpaycardauto.php') . "</callback_url>
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

?>