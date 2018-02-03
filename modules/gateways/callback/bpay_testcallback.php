<?php

$signature = '';

$data = '<payment>
<type>1.2</type>
<order_id>104</order_id>
<amount>21.5</amount>
<valute>498</valute>
<comand>pay</comand>
<advanced1></advanced1>
<advanced2></advanced2>
<transid>104-bpay</transid>
<receipt>108757114530315</receipt>
<time>20111007 134928</time>
<test>0</test>
</payment>';

$key = md5(md5($data) . md5($signature));

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://innovahosting.net/modules/gateways/callback/bpay.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,
    http_build_query(
        array(
            'data' => base64_encode($data),
            'key' => $key,
        )
    )
);
$response = curl_exec($ch);
curl_close($ch);