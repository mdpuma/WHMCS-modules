<?php

class nicmdEppClient {
	private $connection=null;
	private $user = null;
	private $pass = null;
	private $is_logged = false;
	private $debug = false;
	private $dialog = [];
	
	public $contact_attributes = array (
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
	
	function __construct($host, $port=700, $user, $pass, $debug=0) {
		$this->user=$user;
		$this->pass=$pass;
		$this->debug=(bool)$debug;
		
		$this->connection = stream_socket_client('tls://'.$host.':'.$port, $errno, $errstr, 60, STREAM_CLIENT_CONNECT, stream_context_create( array( 'ssl' => array( 'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true ) ) ));
		return $this->connection;
	}
	
	public function login($epp_user, $epp_pass) {
		$i = 0;
		$result = '';
		
		if(empty($epp_user)) {
			$epp_result['error'] = 'Empty epp_user';
			return $epp_result;
		}
		if(empty($epp_pass)) {
			$epp_result['error'] = 'Empty epp_pass';
			return $epp_result;
		}
		
		while (!feof($this->connection)) {
			$i++;
			$result .= fgetc($this->connection);
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
							<clID>'.$epp_user.'</clID>
							<pw>'.$epp_pass.'</pw>
							<options/>
							<svcs/>
						</login>
						<clTRID/>
					</command>
					</epp>';
			$result = $this->_epp_send($xml);
			if (!strstr ($result, '<result code="1000">')) {
				$epp_result['result'] = 0;
				if (preg_match("/<msg>(.+)<\/msg>/",$result,$msg)) {
					$epp_result['error'] = 'Error: '.$msg[0];
				} else {
					$epp_result['error'] = 'Error!';
				}
			} else {
				$this->is_logged=true;
				$epp_result['result'] = 'success';
			}
		} else {
			$epp_result['result'] = 0;
			$epp_result['error'] = 'Error!';
		}
		return $epp_result;
	}
	
	public function logout() {
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
		$this->_epp_send($xml);
		fclose($this->connection);
		return true;
	}
	
	private function molddata_eppGetResultCode ($result) {
		$start_pos = strpos ($result, '<result code="');
		return substr ($result, $start_pos + 14, 4);
	}
	
	private function _epp_send($xml) {
		$result="";
		$len = strlen ($xml);
		$value = $len + 4;
		$b3 = $value % 256;
		$value = ($value - $b3) / 256;
		$b2 = $value % 256;
		$value = ($value - $b2) / 256;
		$b1 = $value % 256;
		$value = ($value - $b1) / 256;
		$b0 = $value % 256;
		if (!@fwrite ($this->connection, @chr ($b0) . @chr ($b1) . @chr ($b2) . @chr ($b3), 4)){
			return $result;
		}
		if (!@fwrite ($this->connection, $xml)){
			return $result;
		}
		$i = 0;
		while (!feof ($this->connection)) {
			$i++;
			$result .= fgetc($this->connection);
			if(strpos($result,'</epp>')) {
				break;
			}
			if ($i > 500000) {
				break;
			}
		}
		$result = preg_replace("/.+<\?xml/","<?xml",$result);
		if($this->debug) {
			echo "===\nSENT:\n\n$xml\n\n\n===\n";
			echo "===\nRECEIVED:\n\n$result\n\n\n===\n";
		}
		$this->dialog[] = array(
			'request'=> $xml,
			'response' => $result
		);
		return $result;
	}
	
	public function get_dialog() {
		return $this->dialog;
	}
	
	public function registerDomain($domain, $reg_period, $contacts, $nameservers) {
		if($this->is_logged==false) {
			return false;
		}
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
				<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
					xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
					xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
				<command>
					<create>
						<domain:create xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
							<domain:account>'.$this->user.'</domain:account>
							<domain:account_pw>'.$this->pass.'</domain:account_pw>
							<domain:name years="'.$reg_period.'">'.$domain.'</domain:name>

							<domain:org_name>'.$contacts['org_name'].'</domain:org_name>

							<domain:adm_firstname>'.$contacts['adm_firstname'].'</domain:adm_firstname>
							<domain:adm_lastname>'.$contacts['adm_lastname'].'</domain:adm_lastname>
							<domain:adm_str>'.$contacts['adm_str'].'</domain:adm_str>
							<domain:adm_city>'.$contacts['adm_city'].'</domain:adm_city>
							<domain:adm_postc>'.$contacts['adm_postc'].'</domain:adm_postc>
							<domain:adm_country>'.$contacts['adm_country'].'</domain:adm_country>
							<domain:adm_phone>'.$contacts['adm_phone'].'</domain:adm_phone>
							<domain:adm_email>'.$contacts['adm_email'].'</domain:adm_email>
							<domain:adm_state>'.$contacts['adm_state'].'</domain:adm_state>

							<domain:teh_firstname>'.$contacts['teh_firstname'].'</domain:teh_firstname>
							<domain:teh_lastname>'.$contacts['teh_lastname'].'</domain:teh_lastname>
							<domain:teh_str>'.$contacts['teh_str'].'</domain:teh_str>
							<domain:teh_city>'.$contacts['teh_city'].'</domain:teh_city>
							<domain:teh_postc>'.$contacts['teh_postc'].'</domain:teh_postc>
							<domain:teh_country>'.$contacts['teh_country'].'</domain:teh_country>
							<domain:teh_phone>'.$contacts['teh_phone'].'</domain:teh_phone>
							<domain:teh_email>'.$contacts['teh_email'].'</domain:teh_email>
							<domain:teh_state>'.$contacts['teh_state'].'</domain:teh_state>

							<domain:bil_firstname>'.$contacts['bil_firstname'].'</domain:bil_firstname>
							<domain:bil_lastname>'.$contacts['bil_lastname'].'</domain:bil_lastname>
							<domain:bil_str>'.$contacts['bil_str'].'</domain:bil_str>
							<domain:bil_city>'.$contacts['bil_city'].'</domain:bil_city>
							<domain:bil_postc>'.$contacts['bil_postc'].'</domain:bil_postc>
							<domain:bil_country>'.$contacts['bil_country'].'</domain:bil_country>
							<domain:bil_phone>'.$contacts['bil_phone'].'</domain:bil_phone>
							<domain:bil_email>'.$contacts['bil_email'].'</domain:bil_email>
							<domain:bil_state>'.$contacts['bil_state'].'</domain:bil_state>

							<domain:primNSname>'.$nameservers['ns1'].'</domain:primNSname>
							<domain:secNSname>'.$nameservers['ns2'].'</domain:secNSname>
						</domain:create>
					</create>
					<clTRID>'.rand(10000,99999).'</clTRID>
				</command>
				</epp>';

		$result = $this->_epp_send($xml);
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
		return $epp_result;
	}
	
	public function renewDomain($domain, $regperiod, $domainExpDate='2018-01-01') {
		if($this->is_logged==false) {
			return false;
		}
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
				<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
					xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
					xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
				<command>
					<renew>
						<domain:renew xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
							<domain:account>'.$this->user.'</domain:account>
							<domain:account_pw>'.$this->pass.'</domain:account_pw>
							<domain:name curexp="'.$domainExpDate.'" years="'.$regperiod.'">'.$domain.'</domain:name>
						</domain:renew>
					</renew>
					<clTRID>'.rand(10000,99999).'</clTRID>
				</command>
				</epp>';
		
		$result = $this->_epp_send($xml);
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
		return $epp_result;
	}
	
	public function getNameservers($domain, $regperiod, $domainExpDate='2018-01-01') {
		if($this->is_logged==false) {
			return false;
		}
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
				<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
					xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
					xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
				<command>
					<info>
						<domain:info xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
							<domain:account>'.$this->user.'</domain:account>
							<domain:account_pw>'.$this->pass.'</domain:account_pw>
							<domain:name curexp="'.$domainExpDate.'" years="'.$regperiod.'">'.$domain.'</domain:name>
						</domain:info>
					</info>
					<clTRID>'.rand(10000,99999).'</clTRID>
				</command>
				</epp>';
		
		$result = $this->_epp_send($xml);
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
		return $epp_result;
	}
	public function saveNameservers($domain, $nameservers=array('ns1', 'ns2')) {
		if($this->is_logged==false) {
			return false;
		}
		$ns1 = explode(" ",$nameservers['ns1']);
		$ns2 = explode(" ",$nameservers['ns2']);
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
				<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
					xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
					xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
				<command>
					<update>
						<domain:update xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
							<domain:account>'.$this->user.'</domain:account>
							<domain:account_pw>'.$this->pass.'</domain:account_pw>
							<domain:name>'.$domain.'</domain:name>

							<domain:primNSname>'.$ns1[0].'</domain:primNSname>
							<domain:primNSip4>'.@$ns1[1].'</domain:primNSip4>
							<domain:primNSip6>'.@$ns1[2].'</domain:primNSip6>
							<domain:secNSname>'.$ns2[0].'</domain:secNSname>
							<domain:secNSip4>'.@$ns2[1].'</domain:secNSip4>
							<domain:secNSip6>'.@$ns2[2].'</domain:secNSip6>
						</domain:update>
					</update>
					<clTRID>'.rand(10000,99999).'</clTRID>
				</command>
				</epp>';
		
		$result = $this->_epp_send($xml);
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
		return $epp_result;
	}

	public function getContactDetails($domain) {
		if($this->is_logged==false) {
			return false;
		}
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
				<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
					xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
					xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
				<command>
					<info>
						<domain:info xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
							<domain:account>'.$this->user.'</domain:account>
							<domain:account_pw>'.$this->pass.'</domain:account_pw>
							<domain:name>'.$domain.'</domain:name>
						</domain:info>
					</info>
					<clTRID>'.rand(10000,99999).'</clTRID>
				</command>
				</epp>';

		$result = $this->_epp_send($xml);
			
		if (!strstr ($result, '<result code="1000">')) {
			if (preg_match("/<msg>(.+)<\/msg>/",$result,$msg)) {
				$epp_result['error'] = 'Error: '.$msg[1];
			} else {
				$epp_result['error'] = 'Error!';
			}
		} else {
			if(preg_match("/<domain:name res=\"0\">(.+)<\/domain:name>/",$result,$msg)) {
				$epp_result['error'] = 'Error: '.$msg[1];
			} elseif (strstr($result,'domain:name res="0"')) {
				$epp_result['error'] = 'Error: domain not exists';
			} elseif (strstr($result,'domain:name res="2"')) {
				$epp_result['error'] = 'Error: domain not in account';
			} else {
				$epp_result= array();
				$epp_result['Registrant'] = array();
				$epp_result['Registrant']['Organization Name'] = "";
				
				$epp_info = [];
				foreach($this->contact_attributes as $p) {
					if (preg_match("/<domain:$p>(.*)<\/domain:$p>/",$result,$exp)) {
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
					'Domain' => array(
						'Registration Date' => $epp_info['reg_date'],
						'Expiration Date' => $epp_info['exp_date'],
						'Renew Date' => $epp_info['ren_date'],
						'Domain Name' => $domain,
					)
				);
			}
		}
		return $epp_result;
	}
	
	public function saveContactDetails($domain, $contacts) {
		if($this->is_logged==false) {
			return false;
		}
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
				<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
					xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
					xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
				<command>
					<update>
						<domain:update xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
							<domain:account>'.$this->user.'</domain:account>
							<domain:account_pw>'.$this->pass.'</domain:account_pw>
							<domain:name>'.$domain.'</domain:name>

							<domain:org_name>'.$contacts['Administrative']['Company Name'].'</domain:org_name>

							<domain:adm_firstname>'.$contacts['Administrative']['First Name'].'</domain:adm_firstname>
							<domain:adm_lastname>'.$contacts['Administrative']['Last Name'].'</domain:adm_lastname>
							<domain:adm_str>'.$contacts['Administrative']['Address'].'</domain:adm_str>
							<domain:adm_city>'.$contacts['Administrative']['City'].'</domain:adm_city>
							<domain:adm_postc>'.$contacts['Administrative']['Postcode'].'</domain:adm_postc>
							<domain:adm_country>'.$contacts['Administrative']['Country'].'</domain:adm_country>
							<domain:adm_phone>'.$contacts['Administrative']['Phone'].'</domain:adm_phone>
							<domain:adm_email>'.$contacts['Administrative']['Email'].'</domain:adm_email>
							<domain:adm_state>'.$contacts['Administrative']['State'].'</domain:adm_state>

							<domain:teh_firstname>'.$contacts['Technical']['First Name'].'</domain:teh_firstname>
							<domain:teh_lastname>'.$contacts['Technical']['Last Name'].'</domain:teh_lastname>
							<domain:teh_str>'.$contacts['Technical']['Address'].'</domain:teh_str>
							<domain:teh_city>'.$contacts['Technical']['City'].'</domain:teh_city>
							<domain:teh_postc>'.$contacts['Technical']['Postcode'].'</domain:teh_postc>
							<domain:teh_country>'.$contacts['Technical']['Country'].'</domain:teh_country>
							<domain:teh_phone>'.$contacts['Technical']['Phone'].'</domain:teh_phone>
							<domain:teh_email>'.$contacts['Technical']['Email'].'</domain:teh_email>
							<domain:teh_state>'.$contacts['Technical']['State'].'</domain:teh_state>

							<domain:bil_firstname>'.$contacts['Billing']['First Name'].'</domain:bil_firstname>
							<domain:bil_lastname>'.$contacts['Billing']['Last Name'].'</domain:bil_lastname>
							<domain:bil_str>'.$contacts['Billing']['Address'].'</domain:bil_str>
							<domain:bil_city>'.$contacts['Billing']['City'].'</domain:bil_city>
							<domain:bil_postc>'.$contacts['Billing']['Postcode'].'</domain:bil_postc>
							<domain:bil_country>'.$contacts['Billing']['Country'].'</domain:bil_country>
							<domain:bil_phone>'.$contacts['Billing']['Phone'].'</domain:bil_phone>
							<domain:bil_email>'.$contacts['Billing']['Email'].'</domain:bil_email>
							<domain:bil_state>'.$contacts['Billing']['State'].'</domain:bil_state>
						</domain:update>
					</update>
					<clTRID>'.rand(10000,99999).'</clTRID>
				</command>
				</epp>';
		
		$result = $this->_epp_send($xml);
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
		return $epp_result;
	}
}
