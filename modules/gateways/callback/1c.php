<?php
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
$localapi_user      = '1c_module';
$req_currency_code  = 'MDL';
$idno_customfieldid = '449';
$tax_rate           = '20'; // add x percent
$action             = $_GET['action'];
$allowed_ips        = array('185.181.228.28', '89.28.42.226');
foreach($allowed_ips as $allow) {
    if($_SERVER['REMOTE_ADDR'] == $allow) {
        $permit=1;
    }
}
if($permit==0) {
    die("Not allowed, get out");
}
switch ($action) {
    case 'getinvoice': {
        $invoice_data = localAPI('GetInvoice', array(
            'invoiceid' => intval($_GET['invoiceid'])
        ), $localapi_user);
        if ($invoice_data['status'] === 'error') {
            print json_encode(array(
                'status' => 'error',
                'reason' => 'inexistent invoice'
            ));
            exit;
        }
        //         if ($invoice_data['status'] === 'Paid') {
        //             print json_encode(array(
        //                 'status' => 'error',
        //                 'reason' => 'already paid invoice'
        //             ));
        //             exit;
        //         }
        $items       = $invoice_data['items']['item'];
        $client_data = localAPI('GetClientsDetails', array(
            'clientid' => $invoice_data['userid']
        ), $localapi_user);
        $cur_data    = localAPI('GetCurrencies', $localapi_user);
        $cur_data2   = '';
        foreach ($cur_data['currencies']['currency'] as $i) {
            $cur_data2[$i['id']] = $i;
            if (strcmp($i['code'], $req_currency_code) == 0) {
                $req_currency_id = $i['id'];
            }
            if ($i['rate'] == 1) {
                $base_currency_id = $i['id'];
            }
        }
        if ($cur_data2[$client_data['currency']] != $req_currency_id) {
            // calculate conversion_rate for double conversion
            // example: if base currency is EUR, customer have USD, and we require MDL
            if ($cur_data2[$client_data['currency']] != $base_currency_id) {
                $conversion_rate = ($cur_data2[$req_currency_id]['rate'] / $cur_data2[$client_data['currency']]['rate']);
                // calculate coversion_rate with single conversion
            } else {
                $conversion_rate = $cur_data2[$req_currency_id]['rate'];
            }
        } else {
            $conversion_rate = 1;
        }
        if ($conversion_rate != 1) {
            foreach ($items as $i => $j) {
                $items[$i]['amount']      = round($j['amount'] * $conversion_rate, 3);
                $list                     = explode("\n", $items[$i]['description']);
                $items[$i]['description'] = $list[0];
                if ($j['taxed'] == 1) {
                    $items[$i]['amount'] += $items[$i]['amount'] * $tax_rate / 100;
                }
                unset($items[$i]['id']);
                unset($items[$i]['relid']);
                unset($items[$i]['taxed']);
            }
        }
        // var_dump($items);
        print json_encode(array(
            'status' => 'success',
            'invoice' => strtolower($invoice_data['status']),
            'idno' => get_customfield($client_data, $idno_customfieldid),
            'items' => $items
        ));
        break;
    }
    case 'payment': {
        // invoiceid
        // sum
//         Техническая информация:
//         - Запрос на сайт передаётся методом «GET»
//         - Параметр «action» = «payment»
//         - Параметр «invoiceid» = ID инвойса
//         - Параметр «sum» = Сумма платежа
// number - id transfer din 1c
// date - data transfer din 1c
        $_GET['date_unix'] = strtotime($_GET['date']);
        $_GET['sum'] = preg_replace('/[^\d,.]+/', '', $_GET['sum']);
        $_GET['sum'] = round($_GET['sum'], 2);
        
        $postData = array(
            'invoiceid' => intval($_GET['invoiceid']),
        );
        $results = localAPI('GetInvoice', $postData, $localapi_user);
        
        $postData = array(
            'paymentmethod' => 'banktransfer',
            'transid' => 'Transfer bancar '.$_GET['number'].' din data '.$_GET['date'],
            'description' => 'from callback 1C',
            'amountin' => $_GET['sum'],
            'invoiceid' => intval($_GET['invoiceid']),
            'userid' => $results['userid'],
            'fees' => '0',
            'rate' => '1.00000',
        );
        $results = localAPI('AddTransaction', $postData, $localapi_user);
        $results['status'] = $results['result'];
        print json_encode($results);
        break;
    }
    case 'setstatus': {
//         Техническая информация:
//         - Запрос на сайт передаётся методом «GET»
//         - Параметр «action» = «setstatus»
//         - Параметр «invoiceid» = ID инвойса
//         - Параметр «status» = «pending»
        $postData = array(
            'invoiceid' => intval($_GET['invoiceid']),
            'status' => 'Payment Pending',
            'paymentmethod' => 'banktransfer',
        );

        $results = localAPI('UpdateInvoice', $postData, $localapi_user);
        $results['status'] = $results['result'];
        print json_encode($results);
        break;
    }
}
function get_customfield($client_array, $id) {
    foreach ($client_array['customfields'] as $j) {
        if ($j['id'] == $id) {
            return $j['value'];
        }
    }
    return;
}
?>