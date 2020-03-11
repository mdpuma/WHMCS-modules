<?php

use WHMCS\Database\Capsule;

//Do not run this file without WHMCS
!defined('ROOTDIR') ? die('Cannot run directly!') : 0;

function molddata_config() {
    $configarray = array(
        "name" => "Molddata domain listing",
        "description" => "",
        "version" => "0.01",
        "author" => "",
        "fields" => array()
    );
    return $configarray;
}

function molddata_activate() {
    # Create Custom DB Table
    $query = "CREATE TABLE IF NOT EXISTS `mod_molddata_domain_log` (
  `id` int(11) NOT NULL,
  `domainid` int(11) NOT NULL,
  `action` varchar(64) NOT NULL,
  `ns` varchar(128) NOT NULL,
  `additional` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    Capsule::statement($query);
//     Capsule::statement("ALTER TABLE `mod_molddata_domain_log` ADD PRIMARY KEY (`id`), ADD KEY `domainid` (`domainid`);");
//     Capsule::statement("ALTER TABLE `mod_molddata_domain_log` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
    
    # Return Result
    return array(
        'status' => 'success'
    );
}

function molddata_deactivate() {
    # Remove Custom DB Table
//     $query = "TRUNCATE TABLE `mod_molddata_domain_log`";
//     Capsule::statement($query);
    
    # Return Result
    return array(
        'status' => 'success'
    );
}

function molddata_output($vars) {
    $modulelink = $vars['modulelink'];
    $version    = $vars['version'];
    $option1    = $vars['option1'];
    $option2    = $vars['option2'];
    $option3    = $vars['option3'];
    $option4    = $vars['option4'];
    $option5    = $vars['option5'];
    $option6    = $vars['option6'];
    $LANG       = $vars['_lang'];
    
	echo <<<EOF
	<div class="lic_linksbar"><a href="addonmodules.php?module=molddata&amp;page=logs">Logs</a></div>
    <div id="main">
EOF;
	if(!isset($_GET['page'])) $_GET['page'] = 'logs';
	switch($_GET['page']) {
		case 'logs':
			show_logs_table();
			break;
		case 'details':
			show_electricity_table();
			break;
	}
    echo '</div>';
}

function show_logs_table() {
	echo <<<EOF
	<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
		<tr>
			<th><a href="?module=molddata&amp;page=logs&amp;orderby=id">ID</a></th>
			<th><a href="?module=molddata&amp;page=logs&amp;orderby=domain">Domain</a></th>
			<th><a href="?module=molddata&amp;page=logs&amp;orderby=action">Action</a></th>
			<th>Nameservers</th>
			<th>Additional</th>
			<th>Date/Time</th>
		</tr>
EOF;
    
        
    $result = Capsule::table('mod_molddata_domain_log')->select('*')->join('tbldomains', 'mod_molddata_domain_log.domainid', '=', 'tbldomains.id')->get();
    $result = molddata_objectToArray($result);
    
    foreach ($result as $l) {
        echo '<tr>
            <td><a href="?module=molddata&amp;page=details&amp;actionid=' . $l['id'] . '">'.$l['id'].'</a></td>
            <td><a href="clientsdomains.php?id=' . $l['domainid'] . '">'.$l['domain'].'</a> (<a href="?module=molddata&amp;page=details&amp;domainid=' . $l['domainid'] . '">filter</a>)</td>
            <td>'.$l['action'].'</td>
			<td>'.preg_replace("/\n/", "<br>", $l['ns']).'</td>
		    <td><textarea rows="2" style="width: 100%;">'.$l['additional'].'</textarea></td>
		    <td>'.date("d.m.Y H:m", $l['time']).'</td>
        </tr>';
    }
    
//     if (isset($_GET['orderby'])) {
//         switch ($_GET['orderby']) {
//             case 'id':
//                 usort($products_info, function($a, $b) {
//                     return $a['id'] - $b['id'];
//                 });
//                 break;
//             case 'domain':
//                 usort($products_info, function($a, $b) {
//                     return strcmp($a['domain'], $b['domain']);
//                 });
//                 break;
//             case 'interface':
//                 usort($products_info, function($a, $b) {
//                     return strcmp($a['value'], $b['value']);
//                 });
//                 break;
//             case 'switch':
//                 usort($products_info, function($a, $b) {
//                     return strcmp($a['servername'], $b['servername']);
//                 });
//                 break;
//             case 'bwmonth':
//                 usort($products_info, function($a, $b) {
//                     return $a['bwusage_month']['bytes'] - $b['bwusage_month']['bytes'];
//                 });
//                 break;
//             case 'bw31d':
//                 usort($products_info, function($a, $b) {
//                     return $a['bwusage_31d']['bytes'] - $b['bwusage_31d']['bytes'];
//                 });
//                 break;
//             case 'status':
// 				usort($products_info, function($a, $b) {
// 					return strcmp($a['domainstatus'], $b['domainstatus']);
// 				});
// 				break;
//         }
//     }
//     
//     foreach ($products_info as $product) {
//         echo '<tr>
//             <td><a href="clientsservices.php?userid=' . $product['userid'] . '&id=' . $product['id'] . '">' . $product['id'] . '</a></td>
//             <td><a href="clientsservices.php?userid=' . $product['userid'] . '&id=' . $product['id'] . '">' . $product['domain'] . '</a></td>
//             <td>' . $product['value'] . '</td>
//             <td>' . $product['servername'] . '</td>
//             <td style="text-align: right">'.$product['bwusage_month']['from'].'-'.$product['bwusage_month']['to'].' ' . print_bwusage($product['bwusage_month']['bytes']) . '</td>
//             <td style="text-align: right">' . print_bwusage($product['bwusage_31d']['bytes']) . '</td>
//             <td>' . $product['nextduedate'] . '</td>
//             <td>' . $product['domainstatus'] . '</td>
//         </tr>';
//     }
    echo '</table>';
}

function molddata_clientarea($vars) {
    return array();
}

function molddata_objectToArray($d) {
    if (is_object($d)) {
        // Gets the properties of the given object
        // with get_object_vars function
        $d = get_object_vars($d);
    }
    if (is_array($d)) {
        /*
         * Return array converted to object
         * Using __FUNCTION__ (Magic constant)
         * for recursive call
         */
        return array_map(__FUNCTION__, $d);
    } else {
        // Return array
        return $d;
    }
}
