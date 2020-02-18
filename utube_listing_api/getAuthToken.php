<?php

$client_id="531877930816-8gj1tg4k9sifv6961ana7edqsif2q1if.apps.googleusercontent.com";
$client_secret="Ch5O_80G0dqMn2TfE2CnUSPY";
$redirect_uri="https://stjohnstimeline.uk/utube_listing_api/get-video-list.php";

$oauth2token_url = "https://accounts.google.com/o/oauth2/token";
$clienttoken_post = array(
    "code" => $code,
    "client_id" => $client_id,
    "client_secret" => $client_secret,
    "redirect_uri" => $redirect_uri,
    "grant_type" => "authorization_code"
);

$curl = curl_init($oauth2token_url);

curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $clienttoken_post);
curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

$json_response = curl_exec($curl);
curl_close($curl);

$authObj = json_decode($json_response);

if (isset($authObj->refresh_token)){
    //refresh token only granted on first authorization for offline access
    //save to db for future use (db saving not included in example)
    global $refreshToken;
    $refreshToken = $authObj->refresh_token;
}

$accessToken = $authObj->access_token;
