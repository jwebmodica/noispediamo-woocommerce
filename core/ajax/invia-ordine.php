<?php
$path = preg_replace( '/wp-content.*$/', '', __DIR__ );
require_once( $path . 'wp-load.php' );

	global $wpdb;
    	$tablecorrieri=$wpdb->prefix . 'noispediamo_corrieri';
        $table_name = $wpdb->prefix . 'noispediamo_settings';
        $posts = $wpdb->get_row("SELECT * FROM $table_name WHERE id=777");
        

        $username=$posts->email;
        $password=$posts->password;
        $basicauth=base64_encode($username . ':' . $password);
        
// leggo le variabili 
$idordine=$_POST['idordine'];
$peso=$_POST['peso'];
$alt=$_POST['alt'];
$largh=$_POST['largh'];
$prof=$_POST['prof'];
$ritiro=$_POST['ritiro'];

// ricavo le informazioni dell'ordine
 $order = wc_get_order($idordine);
 if(!$order) {
      echo json_encode(["errors" => 1, "issue" => "problemi nel ritrovare l'ordine"]);
        exit; }
  if( ($peso<=0) or ($alt<=0) or ($largh<=0) or ($prof<=0) ) {
      echo json_encode(["errors" => 1, "issue" => "Specifica le dimensioni del pacco"]);
        exit; }
  if($ritiro=="") {
      echo json_encode(["errors" => 1, "issue" => "Indicare una data di ritiro valida"]);
        exit; }
  $date = str_replace('/', '-', $ritiro);
  $dataritiro=date('Y-m-d', strtotime($date));
//echo $paymentDate; // echos today! 
$domani = date('Y-m-d',  strtotime("+1 day"));
$duesett = date('Y-m-d',  strtotime("+14 day"));     
        
if (($dataritiro >= $domani) && ($dataritiro <= $duesett)){
    // non fare nulla
    }
else {
     echo json_encode(["errors" => 1, "issue" => "Non puoi prenotare un ritiro con più di 2 settimane di anticipo."]);
        exit; 
}

 $ordine=$order->get_address('shipping');
$tipopagamento = $order->get_payment_method_title();
$totale = $order->get_total();

// Get the Customer billing email
$billing_email  = $order->get_billing_email();

// Get the Customer billing phone
$billing_phone  = $order->get_billing_phone();
$customer_note = $order->get_customer_note();

// costruisco la chiamata API
$parametri= array (
        "mittente" => array(
             "nome" => $posts->nome, 
             "indirizzo" => $posts->indirizzo, 
             "civico" => $posts->civico, 
             "cap" => $posts->cap, 
             "citta" => $posts->citta, 
             "prov" => $posts->prov, 
             "email" => $posts->email, 
             "telefono" => $posts->telefono, 
             "note" => "" 
        ), 
   "destinatario" => array(
            "nome" => $ordine['company'] . " " . $ordine['first_name'] . " "  . $ordine['last_name'], 
            "indirizzo" => $ordine['address_1'], 
            "civico" => "", 
            "cap" => $ordine['postcode'], 
            "citta" => $ordine['city'], 
            "prov" => $ordine['state'], 
            "email" => $billing_email, 
            "telefono" => $billing_phone, 
            "note" => $customer_note 
         ), 
   "ritiro" => $ritiro, 
   "corriere" => $posts->corriere, 
   "pagamento" => $posts->pagamento, 
   "tmerce" => "Oggetti vari", 
   "api" => "1",
   "pacchi" => array(
                  [
                     "weight" => $peso, 
                     "height" => $alt, 
                     "length" => $prof, 
                     "width" => $largh 
                  ]
               )
    );

if($tipopagamento=="Pagamento alla consegna"){
    if($posts->contrassegno_iban=="" or $posts->contrassegno_conto=="") {
       echo json_encode(["errors" => 1, "issue" => "Iban o conto non specificato per il contrassegno. Vai su Impostazioni e fornisci i dati"]);
        exit; }
    $contrass=array(
               "importo" => $totale, 
               "iban" => $posts->contrassegno_iban, 
               "conto" => $posts->contrassegno_conto, 
               "mod_rimborso" => "" 
            );
    $parametri["contrassegno"]=$contrass;
}


// echo json_encode($parametri);


$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://ordini.noispediamo.it/cspedisci-api/spedizione',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => json_encode($parametri, JSON_UNESCAPED_UNICODE),
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json; charset=UTF-8',
    'Authorization: Basic ' . $basicauth
  ),
));

$response = curl_exec($curl);
 $risposta=json_decode($response,true);
curl_close($curl);

if(!isset($risposta)) {
    echo json_encode(["errors" => 1, "issue" => "Credenziali errate. Controlla su Impostazioni di aver inserito la tua email e password corretta."]);
    exit;
 }

 if($risposta['errors']===0) { // tutto ok
   
 	// aggiorno lo stato dell'ordine a completato
 	$orderstat = new WC_Order($idordine);
    if (!empty($orderstat)) {
        $orderstat->update_status('shipped');
    }
 	 // Inserisco nota con numero di ordine
 	 $idspedisci=$risposta['id'];
    $note = __("Ordine inviato al corriere nr ordine " . $idspedisci);
    $order->add_order_note( $note );
 	echo json_encode(["errors" => 0, "id" => $idspedisci, "idordine" => $idordine ]);
        exit; 
     
 }
 else {echo json_encode(["errors" => 1, "issue" => "Credenziali errate. Controlla su Impostazioni di aver inserito la tua email e password corretta."]);
    exit;
 }


 echo json_encode($output);

?>