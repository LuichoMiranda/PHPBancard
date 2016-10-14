# PHPBancard
Clase para procesamiento de pagos a través de bancard

##Procesar una transacción
```
$produccion = false; 
$bancard = new Bancard($produccion);
$bancard->process($transaction_id, $amount);
```
## Métodos de configuración
 - **set_cancel_url($url)**: Establece la URL de respuesta de cancelación del anuncio.
 - **set_return_url($url)**: Establece la URL de respuesta de confirmación de la transacción.
 - **set_item_description($description)**: Establece la descripción del item a mostrar en la pasarela de pagos.
 - **set_response_mode($mode)**: Establece la modalidad de respuesta para el procesamiento de la transacción.
 
## Modalidades de respuesta
Las modalidades de respuesta disponibles son las siguientes:
 - **URL**: Retorna la URL a la pasarela de pagos de Bancard (predeterminado).
 - **REDIRECT**: Redirecciona a la pasarela de pagos de Bancard.
 - **PROCESS_ID**: Retorna el process id.
 - **OBJECT**: Retorna un objeto con las propiedades *process_id*, *url* y *query_string*.
 
 ### Ejemplo de implementación
 ```
 $produccion = false;
 $bancard = new Bancard($produccion);
 $bancard->set_response_mode("REDIRECT")
 $bancard->process($transacion_id, $amount);
 ```
 
 ## Consultar la confirmación de pago
 ```
$confirmation = $bancard->confirmations($transaction_id);
print $confirmation;
 ```
 
 ## Reverso de una transacción
 ```
 $bancard->rollback($transaction_id);
 ```
