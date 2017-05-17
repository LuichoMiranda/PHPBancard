<?php
/*
* Luicho Miranda
* 17/Jun/2016
*/
class Bancard{
	
	private $private_key;
	private $public_key;
	private $service_url;
	private $payment_url;
	private $return_url = "http://dominio.com/transaccion_aceptada";
	private $cancel_url = "http://dominio.com/transaccion_cancelada";

	private $item_description;
	private $currency_code = "PYG";
	private $response_mode = "URL";
	private $salt = '+PbC81_1jLd';

	public function __construct($production = false){
		$this->private_key = $production
		? 'CLAVE_PRIVADA_PRODUCCION' 	/* Clave privada de produccion */
		: 'CLAVE_PRIVADA_STAGING'; 		/* Clave privada de staging */

		$this->public_key  = $production
		? 'CLAVE_PUBLICA_PRODUCCION'	/* Clave publica de produccion */
		: 'CLAVE_PUBLICA_STAGING';		/* Clave publica de staging */

		$this->service_url = $production
		? 'https://vpos.infonet.com.py/vpos/api/0.3/'		 /* URL del servicio en produccion */
		: 'https://vpos.infonet.com.py:8888/vpos/api/0.3/';  /* URL del servicio en staging */

		$this->payment_url = $production
		? 'https://vpos.infonet.com.py/payment/single_buy'		 /* URL de pagina de pagos en produccion */
		: 'https://vpos.infonet.com.py:8888/payment/single_buy'; /* URL de pagina de pagos en staging */
	}

	/*
	* Establece la clave publica desde el método
	*/
	public function set_public_key($key){
		$this->public_key = $key;
	}

	/*
	* Establece la clave privada desde el método
	*/
	public function set_private_key($key){
		$this->private_key = $key;
	}

	/*
	* Procesa los datos de la transacción para obtener el process_id
	* @transaction_id (int)
	* @amount (double)
	* @extra (array) - Claves: currency_code, additinal_data, item_description
	*/
	public function process($transaction_id, $amount, $extra = array()){

		$amount = number_format($amount, 2, '.', '');
		
		$this->currency_code = !isset($extra['currency_code'])
		? $this->currency_code
		: $extra['currency_code'];

		$this->item_description = isset($extra['item_description'])
		? $extra['item_description']
		: (($this->item_description != null) ? $this->item_description : "Transacción #{$transaction_id}");
		
		$result = $this->request(
			"single_buy",
			array(
				"public_key" => $this->public_key,
				"operation" => array(
					"token" => $this->get_token($transaction_id, $amount),
					"shop_process_id" => $transaction_id,
					"currency" => $this->currency_code,
					"amount" => $amount,
					"additional_data" => isset($extra['additional_data']) ? $extra['additional_data'] : "",
					"description" => $this->item_description,
					"return_url" => $this->return_url . '?trid=' . md5($transaction_id . $this->salt),
					"cancel_url" => $this->cancel_url . '?trid=' . md5($transaction_id . $this->salt)
				)
			)
		);

		$result = @json_decode($result);

		if(isset($result->process_id)){
			switch ($this->response_mode){
				default:
				case "URL":
					return $this->payment_url . "?process_id={$result->process_id}";
					break;
				case "REDIRECT":
					$this->redirect($result->process_id);
					break;
				case "PROCESS_ID":
					return $result->process_id;
					break;
				case "OBJECT":
					return (object)array(
						"process_id" => $result->process_id,
						"url" => $this->payment_url,
						"query_string" => "?process_id=" . $result->process_id
					);
					break;
			}
		}else{
			throw new Exception("Imposible obtener process id");
		}

	}

	/*
	* Envía petición de rollback sobre una transacción
	* @transaction_id (int)
	*/
	public function rollback($transaction_id){
		$result = $this->request(
			"single_buy/rollback",
			array(
				"public_key" => $this->public_key,
				"operation" => array(
					"token" => md5($this->private_key . $transaction_id .'rollback' . '0.00'),
					"shop_process_id" => $transaction_id
				)
			)
		);

		return $result;
	}

	/*
	* Consulta de confirmación de pago
	* @transaction_id (int)
	*/
	public function confirmations($transaction_id){
		
		$result = $this->request(
			"single_buy/confirmations",
			array(
				"public_key" => $this->public_key,
				"operation" => array(
					"token" => md5($this->private_key . $transaction_id . 'get_confirmation'),
					"shop_process_id" => $transaction_id
				)
			)
		);

		return $result;
	}

	/*
	* Lee la respuesta enviada por bancard a la url especificada en el panel de comercios de bancard
	*/
	public function get_response(){
		$response = file_get_contents("php://input");
		return $response;
	}

	/*
	* Genera token para el método process
	* @transaction_id (int)
	* @amount (double)
	*/
	private function get_token($transaction_id, $amount){
		return md5($this->private_key . $transaction_id . $amount . $this->currency_code);
	}

	/*
	* Redirecciona a la página de pagos de bancard
	* @process_id (string)
	*/
	private function redirect($process_id){
		@ob_end_clean();
		header("Location: {$this->payment_url}?process_id={$process_id}");
		exit;
	}

	/*
	* Establece la url de cancelación del pago
	*/
	public function set_cancel_url($url){
		$this->cancel_url = $url;
	}

	/*
	* Establece la url de confirmación del pago
	*/
	public function set_return_url($url){
		$this->return_url = $url;
	}

	public function set_currency($cc){
		$this->currency_code = $cc;
	}

	/*
	* Establece modo de respuesta para el método process
	*/
	public function set_response_mode($mode){
		switch ($mode) {
			case "URL":
			case "REDIRECT":
			case "PROCESS_ID":
			case "OBJECT":
				$this->response_mode = $mode;
				break;
			default:
				throw new Exception("Modo de respuesta no definido");
				break;
		}
	}

	/*
	* Establece
	*/
	public function set_item_description($description){
		$this->item_description = $description;
	}

	/*
	* Envía peticiones al servicio de bancard
	*/
	private function request($action, $data){
		
		$data = @json_encode($data);

		$headers = array(
			'Content-Type: application/json'
		);

		$session = curl_init($this->service_url . $action);

		curl_setopt($session, CURLOPT_POST, true);
		curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($session, CURLOPT_POSTFIELDS, $data);
	    	curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
	    	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
	    	$response = curl_exec($session);
	    	$error = curl_error($session);
	    	curl_close($session);

	    	if($response === false){
			throw new Exception("No se pudo enviar la petición {$action}. {$error}");
		}else{
			return $response;
		}

	}
}
