//var app = require('express')();
//var server = require('http').Server(app);
//server.listen(port);
var port = 8080;
var io = require('socket.io')(port);
var ioJwt = require('socketio-jwt');
var redis = require('redis');
var env = require('dotenv').config({path: './.env'}).parsed;
var mysql = require('mysql').createPool({
    user: env.DB_USERNAME,
    password: env.DB_PASSWORD,
    database: env.DB_DATABASE
});
var users = {};

// Cleare online
mysqlQuery("UPDATE users SET online = 0 WHERE type = 'man' AND online != 0");

io.use(ioJwt.authorize({
   secret: env.JWT_SECRET,
   handshake: true
}));

io.on('connection', function(socket) {
   var userId = socket.decoded_token.sub;

   socketHandler('connect', userId);

   var subscriber = redis.createClient();
   subscriber.subscribe('user:' + userId);
   subscriber.on('message', function(channel, data) {
      console.log('New message for user: ' + userId);
      socket.emit('app', data);
   });

   socket.on('disconnect', function() {
      socketHandler('disconnect', userId);
      subscriber.quit();
   });
});

function socketHandler(event, userId) {
   if (!users[userId]) {
      users[userId] = {countConn: 0}
   }
   if (users[userId].countConn == (event == 'connect' ? 0 : 1)) {
      mysqlQuery(
         "UPDATE users SET online = " + (event == 'connect' ? 1 : 0)
         + " WHERE type = 'man' AND id = " + parseInt(userId)
      );
      sendToAll(JSON.stringify({
         action: event == 'connect' ? 'userConnect' : 'userDisconnect',
         id: userId
      }));
   }
   users[userId].countConn += event == 'connect' ? 1 : -1;
   console.log('User ' + event + 'ed: ' + userId);
}

function mysqlQuery(str, callback) {
   mysql.getConnection(function(err, connection) {
      // Use the connection
      connection.query(str, function (error, results, fields) {
         // And done with the connection.
         connection.release();
         // Handle error after the release.
         if (error) throw error;
         // Don't use the connection here, it has been returned to the pool.
     });
   });
}

function sendToAll(data, channel) {
   if (channel === undefined) {
      channel = 'main';
   }
   Object.keys(io.sockets.sockets).forEach(function(s) {
      io.sockets.sockets[s].emit(channel, data);
   });
}

// Nodemon
