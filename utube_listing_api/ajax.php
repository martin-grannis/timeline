<?php
require_once "config.php";

$url = "https://www.googleapis.com/youtube/v3/search?channelId=". $_GET['channel'] ."&order=date&part=snippet&type=video&maxResults=". $_GET['max_result'] ."&pageToken=". $_GET['nextPageToken'] ."&key=". DEVELOPER_KEY;

$arr_list = getYTList($url);

$arr_result = array();
if (!empty($arr_list)) {
    foreach ($arr_list->items as $yt) {
        //echo "<li>". $yt->snippet->title ."|".$yt->snippet->description."|";
        $dur = file_get_contents("https://www.googleapis.com/youtube/v3/videos?part=contentDetails,statistics&id=".$yt->id->videoId."&key=".DEVELOPER_KEY);
        $VidDuration =json_decode($dur, true);
        $viewCount= $VidDuration["items"][0]["statistics"]["viewCount"];
            $likeCount= $VidDuration["items"][0]["statistics"]["likeCount"];
            $dislikeCount= $VidDuration["items"][0]["statistics"]["dislikeCount"];
            //$favoriteCount= $VidDuration["items"][0]["statistics"]["favoriteCount"];
            $commentCount= $VidDuration["items"][0]["statistics"]["commentCount"];
            $publishedAt = $yt->snippet->publishedAt;
            $publishedAtFormatted = date("d/m/Y H:i:s",strtotime($publishedAt));

        $formatted_stamp = str_replace(array("PT","H","M","S"), array(":","",":",""),$VidDuration["items"][0]["contentDetails"]["duration"]);
        //echo $formatted_stamp."</li>";
        $restOfString =   $viewCount."|". $likeCount."|". $dislikeCount."|". $commentCount."|". $publishedAtFormatted."|". $VidDuration["items"][0]["contentDetails"]["duration"]."|". $formatted_stamp;
        
        //array_push($arr_result, ['title' => $yt->snippet->title, 'description' => $yt->snippet->description,'length'=>$formatted_stamp]);
        array_push($arr_result, ['title' => $yt->snippet->title, 'id' => $yt->id->videoId,'length'=>$formatted_stamp,'restOfString'=>$restOfString]);

    }
    if (isset($arr_list->nextPageToken)) {
        array_push($arr_result, ['nextPageToken' => $arr_list->nextPageToken]);
    }
}

echo json_encode($arr_result);