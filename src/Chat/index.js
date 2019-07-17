var app = require('express')();
var http = require('http').createServer(app);
var io = require('socket.io')(http);
var ejs = require('ejs');
var fs = require('fs');
var index_file = fs.readFileSync(__dirname + '/index.ejs', 'utf8');
var mysql = require('mysql');
var con = mysql.createConnection({
	host: "localhost",
	user: "ilias",
	database: "ilias",
	password: "ilias"
});
con.connect();

var chat_rooms = [];
var tokens = [];



app.get('/srchat/:token', function(req, res){
	console.log(req.params.token);
	con.query('SELECT * FROM sr_chat_token WHERE token = "' + req.params.token + '"', function(err, rows, fields) {
		if (!err) {
			var ts = Math.round(new Date().getTime() / 1000);
			if (rows.length === 0 || rows[0].valid_until_unix < ts) {
				console.log('invalid token');
				res.sendFile(__dirname + '/error.html');
			} else {
				tokens[rows[0].token] = {
					public_name: rows[0].public_name,
					usr_id: rows[0].usr_id,
					chat_room_id: rows[0].chat_room_id
				};
				console.log('token verified for user ' + rows[0].usr_id);
				res.send(ejs.render(index_file, {token: rows[0].token}));
			}
		} else {
			console.log('error while performing query. ', err.message);
		}

	})

});

io.use(function(socket, next) {
	if (typeof socket.handshake.query === 'undefined' || typeof socket.handshake.query.token !== 'string') {
		return next(new Error('missing token in handshake'));
	}

	var token = socket.handshake.query.token;
	if (!(token in tokens)) {
		return next(new Error('token not found in database'));
	}

	user_info = tokens[token];
	if (typeof user_info.usr_id !== "number" || typeof user_info.chat_room_id !== "number" || typeof user_info.public_name !== "string" ) {
		return next(new Error('token information invalid or incomplete'));
	}

	socket.usr_id = user_info.usr_id;
	socket.chat_room_id = user_info.chat_room_id;
	socket.public_name = user_info.public_name;

	return  next();
});

io.on('connection', function(socket){
	console.log('user connected');
	socket.join('sr_chat_' + socket.chat_room_id);

	socket.on('disconnect', function(){
		console.log('user disconnected');
	});

	socket.on('chat_msg', function(msg){
		console.log('message: ' + msg);
		io.to('sr_chat_' + socket.chat_room_id).emit('chat_msg', {public_name: socket.public_name, msg: msg});
	});
});

http.listen(3000, function(){
	console.log('listening on *:3000');
});