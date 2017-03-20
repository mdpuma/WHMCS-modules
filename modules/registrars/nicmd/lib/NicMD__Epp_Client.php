<?php

class NicMD__Epp_Client
{
    private $socket = null;

    private $clTRID = 'undefined';

    private $isLoggeId = false;

    private $_username = '';

    private $_password = '';

    public function __construct($username = '', $password = '', $host = 'epp.nic.md', $port = 700)
    {
        $this->_connect($host, $port);

        $this->_username = $username;
        $this->_password = $password;
    }

    public function __destruct()
    {
        $this->_logout();
        $this->_disconnect();
    }

    public function request($xml)
    {
        $this->_login($this->_username, $this->_password);

        return $this->_write($xml);
    }

    private function _connect($host, $port = 700, $timeout = 1)
    {
        $this->clTRID = str_replace('.', '', round(microtime(true), 3));

        $context = stream_context_create(array('ssl' => array(
            'verify_peer' => false,
        )));
        $this->socket = stream_socket_client('tls://' . $host . ':' . $port, $errno, $errmsg, $timeout,
            STREAM_CLIENT_CONNECT, $context);

        if (!$this->socket) {
            throw new Exception('Cannot connect to server \'' . $host . '\': ' . $errmsg);
        }

        return $this->_read();
    }

    private function _login($login, $pwd)
    {
        if ($this->isLoggeId) {
            return true;
        }

        $this->_write('<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
  <command>
    <login>
      <clID>' . htmlspecialchars($login) . '</clID>
      <pw>' . htmlspecialchars($pwd) . '</pw>
      <options>
        <version>1.0</version>
        <lang>en</lang>
      </options>
      <svcs>
        <objURI>urn:ietf:params:xml:ns:obj1</objURI>
        <objURI>urn:ietf:params:xml:ns:obj2</objURI>
        <objURI>urn:ietf:params:xml:ns:obj3</objURI>
        <svcExtension>
          <extURI>http://custom/obj1ext-1.0</extURI>
        </svcExtension>
      </svcs>
    </login>
    <clTRID>{clTRID}</clTRID>
  </command>
</epp>');

        $this->isLoggeId = true;

        return true;
    }

    private function _logout()
    {
        if (!$this->isLoggeId) {
            return true;
        }

        $this->_write('<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
     xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
     xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
   <command>
     <logout/>
     <clTRID>{clTRID}</clTRID>
   </command>
</epp>');

        $this->isLoggeId = false;

        return true;
    }

    private function _read()
    {
        if (feof($this->socket)) {
            throw new Exception('Connection appears to have closed.');
        }

        $hdr = @fread($this->socket, 4);

        if (empty($hdr)) {
            throw new Exception('Error reading from server: ' . $php_errormsg);
        }

        $unpacked = unpack('N', $hdr);

        $xml = fread($this->socket, $unpacked[1] - 4);

        return $xml;
    }

    private function _write($xml)
    {
        $xml = strtr($xml, array(
            '{clTRID}' => $this->clTRID,
        ));

        @fwrite($this->socket, @pack('N', @strlen($xml) + 4) . $xml);

        try {
            $response = $this->_read();
        } catch (Exception $e) {
            $response = '';
        }

        /** @noinspection PhpUndefinedFunctionInspection */
        logModuleCall(__CLASS__, __METHOD__, $xml, $response, '', array('pass'));

        if (!empty($response)) {
            $response = new SimpleXMLElement($response);

            if (!in_array($response->response->result->attributes()->code, array(1000, 1500))) {
                throw new Exception($response->response->result->msg);
            }
        }

        return $response;
    }

    private function _disconnect()
    {
        return @fclose($this->socket);
    }
}
