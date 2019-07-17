var app = require('express')();
var http = require('http').createServer(app);
var io = require('socket.io')(http);
var ejs = require('ejs');
var fs = require('fs');
var index_file = fs.readFileSync(__dirname + '/index.ejs', 'utf8');
var moment = require('moment');
const uuidv4 = require('uuid/v4');
var mysql = require('mysql');
var con = mysql.createConnection({
	host: "localhost",
	user: "ilias",
	database: "ilias",
	password: "ilias"
});
con.connect();

var tokens = [];


app.get('/srchat/:token', function(req, res){
	console.log(req.params.token);
	var ts = Math.round(new Date().getTime() / 1000);
	con.query('SELECT * FROM sr_chat_token WHERE token = "' + req.params.token + '"', function(err, rows, fields) {
		if (!err) {
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
				con.query('SELECT' +
					' m.*, u.login, u.firstname, u.lastname, p.value' +
					' FROM' +
					' sr_chat_message m' +
					' LEFT JOIN' +
					' usr_data u ON u.usr_id = m.usr_id' +
					' LEFT JOIN' +
					' usr_pref p ON p.usr_id = u.usr_id AND p.keyword = "public_profile" AND (p.value = "y" OR p.value = "g")' +
					' WHERE m.chat_room_id = ' + rows[0].chat_room_id +
					' ORDER BY sent_at ASC', function(err, rows, fields) {
					if (!err) {
						rows = rows.map(function(row) {
							if (row.login == null) {
								var public_name = '[deleted]';
							} else if (row.value == null) {
								var public_name = row.login;
							} else {
								var public_name = row.firstname + ' ' + row.lastname + ' (' + row.login + ')';
							}
							
							return Object.assign(
								{},
								row,
								{
									sent_at: moment(row.sent_at).format('HH:mm:ss'),
									public_name: public_name
								});
						});
						return res.send(ejs.render(index_file, {token: req.params.token, messages: rows}));
					} else {
						console.log('error while performing query. ', err.message);
					}
				})
			}
		} else {
			console.log('error while performing query. ', err.message);
		}
	});

	con.query('DELETE FROM sr_chat_token WHERE token = "' + req.params.token + '"');
	con.query('DELETE FROM sr_chat_token WHERE valid_until_unix < ' + ts);
});

io.use(function(socket, next) {
	if (typeof socket.handshake.query === 'undefined' || typeof socket.handshake.query.token !== 'string') {
		console.log('missing token in handshake');
		return next(new Error('missing token in handshake'));
	}

	console.log( socket.handshake.query);

	var token = socket.handshake.query.token;
	if (!(token in tokens)) {
		console.log('token not found in database: ' + token);
		return next(new Error('token not found in database'));
	}

	user_info = tokens[token];
	if (typeof user_info.usr_id !== "number" || typeof user_info.chat_room_id !== "number" || typeof user_info.public_name !== "string" ) {
		console.log('token information invalid or incomplete');
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
		var today = new Date();
		var date = today.getFullYear()+'-'+(today.getMonth()+1)+'-'+today.getDate();
		var time = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();
		var sent_at = date+' '+time;

		io.to('sr_chat_' + socket.chat_room_id).emit('chat_msg', {public_name: socket.public_name, msg: msg, sent_at: time});
		con.query('INSERT INTO sr_chat_message (id, chat_room_id, usr_id, message, sent_at) ' +
			'VALUES ("' + uuidv4() + '",' + socket.chat_room_id + ',' + socket.usr_id + ',"' + msg + '", "' + sent_at + '")');
	});
});

http.listen(3000, function(){
	console.log('listening on *:3000');
});