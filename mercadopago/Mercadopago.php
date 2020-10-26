<?php 
require_once('autoload.php');

class Mercadopago{
	public $apiToken;
	private $items=[];
	private $backUrl=[];
	public $externalReference=null;
	public $notificationUrl=null;
	public $nombreComprador=null;
	public $apellidoComprador=null;
	public $emailComprador=null;
	private $ci=null;
	public function __construct(){
       $this->ci =& get_instance();
	}
	
	public function MpPaymentForm(string $frmAction, string $idPreferencia){
		return '<form method="post" action="'.$frmAction.'" id="formMP" style="display:none">
						 <script src="https://www.mercadopago.com.ar/integrations/v1/web-payment-checkout.js" data-preference-id="'.$idPreferencia.'"></script></form><script>$("#formMP button").click();</script>';
	}
	
	public function addItem(string $item, float $precioUnitario, int $cantidad){
		$this->items[]= ['item'=>$item, 'precioUnitario'=>$precioUnitario, 'cantidad'=>$cantidad];
	}

	public function setSuccessUrl(string $backUrl){
		$this->backUrl['success']=$backUrl;
	}
	public function setPendingUrl(string $backUrl){
		$this->backUrl['pending']=$backUrl;
	}
	public function setFailureUrl(string $backUrl){
		$this->backUrl['failure']=$backUrl;
	}


    public function getUTCdatetime(string $fecha){
		$date= date_create($fecha);
		$offset= date('Z')/3600+4;
		date_add($date, date_interval_create_from_date_string($offset.' hours'));
		return date_format($date, 'Y-m-d H:i:s');
	}

	public function crearPreferencia(){
		MercadoPago\SDK::setAccessToken($this->apiToken);
		$items=[];
		 $preference = new MercadoPago\Preference();
		foreach($this->items as $i)
		{
			$item = new MercadoPago\Item();
            $item->title = $i['item'];
            $item->quantity = $i['cantidad'];
            $item->unit_price = $i['precioUnitario'];
			$items[]= $item;
		}
		$preference->items = $items;
		if(count($this->backUrl)>0)
		{
		  $preference->back_urls=$this->backUrl;
		  $preference->auto_return = "approved";
		}
		$payer = new MercadoPago\Payer();
		$payer->email= $this->emailComprador;
		$payer->name= $this->nombreComprador;
		$payer->surname= $this->apellidoComprador;
		$preference->payer=$payer;
		
		if($this->externalReference!=null)
		  $preference->external_reference= $this->externalReference;	
		if($this->notificationUrl!=null)
		  $preference->notification_url=$this->notificationUrl;	
		
		$preference->expires=true;
		$preference->expiration_date_to=date('c', time()+60*10);
		
		$preference->save();
		return $preference->id;
	}
	
    public function getPaymentCallbackStatus(){
		$externalReference= $_REQUEST['external_reference'];
		$preferenceId= $_REQUEST['preference_id'];
		if(isset($_REQUEST['payment_status']))
		  $status= $_REQUEST['payment_status']; 
	    elseif(isset($_REQUEST['collection_status']))
		  $status= $_REQUEST['collection_status'];
		else
		  return false;
	  
		//$statusDetail= $_REQUEST['payment_status_detail'];
        $response= new stdClass();
		$response->externalReference=$externalReference;
		$response->preferenceId= $preferenceId;
		$response->pagado=false;
		$response->pendiente=false;
		$response->rechazado=false;
		if($status=='approved')
		   $response->pagado=true;
	    else if($status=='in_process')
		   $response->pendiente=true;
        else if($status=='rejected')
           $response->rechazado=true;			
		return $response;
	}

	public function saveNotificationData(){
		if(isset($_REQUEST['type']))
		   $type= $_REQUEST['type'];
	    elseif(isset($_REQUEST['topic']))
		   $type= $_REQUEST['topic'];
		else
		   throw new MyExcepcion('Bad request', 500);
	   
		if(isset($_REQUEST['id']))
		   $id= $_REQUEST['id'];
	    elseif(isset($_REQUEST['data_id']))
		   $id= $_REQUEST['data_id'];
		else
		   throw new MyExcepcion('Bad request', 500);
		MercadoPago\SDK::setAccessToken($this->apiToken);

		switch($type)
		{
		   case 'payment':
				 $paymentData= file_get_contents('https://api.mercadopago.com/v1/payments/'.$id.'?access_token='.$this->apiToken);
                 return $this->savePaymentData(json_decode($paymentData));		  
		  break;
		   
		   case 'chargebacks':
		   
		   break;
		   
		   default:
		        return;
		}

	}
	
	private function savePaymentData($data){
		$query="insert into clasificados_mercadopago_ipn(data_id,
		                                    external_reference,
											last_updated,
											payment_type_id,
											status,
											status_detail,
											transaction_amount,
											transaction_amount_refunded,
											payer_email,
											payer_identification_type,
											payer_identification_number,
											payer_phone,
											payer_first_name,
											payer_last_name,
											money_release_date)
				values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
	    $qData=[$data->id,
		       $data->external_reference,
			   $this->getUTCdatetime($data->date_last_updated),
			   $data->payment_type_id,
			   $data->status,
			   $data->status_detail,
			   $data->transaction_amount,
			   $data->transaction_amount_refunded,
			   $data->payer->email,
			   $data->payer->identification->type,
			   $data->payer->identification->number,
			   $data->payer->phone->area_code.'-'.$data->payer->phone->number,
			   $data->payer->first_name,
			   $data->payer->last_name,
			   $data->money_release_date];
		  $this->ci->db->query($query, $qData);
          $data->pagado=false;
		  $data->pendiente=false;
		  $data->rechazado=false;
          if($data->status=='approved' and $data->status_detail=='accredited')
            $data->pagado=true;
	      if($data->status=='in_process')
		    $data->pendiente=true;
          if($data->status=='rejected')
            $data->rechazado=true;					  
		  return $data;		  
	}
}