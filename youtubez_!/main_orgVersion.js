  /**
   * Sample JavaScript code for youtube.channels.list
   * See instructions for running APIs Explorer code samples locally:
   * https://developers.google.com/explorer-help/guides/code_samples#javascript
   */
  const CLIENT_ID = "531877930816-94oo5vro34mbhs15elhf79neaq0cccgq.apps.googleusercontent.com";
  const API_KEY = "AIzaSyDwtUIKF8A_TcRVRxvBIJIJH44uFc18wfY";

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

  loginButton.onclick = function () {
    //authenticate().then(loadClient);
    authenticate();
  }

  formID.onsubmit = function (e) {
    e.preventDefault();
    fullList = {};
    getChannel(defaultChannel);

  }


  signoutButton.onclick = signOut;

  //<button onclick="authenticate().then(loadClient)">authorize and load</button>
  //<button onclick="execute()">execute</button>

  function updateSigninStatus(isSignedin) {
    if (isSignedin) {
      loginButton.style.display = 'none';
      signoutButton.style.display = 'block';
      content.style.display = 'block';
      videoContainer.style.display = 'block';
      //getChannel(defaultChannel);

    } else {
      loginButton.style.display = 'block';
      signoutButton.style.display = 'none';
      content.style.display = 'none';
      videoContainer.style.display = 'none';
    }

  }

  function showChannelData(data) {

    const channelData = document.getElementById('channel-data');
    channelData.innerHTML = data;
  }

  function getChannel(channel) {

    gapi.client.youtube.channels.list({
        "part": 'snippet,contentDetails,statistics',
        "forUsername": channel
      })
      .then(response => {
        console.log(response);
        const channel = response.result.items[0];
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
        channelID = channel.id;
        uploadsID = channel.contentDetails.relatedPlaylists.uploads;
        getmyVideoList(uploadsID);
        //console.log(uploadsID);
      })
      .catch(err => alert('No channel by that name'));
  }

  function getmyVideoList(playlistId, pageToken = "") {

    params = {
      "part": "snippet,contentDetails",
      "playlistId": playlistId,
      "maxResults": 50
    }
    if (pageToken != "") {
      params["pageToken"] = pageToken;
    }

    var request = gapi.client.youtube.playlistItems.list(params);
    request.execute(function (response) {
      nextToken = response.nextPageToken;
      Array.prototype.push.apply(fullList, response.items);

      if (nextToken) {
        getmyVideoList(playlistId, nextToken);
      } else {
        // we got em all !
        // var element_count = 0;
        //   for(var e in fullList) if(fullList.hasOwnProperty(e)) element_count++;
        output = "";
        // convert fullList to array;
        arr = Object.entries(fullList);
        alert(arr.length);
        for (var key in fullList) {
          if (fullList.hasOwnProperty(key)) {
            // do stuff
            obj = fullList[key];
            if(typeof obj.snippet !== 'undefined'){
              output += obj.snippet.title + "<br>";
            }
            else
            {
              a = "wtf";
            }
            
          }
        }
        videoContainer.innerHTML = output;
      }
        

        // alert(Object.keys(fullList).length-1);


    });

  }

  // function onGapi2Response(response){
  //   nextToken=response.nextPageToken;
  //   fullList += response.items;
  // } 


  function signOut() {
    gapi.auth2.getAuthInstance().signOut();
  }

  function authenticate() {
    return gapi.auth2.getAuthInstance()
      //.signIn({scope: ["https://www.googleapis.com/auth/youtube.readonly","https://www.googleapis.com/auth/youtube.upload"]})
      .signIn({
        scope: "https://www.googleapis.com/auth/youtube.readonly"
      })
      .then(function () {
          console.log("Sign-in successful");
          //loadClient();
        },
        function (err) {
          console.error("Error signing in", err);
        });
  }

  function loadClient() {
    gapi.client.setApiKey(API_KEY);
    return gapi.client.load("https://www.googleapis.com/discovery/v1/apis/youtube/v3/rest")
      .then(function () {
          console.log("GAPI client loaded for API");
          gapi.auth2.getAuthInstance().isSignedIn.listen(updateSigninStatus);
          // handle initial signin status
          updateSigninStatus(gapi.auth2.getAuthInstance().isSignedIn.get());
        },
        function (err) {
          console.error("Error loading GAPI client for API", err);
        });
  }


  // Make sure the client is loaded and sign-in is complete before calling this method.
  function execute() {
    return gapi.client.youtube.channels.list({
        "part": "snippet,contentDetails,statistics",
        "id": "UC_x5XG1OV2P6uZZ5FSM9Ttw"
      })
      .then(function (response) {
          // Handle the results here (response.result has the parsed body).
          console.log("Response", response);
        },
        function (err) {
          console.error("Execute error", err);
        });
  }

  gapi.load("client:auth2", function () {
    gapi.auth2.init({
      client_id: CLIENT_ID
    });
    loadClient();
  });


  function numberWithCommas(x) {
    x = x.toString();
    var pattern = /(-?\d+)(\d{3})/;
    while (pattern.test(x))
      x = x.replace(pattern, "$1,$2");
    return x;
  }