<?php

include_once('tbs_class.php');
include_once('class.phpmailer.php'); // la calsse pour l'envoi d'email

// préparation des données
$data = array();
$data[0] = array('email'=>'bob@dom1.com', 'firstname'=>'Bob', 'lastname'=>'Rock');
$data[0]['articles'][] = array('caption'=>'Book - Are you a geek?' , 'qty'=>1 ,'uprice'=>12.5);
$data[0]['articles'][] = array('caption'=>'DVD - The new hope'     , 'qty'=>1 ,'uprice'=>11.0);
$data[0]['articles'][] = array('caption'=>'Music - Love me tender' , 'qty'=>1 ,'uprice'=>0.99);

$data[1] = array('email'=>'evy@dom1.com', 'firstname'=>'Evy', 'lastname'=>'Studette');
$data[1]['articles'][] = array('caption'=>'Drink - Cola'           , 'qty'=>3 ,'uprice'=>0.99);

$data[2] = array('email'=>'babe@dom1.com', 'firstname'=>'Babe', 'lastname'=>'Moonlike');
$data[2]['articles'][] = array('caption'=>'Book - Love is love'    , 'qty'=>1 ,'uprice'=>12.5);
$data[2]['articles'][] = array('caption'=>'Book - Never panic'     , 'qty'=>1 ,'uprice'=>11.0);

$data[3] = array('email'=>'stephan@dom1.com', 'firstname'=>'Stephan', 'lastname'=>'Kimer');
$data[3]['articles'][] = array('caption'=>'DVD - The very last weekend' , 'qty'=>1 ,'uprice'=>12.5);
$data[3]['articles'][] = array('caption'=>'DVD - Frozen in September'     , 'qty'=>1 ,'uprice'=>11.0);
$data[3]['articles'][] = array('caption'=>'Music - Obladi Oblada' , 'qty'=>1 ,'uprice'=>0.99);
$data[3]['articles'][] = array('caption'=>'Music - Push push' , 'qty'=>1 ,'uprice'=>0.99);

// préparation du modèle du corps du message
$TBS = new clsTinyButStrong;
$TBS->LoadTemplate('tbs_fr_exemples_email.txt',false);
$tpl_subject = $TBS->TplVars['subject']; // retrieve the subject from the template
$tpl_body = $TBS->Source;

// préparation de l'outil d'envoi d'email
$Mail = new PHPMailer();
$Mail->FromName = 'TBS example';
$Mail->From = 'example@tinybutstrong.com';

// fusionne et envoie chaque email
foreach ($data as $recipiant) {

  // fusionne le corps
	$TBS->Source = $tpl_body;	// initialise TBS avec le modèle de corps de message
	$TBS->MergeField('i', $recipiant); // fusionne le déstinataire en cours
	$TBS->MergeBlock('a', $recipiant['articles']);
	$TBS->Show(TBS_NOTHING); // fusionne les champs TBS automatiques
	
	// prépare le courrier
	$Mail->AddAddress($recipiant['email']);  
	$Mail->Subject = $tpl_subject;
	$Mail->Body = $TBS->Source;
	
	// envoie l'email
	//$Mail->Send(); // annulé car pas d'envoie dans les exemples, à la place on affiche les messages
	echo '<pre>To: '.$recipiant['email']."\r\n".'Subject: '.$tpl_subject."\r\n".$Mail->Body."\r\n\r\n============================================\r\n\r\n".'</pre>';
	
}

?>