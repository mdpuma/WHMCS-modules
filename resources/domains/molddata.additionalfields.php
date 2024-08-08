<?php

//.MD
$additionaldomainfields['.md'][] = [
    'Name' => 'Entity Type',
	"DisplayName" => "Tip entitate",
    'Type' => 'radio',
    'Options' => 'organization|Persoana juridica,individual|Persoana fizica',
    'Default' => 'individual|Persoana fizica',
	"Description" => "Rugam sa fie selectat tipul entitatii ce va fi proprietar pe domeniu.",
	'Required' => true,
];
$additionaldomainfields['.md'][] = [
    'Name' => 'IDNO',
	"DisplayName" => "IDNO/IDNP",
    'Type' => 'text',
    'Default' => '',
	'Size' => '60',
    'Required' => true,
];
?>