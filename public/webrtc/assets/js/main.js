var maxCALLERS = 2;
var partnerID;
var myName;
var partnerName;
var room_name = 'default';

var QueryString = function () {
    var query_string = {};
    var query = window.location;
    query = query.toString();
    query = query.split('?');
    if (query[1]) {
        query = query[1];
        var vars = query.split("&");
        for (var i = 0; i < vars.length; i++) {
            var pair = vars[i].split("=");
            if (typeof query_string[pair[0]] === "undefined") {
                query_string[pair[0]] = decodeURIComponent(pair[1]);
            } else if (typeof query_string[pair[0]] === "string") {
                var arr = [query_string[pair[0]], decodeURIComponent(pair[1])];
                query_string[pair[0]] = arr;
            } else {
                query_string[pair[0]].push(decodeURIComponent(pair[1]));
            }
        }
        return query_string;
    } else {
        return false;
    }
}();


if (QueryString.room) {
    room_name = QueryString.room;
}
if (QueryString.uname) {
    myName = QueryString.room;
}
if (QueryString.pname) {
    partnerName = QueryString.pname;
    document.getElementById('partnerName').innerText=partnerName;
}

easyrtc.setSocketUrl("//litesignal.tokbird.com:443");
easyrtc.enableDebug(false);
easyrtc.setOnError(function (errEvent) {
    console.log(errEvent.errorText);
});
easyrtc.setStreamAcceptor(function (callerEasyrtcid, stream) {
    let videoobj = document.getElementById("partner");
    partnerID = callerEasyrtcid;
    easyrtc.setVideoObjectSrc(videoobj, stream);


});

easyrtc.setOnStreamClosed(function (callerEasyrtcid) {
    partnerID = null;
    easyrtc.setVideoObjectSrc(document.getElementById('partner'), "");

});


function my_init() {

    easyrtc.setRoomOccupantListener(loggedInListener);
    easyrtc.setPeerListener(peerListener);
    var connectSuccess = function (myId) {
        // console.log("My easyrtcid is " + myId);
    }
    var connectFailure = function (errorCode, errText) {
        // console.log(errText);
    }
    easyrtc.initMediaSource(
        function () { // success callback
            var selfVideo = document.getElementById("self");
            easyrtc.setVideoObjectSrc(selfVideo, easyrtc.getLocalStream());
            easyrtc.connect(room_name, connectSuccess, connectFailure);
        },
        connectFailure
    );
}


function loggedInListener(roomName, otherPeers) {

    easyrtc.setRoomOccupantListener(null); // so we're only called once.

    var list = [];
    var connectCount = 0;
    for (var easyrtcid in otherPeers) {
        list.push(easyrtcid);
    }
    //
    // Connect in reverse order. Latter arriving people are more likely to have
    // empty slots.
    //
    function establishConnection(position) {
        console.log("Total users-->" + list.length)

        function callSuccess() {
            connectCount++;
            if (connectCount < maxCALLERS && position > 0) {
                establishConnection(position - 1);
            }
        }

        function callFailure(errorCode, errorText) {
            easyrtc.showError(errorCode, errorText);
            if (connectCount < maxCALLERS && position > 0) {
                establishConnection(position - 1);
            }
        }
        easyrtc.call(list[position], callSuccess, callFailure);

    }
    if (list.length > 0) {
        establishConnection(list.length - 1);
    }
    //console.log("Total users-->"+list.length)
}

function sendmessage() {
    let text = document.getElementById('message').value;
    if (text.replace(/\s/g, "").length === 0) { // Don't send just whitespace
        return;
    }
    if (partnerID == null) { // Don't send just whitespace
        return;
    } else {
        easyrtc.sendDataWS(partnerID, "message", text, function (reply) {
            if (reply.msgType === "error") {
                easyrtc.showError(reply.msgData.errorCode, reply.msgData.errorText);
            }
        });
    }
    addToConversation("me", "message", text);
    document.getElementById('message').value = "";
}

function peerListener(who, msgType, content, targeting) {
    addToConversation(who, msgType, content, targeting);
    console.log(content);
}

function addToConversation(who, msgType, content, targeting) {
    // Escape html special characters, then add linefeeds.
    content = content.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    content = content.replace(/\n/g, '<br />');
    let _class = null;
    if (who == 'me') {
        _class = 'blue-color';
    } else {
        _class = 'red-color';
        who=partnerName;
    }
     document.getElementById('messages').innerHTML += '<div class="row"><div class="col-lg-2"><span class="blue-color"><strong>' + who + '</strong><br><small>2018-08-16 16:04:22</small></span></div><div class="col-md-10"><blockquote class="' + _class + '"><small>' + content + '</small></blockquote></div></div>';
}