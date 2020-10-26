<?php
require('vendor/autoload.php');
MercadoPago\SDK::setAccessToken("APP_USR-6317427424180639-042414-47e969706991d3a442922b0702a0da44-469485398");
if($_GET['topic']=='payment')
{
   $payment = MercadoPago\Payment::find_by_id($_GET["id"]);
  // $f= fopen('ipn.txt', 'wb');
  // fwrite($f, json_encode($_REQUEST));
  echo json_encode($payment);
}
?>