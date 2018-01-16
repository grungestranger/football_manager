var app = require('express')();
var server = require('http').Server(app);
var io = require('socket.io')(server);
var redis = require('redis');

var socketioJwt = require('socketio-jwt');
var myEnv = require('dotenv').config({path:'./.env'});
 
server.listen(8080);

/*io.on('connection', function (socket) {
 
  console.log("client connected");
  var redisClient = redis.createClient();
  redisClient.subscribe('message');
 
  redisClient.on("message", function(channel, data) {
    console.log("mew message add in queue "+ data['message'] + " channel" + channel);
    socket.emit(channel, data);
  });
 
  socket.on('disconnect', function() {
    redisClient.quit();
  });
 
});*/


 
// set authorization for socket.io
io.sockets
  .on('connection', socketioJwt.authorize({
    secret: myEnv.parsed.JWT_SECRET,
    timeout: 15000 // 15 seconds to send the authentication message
  })).on('authenticated', function(socket) {
    var userId = socket.decoded_token.sub;
    console.log('Connected user: ' + userId);

    var redisClient = redis.createClient();
    redisClient.subscribe('user:' + userId);
   
    redisClient.on("message", function(channel, data) {
      console.log("mew message");
      socket.emit('message', data);
    });
  });