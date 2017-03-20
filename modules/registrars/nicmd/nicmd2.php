<?php

function nicmd_getConfigArray()
{
    return array(
        'FriendlyName'   => array(
            'Type'  => 'System',
            'Value' => 'Nic.MD',
        ),
        'Description'    => array(
            'Type'  => 'System',
            'Value' => 'A module for domains registration and management with Nic.MD provider',
        ),
        "username"       => array(
            "FriendlyName" => "API username (read)",
            "Type"         => "text",
            "Size"         => "20",
            "Description"  => "Enter your READ-username here",
        ),
        "password"       => array(
            "FriendlyName" => "API password (read)",
            "Type"         => "password",
            "Size"         => "20",
            "Description"  => "Enter your READ-password here",
        ),
        "username2"       => array(
            "FriendlyName" => "API username (write)",
            "Type"         => "text",
            "Size"         => "20",
            "Description"  => "Enter your WRITE-username here",
        ),
        "password2"       => array(
            "FriendlyName" => "API password (write)",
            "Type"         => "password",
            "Size"         => "20",
            "Description"  => "Enter your WRITE-password here",
        ),
        "testmode"       => array(
            "FriendlyName" => "Test mode",
            "Type"         => "yesno",
            "Description"  => "Tick to work in test mode",
        ),
    );
}

function nicmd_GetNameservers($params)
{
    list($username, $password) = NicMD_Module_Helper::extractConfig($params);

    try {
        $client = new NicMD__Epp_Client($username, $password);
        $response = $client->request('<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
     xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
     xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <info>
      <domain:info
       xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"
       xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
        <domain:name hosts="all">' . htmlspecialchars($params['sld'], ENT_QUOTES, 'UTF-8') . '</domain:name>
      </domain:info>
    </info>
    <clTRID>{clTRID}</clTRID>
  </command>
</epp>');

        $infData = $response->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->infData;
    } catch (Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }

    if (is_object($infData)) {
        return array(
            'ns1' => (!empty($infData->primNSname) ? (string) $infData->primNSname : ''),
            'ns2' => (!empty($infData->secNSname)  ? (string) $infData->secNSname  : ''),
            'ns3' => (!empty($response['ns3']) ? $response['ns3'] : ''),
            'ns4' => (!empty($response['ns4']) ? $response['ns4'] : ''),
            'ns5' => (!empty($response['ns5']) ? $response['ns5'] : ''),
        );
    } else {
        return array(
            'error' => "Response XML is not correct. Please check the module's logs",
        );
    }
}

function nicmd_SaveNameservers($params)
{
    list($username, $password, , $username2, $password2) = NicMD_Module_Helper::extractConfig($params);

    try {
        $nameservers = NicMD_Module_Helper::extractNameservers($params);

        $client = new NicMD__Epp_Client($username, $password);
        $client->request('<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
     xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
     xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <update>
      <domain:update
       xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"
       xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
        <domain:account>' . htmlspecialchars($username2, ENT_QUOTES, 'UTF-8') . '</domain:account>
        <domain:account_pw>' . htmlspecialchars($password2, ENT_QUOTES, 'UTF-8') . '</domain:account_pw>
        <domain:name>' . htmlspecialchars($params["sld"]) . '</domain:name>
        <domain:primNSname>' . htmlspecialchars($nameservers['ns1'], ENT_QUOTES, 'UTF-8') . '</domain:primNSname>
        <domain:secNSname>'  . htmlspecialchars($nameservers['ns2'], ENT_QUOTES, 'UTF-8') . '</domain:secNSname>
      </domain:update>
    </update>
    <clTRID>{clTRID}</clTRID>
  </command>
</epp>');
    } catch (Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }

    return array();
}

function nicmd_RegisterDomain($params)
{
    list($username, $password, , $username2, $password2) = NicMD_Module_Helper::extractConfig($params);

    try {
        $client = new NicMD__Epp_Client($username, $password);

        $response = $client->request('<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <check>
      <domain:check
        xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"
        xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
        <domain:name>' . htmlspecialchars($params['sld'], ENT_QUOTES, 'UTF-8') . '</domain:name>
      </domain:check>
    </check>
    <clTRID>{clTRID}</clTRID>
  </command>
</epp>');
        $chkData = $response->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->chkData;
    } catch (Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }

    if (is_object($chkData->name->attributes()->res) && '0' == (string) $chkData->name->attributes()->res) {
        return array(
            'error' => sprintf("Unable to register the domain '%s.%s' - it's already taken", $params['sld'], $params['tld']) ,
        );
    }

    $nameservers = NicMD_Module_Helper::extractNameservers($params);
    $contactData = array(
        'org_name'      => $params['companyname'], # Organization name (Registrant) (mandatory)
        'org_str'       => "{$params['address1']} {$params['address2']}", # Address Field  (mandatory)
        'org_city'      => $params['city'], # City (mandatory)
        'org_postc'     => $params['postcode'], # Postal Code
        'org_state'     => $params['state'], # state
        'org_country'   => $params['country'], # ISO 3166 Country Code (mandatory)
        'adm_firstname' => $params['firstname'], # Administrative Contact First Name (mandatory)
        'adm_lastname'  => $params['lastname'], # Administrative Contact Last Name (mandatory)
        'adm_str'       => "{$params['address1']} {$params['address2']}", # Address Field  (mandatory)
        'adm_city'      => $params['city'], # City (mandatory)
        'adm_postc'     => $params['postcode'], # Postal Code
        'adm_state'     => $params['state'], # state
        'adm_country'   => $params['country'], # ISO 3166 Country Code (mandatory)
        'adm_phone'     => $params['phonenumber'], # Phone Number (mandatory)
        'adm_fax'       => '', # Fax Number
        'adm_email'     => $params['email'], # Email Address (mandatory)
        'teh_firstname' => $params['firstname'], # Technical Contact First Name (mandatory)
        'teh_lastname'  => $params['lastname'], # Technical Contact Last Name (mandatory)
        'teh_str'       => "{$params['address1']} {$params['address2']}", # Address Field  (mandatory)
        'teh_city'      => $params['city'], # City (mandatory)
        'teh_postc'     => $params['postcode'], # Postal Code
        'teh_state'     => $params['state'], # state
        'teh_country'   => $params['country'], # ISO 3166 Country Code (mandatory)
        'teh_phone'     => $params['phonenumber'], # Phone Number (mandatory)
        'teh_fax'       => '', # Fax Number
        'teh_email'     => $params['email'], # Email Address (mandatory)
        'bil_firstname' => $params['firstname'], # Billing Contact First Name (mandatory)
        'bil_lastname'  => $params['lastname'], # Billing Contact Last Name (mandatory)
        'bil_str'       => "{$params['address1']} {$params['address2']}", # Address Field  (mandatory)
        'bil_city'      => $params['city'], # City (mandatory)
        'bil_postc'     => $params['postcode'], # Postal Code
        'bil_state'     => $params['state'], # state
        'bil_country'   => $params['country'], # ISO 3166 Country Code (mandatory)
        'bil_phone'     => $params['phonenumber'], # Phone Number (mandatory)
        'bil_fax'       => '', # Fax Number
        'bil_email'     => $params['email'], # Email Address (mandatory)
        'primNSname'    => $nameservers['ns1'], # Primary Name Server hostname
        'primNSip4'     => '', # Primary Name Server IPv4
        'primNSip6'     => '', # Primary Name Server IPv6
        'secNSname'     => $nameservers['ns2'], # Secondary Name Server hostname
        'secNSip4'      => '', # Secondary Name Server IPv4
        'secNSip6'      => '' # Secondary Name Server IPv6
    );

    $requestXml = '<?xml version="1.0" encoding="UTF-8"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <create>
      <domain:create xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"
        xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
        <domain:account>' . htmlspecialchars($username2, ENT_QUOTES, 'UTF-8') . '</domain:account>
        <domain:account_pw>' . htmlspecialchars($password2, ENT_QUOTES, 'UTF-8') . '</domain:account_pw>
        <domain:name years="' . ((int) $params['regperiod']) . '">' . htmlspecialchars($params['sld'], ENT_QUOTES, 'UTF-8') . '</domain:name>';
    foreach ($contactData as $field => $value) {
        $requestXml .= "<domain:{$field}>" . htmlspecialchars($value) . "</domain:{$field}>";
    }
    $requestXml .= '</domain:create>
    </create>
    <clTRID>{clTRID}</clTRID>
  </command>
</epp>';

    try {
        $response = $client->request($requestXml);
        $creData = $response->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->creData;
    } catch (Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }

    if (is_object($creData->name->attributes()->res) && '0' == (string) $creData->name->attributes()->res) {
        return array(
            'error' => sprintf("Unable to register the domain '%s.%s' - %s", $params['sld'], $params['tld'],
                (string) $creData->name),
        );
    }

    return array();
}

function nicmd_RenewDomain($params)
{
    list($username, $password, , $username2, $password2) = NicMD_Module_Helper::extractConfig($params);

    try {
        $client = new NicMD__Epp_Client($username, $password);

        $response = $client->request('<?xml version="1.0" encoding="UTF-8"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <info>
      <domain:info xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"
        xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
        <domain:account>' . htmlspecialchars($username2, ENT_QUOTES, 'UTF-8') . '</domain:account>
        <domain:account_pw>' . htmlspecialchars($password2, ENT_QUOTES, 'UTF-8') . '</domain:account_pw>
        <domain:name>' . htmlspecialchars($params['sld'], ENT_QUOTES, 'UTF-8') . '</domain:name>
      </domain:info>
    </info>
    <clTRID>{clTRID}</clTRID>
  </command>
</epp>');
        $infData = $response->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->infData;
    } catch (Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }

    $expDate = (string) $infData->exp_date;

    try {
        $response = $client->request('<?xml version="1.0" encoding="UTF-8"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
     xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
     xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
    <command>
        <renew>
            <domain:renew xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"
                xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
                <domain:account>' . htmlspecialchars($username2, ENT_QUOTES, 'UTF-8') . '</domain:account>
                <domain:account_pw>' . htmlspecialchars($password2, ENT_QUOTES, 'UTF-8') . '</domain:account_pw>
                <domain:name curexp="' . $expDate . '" years="' . ((int) $params['regperiod']) . '">' .
                  htmlspecialchars($params['sld'], ENT_QUOTES, 'UTF-8') . '</domain:name>
            </domain:renew>
        </renew>
        <clTRID>{clTRID}</clTRID>
    </command>
</epp>');
        $creData = $response->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->creData;
    } catch (Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }

    if (is_object($creData->name->attributes()->res) && '0' == (string) $creData->name->attributes()->res) {
        return array(
            'error' => sprintf("Unable to renew the domain '%s.%s' - %s", $params['sld'], $params['tld'],
                (string) $creData->name),
        );
    }

    return array();
}

function nicmd_GetContactDetails($params)
{
    list($username, $password, , $username2, $password2) = NicMD_Module_Helper::extractConfig($params);

    try {
        $client = new NicMD__Epp_Client($username, $password);

        $response = $client->request('<?xml version="1.0" encoding="UTF-8"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <info>
      <domain:info xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"
        xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
        <domain:account>' . htmlspecialchars($username2, ENT_QUOTES, 'UTF-8') . '</domain:account>
        <domain:account_pw>' . htmlspecialchars($password2, ENT_QUOTES, 'UTF-8') . '</domain:account_pw>
        <domain:name>' . htmlspecialchars($params['sld'], ENT_QUOTES, 'UTF-8') . '</domain:name>
      </domain:info>
    </info>
    <clTRID>{clTRID}</clTRID>
  </command>
</epp>');
        $infData = $response->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->infData;
    } catch (Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }

    return array(
        'Administrator' => array(
            'First Name'                      => (string) $infData->adm_firstname,
            'Last Name'                       => (string) $infData->adm_lastname,
            'Email'                           => (string) $infData->adm_email,
            'Address'                         => (string) $infData->adm_str,
            'City'                            => (string) $infData->adm_city,
            'State'                           => (string) $infData->adm_state,
            'Postcode'                        => (string) $infData->adm_postc,
            'Country (in ISO format - MD,RO)' => (string) $infData->adm_country,
            'Phone'                           => (string) $infData->adm_phone,
        ),
        'Technical' => array(
            'First Name'                      => (string) $infData->teh_firstname,
            'Last Name'                       => (string) $infData->teh_lastname,
            'Email'                           => (string) $infData->teh_email,
            'Address'                         => (string) $infData->teh_str,
            'City'                            => (string) $infData->teh_city,
            'State'                           => (string) $infData->teh_state,
            'Postcode'                        => (string) $infData->teh_postc,
            'Country (in ISO format - MD,RO)' => (string) $infData->teh_country,
            'Phone'                           => (string) $infData->teh_phone,
        ),
        'Billing' => array(
            'First Name'                      => (string) $infData->bil_firstname,
            'Last Name'                       => (string) $infData->bil_lastname,
            'Email'                           => (string) $infData->bil_email,
            'Address'                         => (string) $infData->bil_str,
            'City'                            => (string) $infData->bil_city,
            'State'                           => (string) $infData->bil_state,
            'Postcode'                        => (string) $infData->bil_postc,
            'Country (in ISO format - MD,RO)' => (string) $infData->bil_country,
            'Phone'                           => (string) $infData->bil_phone,
        ),
    );
}

function nicmd_SaveContactDetails($params)
{
    list($username, $password, , $username2, $password2) = NicMD_Module_Helper::extractConfig($params);

    $contactData = array(
        'adm_firstname' => $params['contactdetails']['Administrator']['First Name'],
        'adm_lastname'  => $params['contactdetails']['Administrator']['Last Name'],
        'adm_str'       => $params['contactdetails']['Administrator']['Address'],
        'adm_city'      => $params['contactdetails']['Administrator']['City'],
        'adm_postc'     => $params['contactdetails']['Administrator']['Postcode'],
        'adm_state'     => $params['contactdetails']['Administrator']['State'],
        'adm_country'   => $params['contactdetails']['Administrator']['Country'],
        'adm_phone'     => $params['contactdetails']['Administrator']['Phone'],
        'adm_email'     => $params['contactdetails']['Administrator']['Email'],
        'teh_firstname' => $params['contactdetails']['Technical']['First Name'],
        'teh_lastname'  => $params['contactdetails']['Technical']['Last Name'],
        'teh_str'       => $params['contactdetails']['Technical']['Address'],
        'teh_city'      => $params['contactdetails']['Technical']['City'],
        'teh_postc'     => $params['contactdetails']['Technical']['Postcode'],
        'teh_state'     => $params['contactdetails']['Technical']['State'],
        'teh_country'   => $params['contactdetails']['Technical']['Country'],
        'teh_phone'     => $params['contactdetails']['Technical']['Phone'],
        'teh_email'     => $params['contactdetails']['Technical']['Email'],
        'bil_firstname' => $params['contactdetails']['Billing']['First Name'],
        'bil_lastname'  => $params['contactdetails']['Billing']['Last Name'],
        'bil_str'       => $params['contactdetails']['Billing']['Address'],
        'bil_city'      => $params['contactdetails']['Billing']['City'],
        'bil_postc'     => $params['contactdetails']['Billing']['Postcode'],
        'bil_state'     => $params['contactdetails']['Billing']['State'],
        'bil_country'   => $params['contactdetails']['Billing']['Country'],
        'bil_phone'     => $params['contactdetails']['Billing']['Phone'],
        'bil_email'     => $params['contactdetails']['Billing']['Email'],
    );

    $requestXml = '<?xml version="1.0" encoding="UTF-8"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"

  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
<command>
 <update>
  <domain:update xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"
  xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
   <domain:account>' . htmlspecialchars($username2, ENT_QUOTES, 'UTF-8') . '</domain:account>
   <domain:account_pw>' . htmlspecialchars($password2, ENT_QUOTES, 'UTF-8') . '</domain:account_pw>
   <domain:name>' . htmlspecialchars($params['sld'], ENT_QUOTES, 'UTF-8') . '</domain:name>';
    foreach ($contactData as $field => $value) {
        $requestXml .= "<domain:{$field}>" . htmlspecialchars($value) . "</domain:{$field}>";
    }
    $requestXml .= '</domain:update>
    </update>
    <clTRID>{clTRID}</clTRID>
  </command>
</epp>';

    try {
        $client = new NicMD__Epp_Client($username, $password);
        $response = $client->request($requestXml);
        $creData = $response->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->creData;
    } catch (Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }

    if (is_object($creData->name->attributes()->res) && '0' == (string) $creData->name->attributes()->res) {
        return array(
            'error' => sprintf("Unable to register the domain '%s.%s' - %s", $params['sld'], $params['tld'],
                (string) $creData->name),
        );
    }

    return array();
}

function nicmd_Sync($params)
{
    list($username, $password, , $username2, $password2) = NicMD_Module_Helper::extractConfig($params);

    try {
        $client = new NicMD__Epp_Client($username, $password);

        $response = $client->request('<?xml version="1.0" encoding="UTF-8"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <info>
      <domain:info xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"
        xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
        <domain:account>' . htmlspecialchars($username2, ENT_QUOTES, 'UTF-8') . '</domain:account>
        <domain:account_pw>' . htmlspecialchars($password2, ENT_QUOTES, 'UTF-8') . '</domain:account_pw>
        <domain:name>' . htmlspecialchars($params['sld'], ENT_QUOTES, 'UTF-8') . '</domain:name>
      </domain:info>
    </info>
    <clTRID>{clTRID}</clTRID>
  </command>
</epp>');
        $infData = $response->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->infData;
    } catch (Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }

    $expDate = (string) $infData->exp_date;
    $expDate = (preg_match('~(\d{4})-(\d{2})-(\d{2})~', $expDate) ? DateTime::createFromFormat('Y-m-d', $expDate)
        : new DateTime());

    return array(
        'active' => ($expDate > new DateTime),
        'expired' => ($expDate < new DateTime),
        'expirydate' => $expDate->format('Y-m-d'),
    );
}

function nicmd_UpdateDomainStatus($params)
{
    $status = nicmd_Sync($params);

    if (isset($status['active']) && true === $status['active']) {
        $newStatus = 'Active';
    } elseif (isset($status['expired']) && true === $status['expired']) {
        $newStatus = 'Expired';
    } else {
        $newStatus = 'Pending';
    }

    if (!empty($newStatus)) {
        /** @noinspection PhpUndefinedFunctionInspection */
        localAPI('updateclientdomain', array('domainid' => $params['domainid'], 'status' => $newStatus));
    }
}

function nicmd_AdminCustomButtonArray() // ClientAreaCustomButtonArray()
{
    return array(
	    'Update Status' => 'UpdateDomainStatus',
	);
}

require_once dirname(__FILE__) . '/lib/NicMD__Epp_Client.php';

class NicMD_Module_Helper
{
    /**
     * @param array $params
     *
     * @return array(username, password, language, ssl, testMode, mnamecfid, bdaycfid, passnumcfid, passdatecfid, passorigincfid, forceemail)
     */
    static public function extractConfig($params)
    {
        $testmode = (!empty($params['testmode']) && ('on' == strtolower($params['testmode'])));

        return array(
            ($testmode ? 'test'    : (!empty($params['username']) ? $params['username'] : '')),
            ($testmode ? 'test123' : (!empty($params['password']) ? $params['password'] : '')),
            $testmode,
            ($testmode ? 'epptest@nic.md' : (!empty($params['username2']) ? $params['username2'] : '')),
            ($testmode ? 'test123'        : (!empty($params['password2']) ? $params['password2'] : '')),
        );
    }

    /**
     * @param array $params
     *
     * @return array
     */
    static public function extractNameservers($params)
    {
        $nameservers = array();

        for ($i = 1; $i <= 2; $i++) {
            if (!empty($params['ns' . $i])) {
                list($ns, ) = explode(' ', $params['ns' . $i], 2);
                $nameservers['ns' . $i] = $ns;
            }
        }

        return $nameservers;
    }
}
