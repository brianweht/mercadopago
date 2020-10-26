<?php
if($_GET['topic']=='payment')
{  $f= fopen('ipn.txt', 'wb');fwrite($f, $_REQUEST['id']);
   $payment = file_get_contents('https://api.mercadopago.com/v1/payments/'.$_REQUEST['id'].'?access_token=APP_USR-6317427424180639-042414-47e969706991d3a442922b0702a0da44-469485398');
   var_dump($payment);
}
?>