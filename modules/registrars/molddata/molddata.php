<?php

use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use Illuminate\Database\Capsule\Manager as Capsule;

function molddata_getConfigArray() {
 $configarray = array(
  "FriendlyName" => array("Type" => "System", "Value" => "MoldData"),
  "EPPUser" => array( "Type" => "text", "Size" => "40", "Description" => "EPP Username"),
  "EPPPassword" => array( "Type" => "password", "Size" => "40", "Description" => "EPP Password"),
  "EPPHost" => array(  "Type" => "text", "Size" => "40", "Description" => "EPP host", "Value" =>""),
  "EPPPort" => array(  "Type" => "text", "Size" => "40", "Description" => "EPP port", "Value" =>""),
  "Login" => array(  "Type" => "text", "Size" => "40", "Description" => "nic.md account login", "Value" =>""),
  "Password" => array(  "Type" => "password", "Size" => "40", "Description" => "nic.md password", "Value" =>""),
  
  "def_firstname" => array( "Type" => "text", "Size" => "40", "Description" => "First Name for billing and technical"),
  "def_lastname" => array( "Type" => "text", "Size" => "40", "Description" => "Last Name for billing and technical"),
  "def_email" => array( "Type" => "text", "Size" => "40", "Description" => "Email for billing and technical"),
  "def_address1" => array( "Type" => "text", "Size" => "40", "Description" => "Address for billing and technical"),
  "def_city" => array( "Type" => "text", "Size" => "40", "Description" => "City for billing and technical"),
  "def_state" => array( "Type" => "text", "Size" => "40", "Description" => "State for billing and technical"),
  "def_postcode" => array( "Type" => "text", "Size" => "40", "Description" => "Postcode for billing and technical"),
  "def_country" => array( "Type" => "text", "Size" => "40", "Description" => "Country for billing and technical"),
  "def_phone" => array( "Type" => "text", "Size" => "40", "Description" => "Phone for billing and technical"),
  );
 return $configarray;
}

// ############################################################################################################

function molddata_login($params) {
    
    if (!$fp = stream_socket_client('tls://'.$params['EPPHost'].':'.$params['EPPPort'], $errno, $errstr, 60, STREAM_CLIENT_CONNECT, stream_context_create( array( 'ssl' => array( 'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true ) ) )) ) {
        logModuleCall( "MoldData", "connect", "ssl://".$params['EPPHost'].":".$params['EPPPort'], "can't connect", "can't connect" ); return  0; 
    }
    
    $i = 0;
    while (!feof($fp)) {
        $i++;
        $result .= fgetc($fp);
        if (strpos($result,'</epp>')) { break; }
        if ($i > 500000) { break; }
    }
    if (strpos ($result,'</epp>')) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
                <epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
                     xmlns:xsi="http://www.w3.org/2001/XMLSchemainstance"
                     xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
                <command>
                    <login>
                        <clID>'.$params['EPPUser'].'</clID>
                        <pw>'.$params['EPPPassword'].'</pw>
                        <options/>
                        <svcs/>
                    </login>
                    <clTRID/>
                </command>
                </epp>';
        $result = molddata_eppSendCommand ($fp, $xml);
        if (!strstr ($result, '<result code="1000">')) {
            $epp_result['result'] = 0;
            if (preg_match("/<msg>(.+)<\/msg>/",$result,$msg)) {
                $epp_result['error'] = 'Error: '.$msg[0];
            } else {
                $epp_result['error'] = 'Error!';
            }
            return $epp_result;
        } else {
            return $fp;
        }
    } else {
        $epp_result['result'] = 0;
        $epp_result['error'] = 'Error!';
        return $epp_result;
    }
}

// ############################################################################################################

function molddata_logout ($fp,$params) {
    $clTrid = rand(0,9999);
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
            <epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
                 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                 xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp1.0.xsd">
            <command>
                <logout/>
                <clTRID/>
            </command>
            </epp>';
    $result = molddata_eppSendCommand ($fp, $xml);
    fclose($fp);
    return true;
}

// ############################################################################################################

function molddata_eppGetResultCode ($result) {
    $start_pos = strpos ($result, '<result code="');
    return substr ($result, $start_pos + 14, 4);
}

// ############################################################################################################

function molddata_eppSendCommand ($fp, $command) {
    $result="";
    $len = strlen ($command);
    $value = $len + 4;
    $b3 = $value % 256;
    $value = ($value - $b3) / 256;
    $b2 = $value % 256;
    $value = ($value - $b2) / 256;
    $b1 = $value % 256;
    $value = ($value - $b1) / 256;
    $b0 = $value % 256;
    if (!@fwrite ($fp, @chr ($b0) . @chr ($b1) . @chr ($b2) . @chr ($b3), 4)){
        logModuleCall( "MoldData", "command", $command, "Error");
        return $result;
    }
    if (!@fwrite ($fp, $command)){
        return $result;
    }
    $i = 0;
    while (!feof ($fp)) {
        $i++;
        $result .= fgetc($fp);
        if(strpos($result,'</epp>')) {
            break;
        }
        if ($i > 500000) {
            break;
        }
    }
    $result = preg_replace("/.+<\?xml/","<?xml",$result);
    logModuleCall( "MoldData", "command", $command, html_entity_decode(htmlentities($result,ENT_IGNORE)));
    return $result;
}

// ############################################################################################################

function molddata_RegisterDomain($params) {
    $fp = molddata_login($params);
    if (is_array($fp)) {
        return $fp;
    }
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
                 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                 xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
            <command>
                <create>
                    <domain:create xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
                        <domain:account>'.$params['Login'].'</domain:account>
                        <domain:account_pw>'.$params['Password'].'</domain:account_pw>
                        <domain:name years="'.$params['regperiod'].'">'.$params['sld'].'</domain:name>

                        <domain:org_name>'.$params['companyname'].'</domain:org_name>

                        <domain:adm_firstname>'.$params['firstname'].'</domain:adm_firstname>
                        <domain:adm_lastname>'.$params['lastname'].'</domain:adm_lastname>
                        <domain:adm_str>'.$params['address1'].'</domain:adm_str>
                        <domain:adm_city>'.$params['city'].'</domain:adm_city>
                        <domain:adm_postc>'.$params['postcode'].'</domain:adm_postc>
                        <domain:adm_country>'.$params['countrycode'].'</domain:adm_country>
                        <domain:adm_phone>'.$params['phonenumber'].'</domain:adm_phone>
                        <domain:adm_email>'.$params['email'].'</domain:adm_email>
                        <domain:adm_state>'.$params['state'].'</domain:adm_state>

                        <domain:teh_firstname>'.$params['def_firstname'].'</domain:teh_firstname>
                        <domain:teh_lastname>'.$params['def_lastname'].'</domain:teh_lastname>
                        <domain:teh_str>'.$params['def_address1'].'</domain:teh_str>
                        <domain:teh_city>'.$params['def_city'].'</domain:teh_city>
                        <domain:teh_postc>'.$params['def_postcode'].'</domain:teh_postc>
                        <domain:teh_country>'.$params['def_country'].'</domain:teh_country>
                        <domain:teh_phone>'.$params['def_phone'].'</domain:teh_phone>
                        <domain:teh_email>'.$params['def_email'].'</domain:teh_email>
                        <domain:teh_state>'.$params['def_state'].'</domain:teh_state>

                        <domain:bil_firstname>'.$params['def_firstname'].'</domain:bil_firstname>
                        <domain:bil_lastname>'.$params['def_lastname'].'</domain:bil_lastname>
                        <domain:bil_str>'.$params['def_address1'].'</domain:bil_str>
                        <domain:bil_city>'.$params['def_city'].'</domain:bil_city>
                        <domain:bil_postc>'.$params['def_postcode'].'</domain:bil_postc>
                        <domain:bil_country>'.$params['def_country'].'</domain:bil_country>
                        <domain:bil_phone>'.$params['def_phone'].'</domain:bil_phone>
                        <domain:bil_email>'.$params['def_email'].'</domain:bil_email>
                        <domain:bil_state>'.$params['def_state'].'</domain:bil_state>

                        <domain:primNSname>'.$params['ns1'].'</domain:primNSname>
                        <domain:secNSname>'.$params['ns2'].'</domain:secNSname>
                    </domain:create>
                </create>
                <clTRID>'.rand(10000,99999).'</clTRID>
            </command>
            </epp>';

    $result = molddata_eppSendCommand ($fp, $xml);
    if (!strstr ($result, '<result code="1000">')) {
        if (preg_match("/<msg>(.+)<\/msg>/",$result,$msg)) {
            $epp_result['error'] = 'Error: '.$msg[0];
        } else {
            $epp_result['error'] = 'Error!';
        }
    } else {
        if (!strstr($result,'domain:name res="1"')) {
            $epp_result['error'] = 'Error: domain busy';
        } else {
            $epp_result = 'Successful';
        }
    }
    molddata_logout($fp,$params);
    return $epp_result;
}

// ############################################################################################################

function molddata_RenewDomain($params) {
    $fp = molddata_login($params);
    if (is_array($fp)) {
        return $fp;
    }
    $rdata = Capsule::table('tbldomains')->where('id',$params['domainid'])->first();
    if ($rdata) {
        $domainExpDate = $rdata->expirydate;
    }
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
                 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                 xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
            <command>
                <renew>
                    <domain:renew xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
                        <domain:account>'.$params['Login'].'</domain:account>
                        <domain:account_pw>'.$params['Password'].'</domain:account_pw>
                        <domain:name curexp="'.$domainExpDate.'" years="'.$params['regperiod'].'">'.$params['sld'].'</domain:name>
                    </domain:renew>
                </renew>
                <clTRID>'.rand(10000,99999).'</clTRID>
            </command>
            </epp>';

    $result = molddata_eppSendCommand ($fp, $xml);
    if (!strstr ($result, '<result code="1000">')) {
        if (preg_match("/<msg>(.+)<\/msg>/",$result,$msg)) {
            $epp_result['error'] = 'Error: '.$msg[0];
        } else {
            $epp_result['error'] = 'Error!';
        }
    } else {
        if (strstr($result,'domain:name res="0"')) {
            $epp_result['error'] = 'Error: domain not in account';
        } elseif (strstr($result,'domain:name res="2"')) {
            $epp_result['error'] = 'Error: fail current expiration date';
        } else {
            $epp_result = 'Successful';
        }
    }
    molddata_logout($fp,$params);
    return $epp_result;
}

// ############################################################################################################

function molddata_GetNameservers($params) {
    $fp = molddata_login($params);
    if (is_array($fp)) {
        return $fp;
    }
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
                 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                 xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
            <command>
                <info>
                    <domain:info xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
                        <domain:account>'.$params['Login'].'</domain:account>
                        <domain:account_pw>'.$params['Password'].'</domain:account_pw>
                        <domain:name curexp="'.$domainExpDate.'" years="'.$params['regperiod'].'">'.$params['sld'].'</domain:name>
                    </domain:info>
                </info>
                <clTRID>'.rand(10000,99999).'</clTRID>
            </command>
            </epp>';

    $result = molddata_eppSendCommand ($fp, $xml);
    if (!strstr ($result, '<result code="1000">')) {
        if (preg_match("/<msg>(.+)<\/msg>/",$result,$msg)) {
            $epp_result['error'] = 'Error: '.$msg[1];
        } else {
            $epp_result['error'] = 'Error!';
        }
    } else {
        if (strstr($result,'domain:name res="0"')) {
            $epp_result['error'] = 'Error: domain not exists';
        } elseif (strstr($result,'domain:name res="2"')) {
            $epp_result['error'] = 'Error: domain not in account';
        } else {
            $epp_result= array();
            if (preg_match("/<domain:primNSname>(.+)<\/domain:primNSname>/",$result,$ns)) {
                $epp_result['ns1'] = $ns[1];
            }
            if (preg_match("/<domain:primNSip4>(.+)<\/domain:primNSip4>/",$result,$ns)) {
                $epp_result['ns1'] .= " ".$ns[1];
            }
            if (preg_match("/<domain:primNSip6>(.+)<\/domain:primNSip6>/",$result,$ns)) {
                $epp_result['ns1'] .= " ".$ns[1];
            }
            if (preg_match("/<domain:secNSname>(.+)<\/domain:secNSname>/",$result,$ns)) {
                $epp_result['ns2'] = $ns[1];
            }
            if (preg_match("/<domain:secNSip4>(.+)<\/domain:secNSip4>/",$result,$ns)) {
                $epp_result['ns2'] .= " ".$ns[1];
            }
            if (preg_match("/<domain:secNSip6>(.+)<\/domain:secNSip6>/",$result,$ns)) {
                $epp_result['ns2'] .= " ".$ns[1];
            }
        }
    }
    molddata_logout($fp,$params);
    return $epp_result;

}// ############################################################################################################

function molddata_SaveNameservers($params) {
    $fp = molddata_login($params);
    if (is_array($fp)) {
        return $fp;
    }
    $ns1 = explode(" ",$params['ns1']);
    $ns2 = explode(" ",$params['ns2']);


    $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
                 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                 xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
            <command>
                <update>
                    <domain:update xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
                        <domain:account>'.$params['Login'].'</domain:account>
                        <domain:account_pw>'.$params['Password'].'</domain:account_pw>
                        <domain:name>'.$params['sld'].'</domain:name>

                        <domain:primNSname>'.$ns1[0].'</domain:primNSname>
                        <domain:primNSip4>'.$ns1[1].'</domain:primNSip4>
                        <domain:primNSip6>'.$ns1[2].'</domain:primNSip6>
                        <domain:secNSname>'.$ns2[0].'</domain:secNSname>
                        <domain:secNSip4>'.$ns2[1].'</domain:secNSip4>
                        <domain:secNSip6>'.$ns2[2].'</domain:secNSip6>
                    </domain:update>
                </update>
                <clTRID>'.rand(10000,99999).'</clTRID>
            </command>
            </epp>';

    $result = molddata_eppSendCommand ($fp, $xml);
    if (!strstr ($result, '<result code="1000">')) {
        if (preg_match("/<msg>(.+)<\/msg>/",$result,$msg)) {
            $epp_result['error'] = 'Error: '.$msg[0];
        } else {
            $epp_result['error'] = 'Error!';
        }
    } else {
        if (strstr($result,'domain:name res="0"')) {
            $epp_result['error'] = 'Error: domain not in account';
        } else {
            $epp_result = 'Successful';
        }
    }
    molddata_logout($fp,$params);
    return $epp_result;
}

// ############################################################################################################

function molddata_Sync($params) {
    $fp = molddata_login($params);
    if (is_array($fp)) {
        return $fp;
    }
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
                 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                 xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
            <command>
                <info>
                    <domain:info xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
                        <domain:account>'.$params['Login'].'</domain:account>
                        <domain:account_pw>'.$params['Password'].'</domain:account_pw>
                        <domain:name curexp="'.$domainExpDate.'" years="'.$params['regperiod'].'">'.$params['sld'].'</domain:name>
                    </domain:info>
                </info>
                <clTRID>'.rand(10000,99999).'</clTRID>
            </command>
            </epp>';

    $result = molddata_eppSendCommand ($fp, $xml);
    if (!strstr ($result, '<result code="1000">')) {
        if (preg_match("/<msg>(.+)<\/msg>/",$result,$msg)) {
            $epp_result['error'] = 'Error: '.$msg[1];
        } else {
            $epp_result['error'] = 'Error!';
        }
    } else {
        if (strstr($result,'domain:name res="0"')) {
            $epp_result['error'] = 'Error: domain not exists';
        } elseif (strstr($result,'domain:name res="2"')) {
            $epp_result['error'] = 'Error: domain not in account';
        } else {
            $epp_result= array();
            if (preg_match("/<domain:exp_date>(.+)<\/domain:exp_date>/",$result,$exp)) {
                $epp_result['expirydate'] = $exp[1];
                $expiryEpoch = strtotime( $exp[1] );
                if ($expiryEpoch !== false) { 
                    if (time() < $expiryEpoch) {
                        $epp_result['active'] = true;
                    } else {
                        $epp_result['expired'] = true;
                    }
                }
            }
        }
    }
    molddata_logout($fp,$params);
    return $epp_result;
}

// ############################################################################################################

function molddata_GetContactDetails($params) {
	
	$epp_info_parameters = array (
	'org_name',
	'org_str',
	'org_city',
	'org_postc',
	'org_state',
	'org_country',
	'adm_firstname',
	'adm_lastname',
	'adm_str',
	'adm_city',
	'adm_postc',
	'adm_state',
	'adm_country',
	'adm_phone',
	'adm_fax',
	'adm_email',
	'teh_firstname',
	'teh_lastname',
	'teh_str',
	'teh_city',
	'teh_postc',
	'teh_state',
	'teh_country',
	'teh_phone',
	'teh_fax',
	'teh_email',
	'bil_firstname',
	'bil_lastname',
	'bil_str',
	'bil_city',
	'bil_postc',
	'bil_state',
	'bil_country',
	'bil_phone',
	'bil_fax',
	'bil_email',
	'primNSname',
	'primNSip4',
	'primNSip6',
	'secNSname',
	'secNSip4',
	'secNSip6',
	'reg_date',
	'exp_date',
	'ren_date'
);
	
	
    $fp = molddata_login($params);
    if (is_array($fp)) {
        return $fp;
    }
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
                 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                 xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
            <command>
                <info>
                    <domain:info xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
                        <domain:account>'.$params['Login'].'</domain:account>
                        <domain:account_pw>'.$params['Password'].'</domain:account_pw>
                        <domain:name curexp="'.$domainExpDate.'" years="'.$params['regperiod'].'">'.$params['sld'].'</domain:name>
                    </domain:info>
                </info>
                <clTRID>'.rand(10000,99999).'</clTRID>
            </command>
            </epp>';

    $result = molddata_eppSendCommand ($fp, $xml);
        
    if (!strstr ($result, '<result code="1000">')) {
        if (preg_match("/<msg>(.+)<\/msg>/",$result,$msg)) {
            $epp_result['error'] = 'Error: '.$msg[1];
        } else {
            $epp_result['error'] = 'Error!';
        }
    } else {
        if (strstr($result,'domain:name res="0"')) {
            $epp_result['error'] = 'Error: domain not exists';
        } elseif (strstr($result,'domain:name res="2"')) {
            $epp_result['error'] = 'Error: domain not in account';
        } else {
            $epp_result= array();
            $epp_result['Registrant'] = array();
            $epp_result['Registrant']['Organization Name'] = "";
            
            $epp_info = '';
            foreach($epp_info_parameters as $p) {
				if (preg_match("/<domain:$p>(.+)<\/domain:$p>/",$result,$exp)) {
					$epp_info[$p] = $exp[1];
				}
            }
            
            
            $epp_result = array(
				'Administrative' => array(
					'First Name' => $epp_info['adm_firstname'],
					'Last Name' => $epp_info['adm_lastname'],
					'Email' => $epp_info['adm_email'],
					'Address' => $epp_info['adm_str'],
					'City' => $epp_info['adm_city'],
					'State' => $epp_info['adm_state'],
					'Postcode' => $epp_info['adm_postc'],
					'Country' => $epp_info['adm_country'],
					'Phone' => $epp_info['adm_phone'],
					'Company Name' => $epp_info['org_name'],
				),
				'Technical' => array(
					'First Name' => $epp_info['teh_firstname'],
					'Last Name' => $epp_info['teh_lastname'],
					'Email' => $epp_info['teh_email'],
					'Address' => $epp_info['teh_str'],
					'City' => $epp_info['teh_city'],
					'State' => $epp_info['teh_state'],
					'Postcode' => $epp_info['teh_postc'],
					'Country' => $epp_info['teh_country'],
					'Phone' => $epp_info['teh_phone'],
				),
				'Billing' => array(
					'First Name' => $epp_info['bil_firstname'],
					'Last Name' => $epp_info['bil_lastname'],
					'Email' => $epp_info['bil_email'],
					'Address' => $epp_info['bil_str'],
					'City' => $epp_info['bil_city'],
					'State' => $epp_info['bil_state'],
					'Postcode' => $epp_info['bil_postc'],
					'Country' => $epp_info['bil_country'],
					'Phone' => $epp_info['bil_phone'],
				),
			);
        }
    }
    molddata_logout($fp,$params);
    return $epp_result;
}

// ############################################################################################################

function molddata_SaveContactDetails($params) {
    $fp = molddata_login($params);
    if (is_array($fp)) {
        return $fp;
    }
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
                 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                 xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
            <command>
                <update>
                    <domain:update xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
                        <domain:account>'.$params['Login'].'</domain:account>
                        <domain:account_pw>'.$params['Password'].'</domain:account_pw>
                        <domain:name>'.$params['sld'].'</domain:name>

                        <domain:org_name>'.$params['contactdetails']['Administrative']['Company Name'].'</domain:org_name>

                        <domain:adm_firstname>'.$params['contactdetails']['Administrative']['First Name'].'</domain:adm_firstname>
                        <domain:adm_lastname>'.$params['contactdetails']['Administrative']['Last Name'].'</domain:adm_lastname>
                        <domain:adm_str>'.$params['contactdetails']['Administrative']['Address'].'</domain:adm_str>
                        <domain:adm_city>'.$params['contactdetails']['Administrative']['City'].'</domain:adm_city>
                        <domain:adm_postc>'.$params['contactdetails']['Administrative']['Postcode'].'</domain:adm_postc>
                        <domain:adm_country>'.$params['contactdetails']['Administrative']['Country'].'</domain:adm_country>
                        <domain:adm_phone>'.$params['contactdetails']['Administrative']['Phone'].'</domain:adm_phone>
                        <domain:adm_email>'.$params['contactdetails']['Administrative']['Email'].'</domain:adm_email>
                        <domain:adm_state>'.$params['contactdetails']['Administrative']['State'].'</domain:adm_state>

                        <domain:teh_firstname>'.$params['contactdetails']['Technical']['First Name'].'</domain:teh_firstname>
                        <domain:teh_lastname>'.$params['contactdetails']['Technical']['Last Name'].'</domain:teh_lastname>
                        <domain:teh_str>'.$params['contactdetails']['Technical']['Address'].'</domain:teh_str>
                        <domain:teh_city>'.$params['contactdetails']['Technical']['City'].'</domain:teh_city>
                        <domain:teh_postc>'.$params['contactdetails']['Technical']['Postcode'].'</domain:teh_postc>
                        <domain:teh_country>'.$params['contactdetails']['Technical']['Country'].'</domain:teh_country>
                        <domain:teh_phone>'.$params['contactdetails']['Technical']['Phone'].'</domain:teh_phone>
                        <domain:teh_email>'.$params['contactdetails']['Technical']['Email'].'</domain:teh_email>
                        <domain:teh_state>'.$params['contactdetails']['Technical']['State'].'</domain:teh_state>

                        <domain:bil_firstname>'.$params['contactdetails']['Billing']['First Name'].'</domain:bil_firstname>
                        <domain:bil_lastname>'.$params['contactdetails']['Billing']['Last Name'].'</domain:bil_lastname>
                        <domain:bil_str>'.$params['contactdetails']['Billing']['Address'].'</domain:bil_str>
                        <domain:bil_city>'.$params['contactdetails']['Billing']['City'].'</domain:bil_city>
                        <domain:bil_postc>'.$params['contactdetails']['Billing']['Postcode'].'</domain:bil_postc>
                        <domain:bil_country>'.$params['contactdetails']['Billing']['Country'].'</domain:bil_country>
                        <domain:bil_phone>'.$params['contactdetails']['Billing']['Phone'].'</domain:bil_phone>
                        <domain:bil_email>'.$params['contactdetails']['Billing']['Email'].'</domain:bil_email>
                        <domain:bil_state>'.$params['contactdetails']['Billing']['State'].'</domain:bil_state>
                    </domain:update>
                </update>
                <clTRID>'.rand(10000,99999).'</clTRID>
            </command>
            </epp>';

    $result = molddata_eppSendCommand ($fp, $xml);
    if (!strstr ($result, '<result code="1000">')) {
        if (preg_match("/<msg>(.+)<\/msg>/",$result,$msg)) {
            $epp_result['error'] = 'Error: '.$msg[0];
        } else {
            $epp_result['error'] = 'Error!';
        }
    } else {
        if (strstr($result,'domain:name res="0"')) {
            $epp_result['error'] = 'Error: domain not in account';
        } else {
            $epp_result = 'Successful';
        }
    }
    molddata_logout($fp,$params);
    return $epp_result;
}
