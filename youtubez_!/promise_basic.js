const loginButton = document.getElementById('authorize-button');
const signoutButton = document.getElementById('signout-button');
const formID = document.getElementById('formID');
const getVideosButton = document.getElementById('get-button');
const content = document.getElementById('content');
const channelInput = document.getElementById('channel-input');
const videoContainer = document.getElementById('video-container');
const defaultChannel = "StJohnsNottingham";
var channelID = "";
var uploadsID = "";
var fullList = {};


formID.onsubmit = function (e) {
    e.preventDefault();
    fullList = {}; // empty this before another run!
    getChannel(defaultChannel);    
}


function writeChannelDescription(r){
    console.log(r);
        const channel = r.result.items[0];
        const output = `
  <ul class="collection">
  <li class="collection-item">Title: ${channel.snippet.title}</li>
  <li class="collection-item">ID: ${channel.id}</li>
  <li class="collection-item">Subscribers: ${numberWithCommas(channel.statistics.subscriberCount)}</li>
  <li class="collection-item">Views: ${numberWithCommas(channel.statistics.viewCount)}</li>
  <li class="collection-item">Videos: ${numberWithCommas(channel.statistics.videoCount)}</li>
  </ul>
  <p>  ${channel.snippet.description}  </p>
  <hr>
  <a class="btn grey darken-2" target="_blank" href="https://youtube.com/${channel.snippet.customUrl}">Visit channel</a>
  `;
        showChannelData(output);
}

//tempcounters
var c1,c2,c3;

function getChannel(channel) {

    c1=0;
    gapi.client.youtube.channels.list({
        "part": 'snippet,contentDetails,statistics',
        "forUsername": channel
      })
      .then(response => {
            writeChannelDescription(response); 
            return channel.id;
      })
      .then(c => {
            get50List(c,"");
      })
      .then( x_unused => {
        arr = Object.entries(fullList);
        alert("total: " + arr.length);
      })
      .catch(err => alert('No channel by that name' + err));
  }


  function get50List(c, tok) {

    params = {
      "part": "snippet",
      "type": "video",
      "channelId": channelID,
      //"channelType": "any",
      //"eventType": "live",
      "order": "date",
      "maxResults": 50,
    }

    if (tok != "") {
      params["pageToken"] = tok;
    }

    return gapi.client.youtube.search.list(params)
      .then(function (r1) {
          // we have a chunk of 50 video ids, so process them
          id_array = "";
          Object.keys(r1.result.items).forEach(function (item) {
            id_array += r1.result.items[item].id.videoId + ",";
            //fullList += r1.result.items[item].id.videoId + ",";

          });
          id_array = id_array.slice(0, -1);

          // get video details
          executeGet50List(id_array); // this appends into fullList

          // add this list to fulllist
          //fullList +=id_array;
          nextToken = r1.result.nextPageToken;
          if (typeof (nextToken) !== 'undefined') {
            get50List(c, nextToken);
          } else {
            return fullList;
          }

        },
        function (err) {
          console.error("Execute error", err);
          alert("r1 error", err);
        });

  }

  function executeGet50List(a) {
    return gapi.client.youtube.videos.list({
        "part": "snippet,contentDetails,statistics",
        "id": a
      })
      .then(function (r2) {
          // Handle the results here (response.result has the parsed body).
          //alert("then function");
          //console.log("Response", r2);
          Array.prototype.push.apply(fullList, r2.result.items);
          arr = Object.entries(fullList);
          alert("partial: " + arr.length);
        },
        function (err) {
          console.error("Execute error", err);
          alert("r2 error", err);
        });
  }


