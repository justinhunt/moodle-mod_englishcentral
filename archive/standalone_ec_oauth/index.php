<?php
/*
 *
 * @package    englishcentral
 * @copyright  2014 Justin Hunt
 */


require_once(dirname(__FILE__).'/englishcentral.php');

  	$consumer_key = "fefe1c7849d542fc54747b318f7bcd6b";
    $consumer_secret="6PZQtpjZbtLDnL5vjUm88Fo0zHGP4X5x1A2GRh5vy0R+w1hFtdCp0B0jMMVjylshsas2IPAxmHZQGyqOaNvvEcyY5yrWNpt/abmgc8MQWJYkgisu0VFe0lpk8KFA73CdHn5YfmDXKrO5KTjMZ82YR4d6phZYiuQTh9Lmor+c8iY=";
    $ec = new EnglishCentral($consumer_key,$consumer_secret);
    $oauth_callback = $CFG->wwwroot. '/admin/oauth2callback.php';
	//$oauth_callback = 'oob';

    $requesttoken = $ec->getRequestToken($oauth_callback);
    $verifier = $ec->getVerifier();
   // $accesstoken = $ec->getAccessToken($requesttoken,$verifier);
    $accesstoken = "";
	echo "requesttoken:" . $requesttoken;
	echo "<br />verifier:" . $verifier;
	echo "<br />accesstoken:" . $accesstoken;
    die;

