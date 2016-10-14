<?php
require 'bancard.class.php';

/*
* Procesar transaccion
*/
$bancard = new Bancard(true);
$bancard->set_response_mode("REDIRECT");
$bancard->set_item_description("Producto color blanco");
$response = $bancard->process(1356, 465000);
print $response;

