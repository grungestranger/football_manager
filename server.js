var app = require('express')();
var server = require('http').Server(app);
var port = 8080;
var io = require('socket.io')(server);
var ioJwt = require('socketio-jwt');
var redis = require('redis');
var env = require('dotenv').config({path: './.env'});
var publisher  = redis.createClient();

io.use(ioJwt.authorize({
   secret: env.parsed.JWT_SECRET,
   handshake: true
}));

io.on('connection', function (socket) {
   var userId = socket.decoded_token.sub;

   console.log('User connected: ' + userId);
   publisher.publish('system', 'User connected: ' + userId);

   var subscriber = redis.createClient();
   subscriber.subscribe('user:' + userId);
   subscriber.on('message', function(channel, data){
      console.log('New message for user: ' + userId);
      socket.emit('common', data);
   });

   socket.on('disconnect', function(){
      console.log('User disconnected: ' + userId);
      publisher.publish('system', 'User disconnected: ' + userId);

      //subscriber.quit();
   });
});

server.listen(port);
publisher.publish('system', 'server:start');
