<?php
$path = preg_replace( '/wp-content.*$/', '', __DIR__ );
require_once( $path . 'wp-load.php' );

	global $wpdb;
    	$tablecorrieri=$wpdb->prefix . 'cspedisci_corrieri';

// leggo le variabili 
$username=$_POST['email'];
$password=$_POST['password'];

$basicauth=base64_encode($username . ':' . $password);

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://ordini.noispediamo.it/cspedisci-api/spedizione',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'Authorization: Basic ' . $basicauth
  ),
));

$response = curl_exec($curl);
 $risposta=json_decode($response,true);
curl_close($curl);



 if($risposta['errors']==0) { // tutto ok
	require_once( $path . 'wp-admin/includes/upgrade.php' );
	$sql = "TRUNCATE TABLE $tablecorrieri";
    $wpdb->query($sql); // Executes the TRUNCATE
 //   print_r($risposta['corrieri']);
    foreach($risposta['corrieri'] as $corriere) {
     /*   print_r($corriere);
        exit; */
    	$sqlcorriere="Insert Ignore into $tablecorrieri set id=" . $corriere['id_corriere'] .", corriere='" . $corriere['nome'] .   "', tconsegna='" . $corriere['tconsegna'] . " gg'";
	    $wpdb->query($sqlcorriere);
    }
     
 }
 else json_encode(["errors" => 1]);

exit;


 echo json_encode($output);

?>