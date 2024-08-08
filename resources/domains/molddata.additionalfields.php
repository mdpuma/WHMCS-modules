<?php

//.MD
$additionaldomainfields['.md'][] = [
    'Name' => 'Entity Type',
    'Type' => 'dropdown',
    'Options' => 'organization|Persoana juridica,individual|Persoana fizica',
    'Default' => 'individual|Persoana fizica',
	'Required' => true,
];
$additionaldomainfields['.md'][] = [
    'Name' => 'IDNO',
	"DisplayName" => "IDNO/IDNP",
    'Type' => 'textbox',
    'Default' => '',
    'Required' => true,
];
?>