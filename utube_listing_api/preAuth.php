<?php

$url = "https://accounts.google.com/o/oauth2/auth";  
$params = array(
"response_type" => "code",
"client_id" => "531877930816-8gj1tg4k9sifv6961ana7edqsif2q1if.apps.googleusercontent.com",
"redirect_uri" => "https://stjohnstimeline.uk/utube_listing_api/get-video-list.php",

"scope" => "https://gdata.youtube.com",
"access_type" => "offline"
);
$request_to = $url . '?' . http_build_query($params);
header("Location: " . $request_to);