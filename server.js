var port = 8080;
var io = require('socket.io')(port);
var jwt = require('jsonwebtoken');
var redis = require('redis');
var env = require('dotenv').config({path: './.env'}).parsed;
var mysql = require('mysql').createPool({
    user: env.DB_USERNAME,
    password: env.DB_PASSWORD,
    database: env.DB_DATABASE
});
var users = {};
var timeoutMs = 10000;
var intervalMs = 60000;

// Cleare online
mysqlQuery("UPDATE users SET online = 0 WHERE type = 'man' AND online != 0");

io.on('connection', function(socket) {
    requireToken(socket);

    socket.on('disconnect', function() {
        if (socket.userId) {
            socketHandler('disconnect', socket.userId);
            socket.subscriber.quit();
        }
    });

    socket.on('token', function(token) {
        jwt.verify(token, env.JWT_SECRET, function(err, decoded) {
            if (decoded) {
                if (socket.timeout) {
                    clearTimeout(socket.timeout);
                }
                if (socket.interval) {
                    clearTimeout(socket.interval);
                }
                if (!socket.userId) {
                    var userId = decoded.sub;
                    var subscriber = redis.createClient();
                    subscriber.subscribe('user:' + userId);
                    subscriber.on('message', function(channel, data) {
                        console.log('New message for user: ' + userId);
                        socket.emit('app', data);
                    });
                    socket.userId = userId;
                    socket.subscriber = subscriber;
                    socketHandler('connect', userId);
                }
                var ms = decoded.exp * 1000 - Date.now();
                setTimeout(function() {
                    requireToken(socket);
                }, ms);
            } else {
                console.log('Wrong token.');
            }
        });
    });
});

function requireToken(socket) {
    if (socket.userId) {
        socket.timeout = setTimeout(function() {
            socket.userId = null;
            socket.subscriber.quit();
            socketHandler('disconnect', socket.userId);
        }, timeoutMs);
    }
    socket.emit('needToken');
    socket.interval = setInterval(function() {
        socket.emit('needToken');
    }, intervalMs);
}

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

function sendToAll(data) {
    Object.keys(io.sockets.sockets).forEach(function(key) {
        var socket = io.sockets.sockets[key];
        if (socket.userId) {
            socket.emit('app', data);
        }
    });
}

// Nodemon
