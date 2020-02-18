<?php
require_once "config.php";

// get code from URL

// if (isset($_REQUEST["code"])){

//     $code = $_REQUEST["code"];
// }
require_once "getAuthToken.php";



// $service = new Google_Service_YouTube($client);
// $videoSnippet = new Google_Service_YouTube_VideoSnippet();

$arr_list = array();
if (array_key_exists('channel', $_GET) && array_key_exists('max_result', $_GET)) {
    $channel = $_GET['channel'];
    $url = "https://www.googleapis.com/youtube/v3/search?channelId=$channel&order=date&part=snippet&type=video&maxResults=". $_GET['max_result'] ."&key=". DEVELOPER_KEY;
    
    $arr_list = getYTList($url);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Get Video List of YouTube Channel</title>
    <style>
    div#loadmore {
        background: red;
        width: 75px;
        padding: 15px;
        border-radius: 6px;
        color: burlywood;
        cursor: pointer;
        text-align: center;
        margin: 0 auto;
    }
    </style>
</head>
<body>
    <form method="get">
        <p><input type="text" name="channel" placeholder="Enter Channel ID" value="<?php if(array_key_exists('channel', $_GET)) echo $_GET['channel']; ?>" required></p>
        <p><input type="number" name="max_result" placeholder="Max Results" min="1" max="50" value="<?php if(array_key_exists('max_result', $_GET)) echo $_GET['max_result']; ?>" required></p>
        <p><input type="submit" value="Submit"></p>
    </form>

    <?php
    if (!empty($arr_list)) {
        echo '<ul class="video-list">';
        foreach ($arr_list->items as $yt) {
            //echo "<li>". $yt->snippet->title ." (". $yt->id->videoId .")</li>";
            echo "<li>". $yt->snippet->title ."|"."https://www.youtube.com/watch?v=".$yt->id->videoId."|";

            // update the description while we are here
          // $yt->snippet->description= "Hello sailor";

           //curl --insecure -v -i -X PUT -H "Content-Type: application/json" -H "Authorization:  Bearer ACCESS_TOKEN_FROM_GOOGLE_HERE" -d '{"id":"YOUTUBE_VIDEO_ID_HERE","kind":"youtube#video","snippet":{"title":"My title","description":"My description","categoryId":"22"}}' "https://www.googleapis.com/youtube/v3/videos?part=snippet"           

        //    $newDesc="some words for the video after uploading";
            
        //    $ch = curl_init();

        //    curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/youtube/v3/videos?part=snippet&key='. DEVELOPER_KEY);
        //    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //    curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id\":\"".$yt->id_>videoId."\",\"kind\":\"youtube#video\",\"snippet\":{\"description\":\"".$newDesc."\"}}");
        //    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

        //    $headers = array();
        //    $headers[] = 'Content-Type: application/json';
        //    $headers[] = 'Authorization: Bearer '.$accessToken;
        //    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        //    $result = curl_exec($ch);
        //    if (curl_errno($ch)) {
        //        echo 'Error:' . curl_error($ch);
        //    }
        //    curl_close($ch);


            $dur = file_get_contents("https://www.googleapis.com/youtube/v3/videos?part=contentDetails,statistics&id=".$yt->id->videoId."&key=".DEVELOPER_KEY);
            $VidDuration =json_decode($dur, true);
            $viewCount= $VidDuration["items"][0]["statistics"]["viewCount"];
            $likeCount= $VidDuration["items"][0]["statistics"]["likeCount"];
            $dislikeCount= $VidDuration["items"][0]["statistics"]["dislikeCount"];
           // $favoriteCount= $VidDuration["items"][0]["statistics"]["favoriteCount"];
            $commentCount= $VidDuration["items"][0]["statistics"]["commentCount"];
            $publishedAt = $yt->snippet->publishedAt;
            $publishedAtFormatted = date("d/m/Y H:i:s",strtotime($publishedAt));

            $formatted_stamp = str_replace(array("PT","H", "M","S"), array(":","",":",""),$VidDuration["items"][0]["contentDetails"]["duration"]);
            echo $viewCount."|";
            echo $likeCount."|";
            echo $dislikeCount."|";
            //echo $favoriteCount."|";
            echo $commentCount."|";
            echo $publishedAtFormatted."|";
            echo $VidDuration["items"][0]["contentDetails"]["duration"]."|";
            echo $formatted_stamp."</li>";
        }
        echo '</ul>';

        if (isset($arr_list->nextPageToken)) {
            echo '<input type="hidden" class="nextpagetoken" value="'. $arr_list->nextPageToken .'" />';
            echo '<div id="loadmore">Load More</div>';
        }
    }
    ?>

    <script>
    var httpRequest, nextPageToken;
    document.getElementById("loadmore").addEventListener('click', makeRequest);
    function makeRequest() {
        httpRequest = new XMLHttpRequest();
        nextPageToken = document.querySelector('.nextpagetoken').value;
        if (!httpRequest) {
            alert('Giving up :( Cannot create an XMLHTTP instance');
            return false;
        }
        httpRequest.onreadystatechange = function(){
            if (this.readyState == 4 && this.status == 200) {
                var list = JSON.parse(this.responseText);
                for(var i in list) {
                    //if(list[i].title != undefined && list[i].description != undefined) {
                        if(list[i].title != undefined && list[i].id != undefined) {
                        var newElement = document.createElement('li');
//                        newElement.innerHTML = '<li>'+ list[i].title +'('+ list[i].id +')</li>';
                        //newElement.innerHTML = "<li>"+ list[i].title +"|"+list[i].description+"|"+list[i].length+"</li>";
                        newElement.innerHTML = "<li>"+ list[i].title +"|"+"https://www.youtube.com/watch?v="+list[i].id+"|"+ list[i].restOfString+"</li>";
                        //$dur = file_get_contents("https://www.googleapis.com/youtube/v3/videos?part=contentDetails&id=".$yt->id->videoId."&key=".DEVELOPER_KEY);
                        
                       // $VidDuration =json_decode($dur, true);
                       // $formatted_stamp = str_replace(array("PT", "H","M","S"), array("",":",":",""),$VidDuration["items"][0]["contentDetails"]["duration"]);
                        //newElement.innerHTML += $formatted_stamp+"</li>";

                        document.querySelector('.video-list').appendChild(newElement);
                    }
                }

                if(list[list.length-1].nextPageToken != undefined) {
                    document.querySelector('.nextpagetoken').value = list[list.length-1].nextPageToken;
                } else {
                    var loadmore = document.getElementById("loadmore");
                    loadmore.parentNode.removeChild(loadmore);
                }
            }
        };
        httpRequest.open('GET', 'ajax.php?channel=<?php echo $_GET['channel']; ?>&max_result=<?php echo $_GET['max_result']; ?>&nextPageToken='+nextPageToken, true);
        httpRequest.send();
    }
    </script>
</body>
</html>