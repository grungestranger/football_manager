//var app = require('express')();
//var server = require('http').Server(app);
var port = 8080;
var io = require('socket.io')(port);
var ioJwt = require('socketio-jwt');
var redis = require('redis');
var env = require('dotenv').config({path: './.env'}).parsed;
var mysql = require('mysql').createPool({
    //host: env.DB_HOST,
    user: env.DB_USERNAME,
    password: env.DB_PASSWORD,
    database: env.DB_DATABASE
});
var users = {};

/*mysql.connect();
mysql.query("UPDATE users SET online = 0 WHERE type = 'man' AND online != 0", function (error, results, fields) {
   mysql.end();
});*/
//mysql.end();
mysqlPool.getConnection(function(err, connection) {
   connection.query("UPDATE users SET online = 0 WHERE type = 'man' AND online != 0", function(err, rows) {
      connection.end();
   });
});

io.use(ioJwt.authorize({
   secret: env.JWT_SECRET,
   handshake: true
}));

io.on('connection', function(socket) {
   var userId = socket.decoded_token.sub;

   socketHandler('connect', userId);

   var subscriber = redis.createClient();
   subscriber.subscribe('user:' + userId);
   subscriber.on('message', function(channel, data){
      console.log('New message for user: ' + userId);
      socket.emit('common', data);
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
      mysql.query("UPDATE users SET online = " + (event == 'connect' ? 1 : 0) + " WHERE type = 'man' AND id = " + parseInt(userId));
   }
   users[userId].countConn += event == 'connect' ? 1 : -1;
   console.log('User ' + event + 'ed: ' + userId);
}

//server.listen(port);

// Nodemon



/*
      Object.keys(io.sockets.sockets).forEach(function(s) {
         io.sockets.sockets[s].emit('common', data);
      });
*/
