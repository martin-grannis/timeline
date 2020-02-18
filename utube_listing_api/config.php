<?php
define('DEVELOPER_KEY', 'AIzaSyA5FhC-5a5TvK7qfll7fw8GTL_Hm_gbKSg');

function getYTList($api_url = '') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $arr_result = json_decode($response);
    if (isset($arr_result->items)) {
        return $arr_result;
    } elseif (isset($arr_result->error)) {
        //echo "Invalid channel id";
        echo "Error: ".$arr_result->error->message;
    }
}


function updateVidDescription(){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/youtube/v3/videos?key=".DEVELOPER_KEY);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

}