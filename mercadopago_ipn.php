<?php
if($_GET['topic']=='payment')
   $payment = file_get_contents('https://api.mercadopago.com/v1/payments/'.$_REQUEST['id'].'?access_token=APP_USR-6317427424180639-042414-47e969706991d3a442922b0702a0da44-469485398');
   
   if($payment)
   {
	   $payment=json_decode($payment);
	   
	   // aca va la lógica para guardar los datos del pago en base de datos en caso de requerirlo
	   
	   if($payment->status=='approved' and $payment->status_detail=='accredited')
	   {
		  //aca iría la lógica necesaria para procesar los pagos aprobados
	   }		   
   }
   else
	  http_response_code(404);
   
}
?>