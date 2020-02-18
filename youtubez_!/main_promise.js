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
    fullList = {}; // empty this before another run!
    getChannel(defaultChannel);

  }

  signoutButton.onclick = signOut;


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

// new promise to get videos 
        get50List(channelID,"");
        // var promise = new Promise(function(resolve, reject) {
        //   // do a thing, possibly async, then…
        //   get50List(channelID);
        
        //   if (/* everything turned out fine */) {
        //     resolve("Stuff worked!");
        //   }
        //   else {
        //     reject(Error("It broke"));
        //   }
        // });
        
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
            return get50List(c, nextToken);
          } else {
            // arr = Object.entries(fullList);
            // alert("total: " + arr.length);

            // we have all the data in fulllist
            //return id_array;
            //return "something";
            return fullList;
          }
 
        }
        .then (function(){
// here at end of promise chain ?
            arr = Object.entries(fullList);
            alert("total: " + arr.length);
        })
        
        ,
        function (err) {
          console.error("Execute error", err);
          alert("r1 error", err);
        });

  }




  function getmyVideoListOLD(channelID, pageToken = "") {

    params = {
      "part": "snippet",
      "type": "video",
      "channelId": channelID,
      //"channelType": "any",
      //"eventType": "live",
      "order": "date",
      "maxResults": 50,
      //"code":API_KEY
    }
    if (pageToken != "") {
      params["pageToken"] = pageToken;
    }
    id_array = "";
    var request = gapi.client.youtube.search.list(params);
    id_array = ""; // empty the result string
    request.execute(function (response) {
      nextToken = response.nextPageToken;
      Object.keys(response.items).forEach(function (item) {
        id_array += response.items[item].id.videoId + ",";
      });

      //id_array = substr(id_array, -1); // lost the last comma
      id_array = id_array.slice(0, -1);

      executeGet50List(id_array);
      // params2 = {
      //   "part": "snippet,contentDetails,statistics",
      //   "id": id_array
      // }
      // ///var hhh="";
      // var request2 = gapi.client.youtube.videos.list(params2);
      // request2.execute(function (response2) {
      //   //h = "hkjh";
      //   //hhh = response2;
      //   Array.prototype.push.apply(fullList, response2.items);
      // });

      // // do the video list call and process the response into fullList 
      // //  Array.prototype.push.apply(fullList, response.items);

      if (nextToken) {
        getmyVideoList(channelID, nextToken);
      } else {
        // we got em all !
        // var element_count = 0;
        //   for(var e in fullList) if(fullList.hasOwnProperty(e)) element_count++;
        // id_array = substr(id_array,-1); // lost the last comma
        output = "";
        // convert fullList to array;
        arr = Object.entries(fullList);
        alert("total: " + arr.length);
        for (var key in fullList) {
          if (fullList.hasOwnProperty(key)) {
            // do stuff
            obj = fullList[key];
            if (typeof obj.snippet !== 'undefined') {
              output += obj.snippet.id + "<br>";
            } else {
              a = "wtf";
            }

          }
        }
        videoContainer.innerHTML = output;
      }


      // alert(Object.keys(fullList).length-1);


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


  async function foo(c) {
    apiKey = API_KEY;
    channelId = c;
    var channelId = channelID;
    var url = `https://www.googleapis.com/youtube/v3/search?key=${apiKey}&channelId=${channelId}&part=snippet,id&order=date&maxResults=20`

    var resp = await fetch(url)
    var data = await resp.json()

    var videoIds = []
    for (var item of data.items)
      videoIds.push(item.id.videoId);

    var urls = []
    for (var id of videoIds)
      urls.push(`https://www.googleapis.com/youtube/v3/videos?part=statistics&id=${id}&key=${apiKey}`);

    for (var url of urls) { //This for loop will stop in each url to complete its fetch
      let resp = await fetch(url)
      let data = await resp.json()
      console.log(data)
    }
    return 'Good Anakin goooood'
  }

  