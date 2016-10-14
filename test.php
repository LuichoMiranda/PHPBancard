<?php
require 'bancard.class.php';
$bancard = new Bancard();
$response = $bancard->process(1356, 465000);
print $response;