
<?php
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
$localapi_user      = 'bpay';
$req_currency_code  = 'MDL';
$idno_customfieldid = '449';
$invoiceid          = intval($_GET['invoiceid']);
$action             = $_GET['action'];
switch ($action) {
    case 'getinvoice': {
        $invoice_data = localAPI('GetInvoice', array(
            'invoiceid' => $invoiceid
        ), $localapi_user);
        // var_dump($invoice_data);
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
                $items[$i]['amount'] = round($j['amount'] * $conversion_rate, 3);
                unset($items[$i]['id']);
                unset($items[$i]['relid']);
                $list                     = explode("\n", $items[$i]['description']);
                $items[$i]['description'] = $list[0];
            }
        }
        // var_dump($items);
        print json_encode(array(
            'status' => 'success',
            'invoice' => strtolower($invoice_data['status']),
            'idno' => get_customfield($client_data, $idno_customfieldid),
            'items' => $items
        ));
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
