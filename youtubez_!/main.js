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


  function getAllResults(maxResults, cid) {
    let total = 0,
      retrieved = 0;
    const responses = [];
    const getResults = function (pageToken) {
      return gapi.client.youtube.search.list(
        Object.assign({
            'part': 'id',
            //'type': 'video',
            'channelId': cid,
            //'onBehalfOfContentOwner': defaultChannel,
            maxResults
          },
          pageToken && {
            pageToken
          } // this only adds pageToken to the options if pageToken is defined
               )
      ).then(function (response) {
        // do one call here to return video details in the returned list
        
        
        
        responses.push(response);
        if (!pageToken) { // first call - set total
          total = response.result.pageInfo.totalResults;
        }
        retrieved += response.result.items.length;
        if (retrieved < total) {
          return getResults(response.result.nextPageToken);
        }
      })
      .catch(err => 
        alert('Error:' + err.result.error.message));
    };
    return getResults().then(function () {
      return responses;
    });
  }




  loginButton.onclick = function () {
    //authenticate().then(loadClient);
    authenticate();
  }

  formID.onsubmit = function (e) {
    e.preventDefault();
    fullList = {}; // empty this before another run!
    c = getChannel(defaultChannel).then(c => {
      getAllResults(50, c).then(allResponses => {
        // allresponses has all the responses in an array
        let h = allResponses;
      });
    });

  };

  formID.onsubmitXX = function (e) {
    e.preventDefault();
    fullList = {}; // empty this before another run!

    c = getChannel(defaultChannel);


    get50List(c, "")
      .then(function (result) { // (**)
        alert("nearly done");
      })

      .catch(err => alert('No channel by that name' + err));

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

    return gapi.client.youtube.channels.list({
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
        return channel.id;
      });
  }

  function get50List(c, tok) {

    params = {
      "part": "snippet",
      "type": "video",
      "channelId": c,
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
            alert("done all 50s");
            resolve("done");
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