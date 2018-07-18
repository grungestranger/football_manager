var port = 8080;
var io = require('socket.io')(port);
var jwt = require('jsonwebtoken');
var redis = require('redis');
var env = require('dotenv').config({path: __dirname + '/.env'}).parsed;
var mysql = require('mysql').createPool({
    user: env.DB_USERNAME,
    password: env.DB_PASSWORD,
    database: env.DB_DATABASE
});
var users = {};
var timeoutMs = 10000;
var intervalMs = 60000;

var subscriber = redis.createClient();
subscriber.subscribe('all');
subscriber.on('message', function(channel, data) {
    log('New message for all');
    sendToAll(data);
});

// Cleare online
mysqlQuery("UPDATE users SET online = 0 WHERE type = 'man' AND online != 0");

io.on('connection', function(socket) {
    socket.user = {};

    requireToken(socket);

    socket.on('disconnect', function() {
        clearTimers(socket);
        if (socket.user.id) {
            socketHandler(socket, 'disconnect');
        }
    });

    socket.on('token', function(token) {
        jwt.verify(token, env.JWT_SECRET, function(err, decoded) {
            if (decoded) {
                clearTimers(socket);
                if (!socket.user.id) {
                    socket.user.id = decoded.sub;
                    socketHandler(socket, 'connect');
                }
                var ms = decoded.exp * 1000 - Date.now();
                socket.user.timeout = setTimeout(function() {
                    requireToken(socket);
                }, ms);
            } else {
                log('Wrong token from ' + socket.id);
            }
        });
    });
});

function log(str) {
    console.log(str);
}

function requireToken(socket) {
    if (socket.user.id) {
        socket.user.timeout = setTimeout(function() {
            socketHandler(socket, 'disconnect');
            socket.user.id = null;
        }, timeoutMs);
    }
    needToken(socket);
    socket.user.interval = setInterval(function() {
        needToken(socket);
    }, intervalMs);
}

function needToken(socket) {
    socket.emit('needToken');
    log('Need token to ' + socket.id);
}

function clearTimers(socket) {
    if (socket.user.timeout) {
        clearTimeout(socket.user.timeout);
    }
    if (socket.user.interval) {
        clearInterval(socket.user.interval);
    }
}

function socketHandler(socket, event) {
    var userId = socket.user.id;
    if (event == 'connect') {
        var subscriber = redis.createClient();
        subscriber.subscribe('user:' + userId);
        subscriber.on('message', function(channel, data) {
            log('New message for user: ' + userId);
            socket.emit('message', data);
        });
        socket.user.subscriber = subscriber;
    } else {
        socket.user.subscriber.quit();
    }
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
            user: {
                id: userId,
            },
        }));
    }
    users[userId].countConn += event == 'connect' ? 1 : -1;
    log('User ' + event + 'ed: ' + userId + ' (' + socket.id + ')');
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
        if (socket.user.id) {
            socket.emit('message', data);
        }
    });
}

// Nodemon
