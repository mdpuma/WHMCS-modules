<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

$pmonth = str_pad((int)$month, 2, "0", STR_PAD_LEFT);

$reportdata["title"] = "Income (in base currency) by Product for ".$months[(int)$month]." ".$year;
$reportdata["description"] = "";

$reportdata["tableheadings"] = array("Product Name", "Units", "Monthly", "Quarterly", "Semi-Annual", "Annual", "Biennial", "Triennial", "Mediu anual");

$products = $addons = array();

$base_currency = $currencyid;

$cresult = select_query('tblcurrencies',"","");
while ($cdata = mysql_fetch_array($cresult)) {
    $currencyid = $cdata['id'];
    $rate = $cdata['rate'];

    # Loop Through Products
    $result = full_query("SELECT packageid, COUNT(*), SUM(amount), billingcycle FROM tblhosting INNER JOIN tblclients ON tblclients.id = tblhosting.userid WHERE tblclients.currency = ".(int)$currencyid." AND ( domainstatus='Active' OR domainstatus='Suspended' ) GROUP BY tblhosting.packageid,billingcycle");
    while ($data = mysql_fetch_array($result)) {
		$billingcycles = array("Monthly", "Quarterly", "Semi-Annually", "Annually", "Biennially", "Triennially");
		$billingcycle = $data[3];
		if(in_array($billingcycle, $billingcycles)) {
			$products[$data[0]][$billingcycle] += $data[2]/$rate;
		}
        $products[$data[0]]['unitssold'] += $data[1];
    }
    # Loop Through Addons
    $result = full_query("SELECT addonid as packageid, COUNT(*), SUM(recurring) as amount, billingcycle FROM tblhostingaddons INNER JOIN tblclients ON tblclients.id = tblhostingaddons.userid WHERE tblclients.currency = ".(int)$currencyid." AND ( tblhostingaddons.status='Active' OR tblhostingaddons.status='Suspended' ) GROUP BY tblhostingaddons.addonid,billingcycle");
	while ($data = mysql_fetch_array($result)) {
		$billingcycles = array("Monthly", "Quarterly", "Semi-Annually", "Annually", "Biennially", "Triennially");
		$billingcycle = $data[3];
		if(in_array($billingcycle, $billingcycles)) {
			$addons[$data[0]][$billingcycle] += $data[2]/$rate;
		}
        $addons[$data[0]]['unitssold'] += $data[1];
    }
}

foreach($products as $id => $p) {
	$products[$id]['total'] = 0;
	
	$billingcycle = array("Monthly", "Quarterly", "Semi-Annually", "Annually", "Biennially", "Triennially");
	foreach($billingcycle as $b) {
		switch($b) {
			case 'Monthly':       $products[$id]['total'] += $p[$b]*12; break;
			case 'Quarterly':     $products[$id]['total'] += $p[$b]*4; break;
			case 'Semi-Annually': $products[$id]['total'] += $p[$b]*2; break;
			case 'Annually':      $products[$id]['total'] += $p[$b]*1; break;
			case 'Biennially':    $products[$id]['total'] += $p[$b]/2; break;
			case 'Triennially':   $products[$id]['total'] += $p[$b]/3; break;
		}
	}
}


foreach($addons as $id => $p) {
	$addons[$id]['total'] = 0;
	
	$billingcycle = array("Monthly", "Quarterly", "Semi-Annually", "Annually", "Biennially", "Triennially");
	foreach($billingcycle as $b) {
		switch($b) {
			case 'Monthly':       $addons[$id]['total'] += $p[$b]*12; break;
			case 'Quarterly':     $addons[$id]['total'] += $p[$b]*4; break;
			case 'Semi-Annually': $addons[$id]['total'] += $p[$b]*2; break;
			case 'Annually':      $addons[$id]['total'] += $p[$b]*1; break;
			case 'Biennially':    $addons[$id]['total'] += $p[$b]/2; break;
			case 'Triennially':   $addons[$id]['total'] += $p[$b]/3; break;
		}
	}
}

$currencyid = $base_currency;
$total = 0;
$itemtotal = 0;
$firstdone = false;
$result = full_query("SELECT tblproducts.id,tblproducts.name,tblproductgroups.name AS groupname FROM tblproducts INNER JOIN tblproductgroups ON tblproducts.gid=tblproductgroups.id ORDER by `tblproductgroups`.`order` ASC,`tblproducts`.`order`");
while($data = mysql_fetch_array($result)) {
    $pid = $data["id"];
    $group = $data["groupname"];
    $prodname = $data["name"];

    if ($group!=$prevgroup) {
        $total += $itemtotal;
        if ($firstdone) {
            $reportdata["tablevalues"][] = array('','<strong>Sub-Total</strong>','','','','','','','<strong>'.formatCurrency($itemtotal).'</strong>');
            $chartdata['rows'][] = array('c'=>array(array('v'=>$prevgroup),array('v'=>$itemtotal,'f'=>formatCurrency($itemtotal))));
        }
        $reportdata["tablevalues"][] = array("**<strong>$group</strong>");
        $itemtotal = 0;
    }

    $amount = $products[$pid]["total"];
    $number = $products[$pid]["unitssold"];

    $itemtotal += $amount;

    if (!$amount) $amount="0.00";
    if (!$number) $number="0";
    $amount = formatCurrency($amount);

    $monthly = formatCurrency($products[$pid]['Monthly']);
    $quarterly = formatCurrency($products[$pid]['Quarterly']);
    $semianu = formatCurrency($products[$pid]['Semi-Annually']);
    $anually = formatCurrency($products[$pid]['Annually']);
    $bienally = formatCurrency($products[$pid]['Biennially']);
    $trienally = formatCurrency($products[$pid]['Triennially']);
    
    $reportdata["tablevalues"][] = array($prodname,$number, $monthly, $quarterly, $semianu, $anually, $bienally, $trienally, $amount);

    $prevgroup = $group;
    $firstdone = true;

}

$total += $itemtotal;
$reportdata["tablevalues"][] = array('','<strong>Sub-Total</strong>','','','','','','','<strong>'.formatCurrency($itemtotal).'</strong>');
$chartdata['rows'][] = array('c'=>array(array('v'=>$group),array('v'=>$itemtotal,'f'=>formatCurrency($itemtotal))));



$reportdata["tablevalues"][] = array("**<strong>Addons</strong>");
$itemtotal = 0;

$result = full_query("SELECT id,name from tbladdons order by name ASC");
while($data = mysql_fetch_array($result)) {

    $addonid = $data["id"];
    $prodname = $data["name"];

    $amount = $addons[$addonid]["total"];
    $number = $addons[$addonid]["unitssold"];

    $itemtotal += $amount;

    if (!$amount) $amount="0.00";
    if (!$number) $number="0";
    $amount = formatCurrency($amount);

    $monthly = formatCurrency($addons[$addonid]['Monthly']);
    $quarterly = formatCurrency($addons[$addonid]['Quarterly']);
    $semianu = formatCurrency($addons[$addonid]['Semi-Annually']);
    $anually = formatCurrency($addons[$addonid]['Annually']);
    $bienally = formatCurrency($addons[$addonid]['Biennially']);
    $trienally = formatCurrency($addons[$addonid]['Triennially']);
    
    $reportdata["tablevalues"][] = array($prodname,$number, $monthly, $quarterly, $semianu, $anually, $bienally, $trienally, $amount);

    $prevgroup = $group;

}

// $itemtotal += $addons[0]["total"];
// $number = $addons[0]["unitssold"];
// $amount = $addons[0]["amount"];
// if (!$amount) $amount="0.00";
// if (!$number) $number="0";
// $reportdata["tablevalues"][] = array('Miscellaneous Custom Addons',$number,formatCurrency($amount));

$total += $itemtotal;
$reportdata["tablevalues"][] = array('','<strong>Sub-Total</strong>','','','','','','','<strong>'.formatCurrency($itemtotal).'</strong>');
$chartdata['rows'][] = array('c'=>array(array('v'=>"Addons"),array('v'=>$itemtotal,'f'=>formatCurrency($itemtotal))));

// $itemtotal = 0;
// $reportdata["tablevalues"][] = array("**<strong>Miscellaneous</strong>");
// 
// $base_currency = $currencyid;
// 
// $ndx = count($reportdata["tablevalues"]);
// $cresult = select_query('tblcurrencies',"","");
// while ($cdata = mysql_fetch_array($cresult)) {
//     $currencyid = $cdata['id'];
//     $rate = $cdata['rate'];
//     $sql = "SELECT COUNT(*), SUM(tblinvoiceitems.amount)
//             FROM tblinvoiceitems
//             INNER JOIN tblinvoices ON tblinvoices.id=tblinvoiceitems.invoiceid
//             INNER JOIN tblclients ON tblclients.id=tblinvoices.userid
//             WHERE tblinvoices.datepaid LIKE '" . (int)$year . "-" . $pmonth . "-%' AND tblinvoiceitems.type='Item' AND currency=" . (int)$currencyid;
//     $result = full_query($sql);
//     $data = mysql_fetch_array($result);
//     $itemtotal += $data[1]/$rate;
//     $number = $data[0];
//     $amount = $data[1]/$rate;
//     if (!$amount) $amount="0.00";
//     if (!$number) $number="0";
//     $reportdata["tablevalues"][$ndx] = array('Billable Items',$number,formatCurrency($amount));
// 
//     $result = full_query("SELECT COUNT(*),SUM(tblinvoiceitems.amount) FROM tblinvoiceitems INNER JOIN tblinvoices ON tblinvoices.id=tblinvoiceitems.invoiceid INNER JOIN tblclients ON tblclients.id=tblinvoices.userid WHERE tblinvoices.datepaid LIKE '".(int)$year."-".$pmonth."-%' AND tblinvoiceitems.type='' AND currency='$currencyid'");
//     $data = mysql_fetch_array($result);
//     $itemtotal += $data[1]/$rate;
//     $reportdata["tablevalues"][$ndx+1] = array('Custom Invoice Line Items',$data[0],formatCurrency($data[1]/$rate));
// }
// $currencyid = $base_currency;
// 
// $total += $itemtotal;
// $reportdata["tablevalues"][] = array('','<strong>Sub-Total</strong>','<strong>'.formatCurrency($itemtotal).'</strong>');
// $chartdata['rows'][] = array('c'=>array(array('v'=>"Miscellaneous"),array('v'=>$itemtotal,'f'=>formatCurrency($itemtotal))));

$total = formatCurrency($total);

$chartdata['cols'][] = array('label'=>'Days Range','type'=>'string');
$chartdata['cols'][] = array('label'=>'Value','type'=>'number');

$args = array();
$args['legendpos'] = 'right';

$reportdata["footertext"] = $chart->drawChart('Pie',$chartdata,$args,'300px');

$reportdata["monthspagination"] = false;
