var client_id = process.argv[2];
if (!(typeof client_id === "string")) {
	console.log('Please pass a client ID as an argument');
	process.exit();
}
var app = require('express')();
var http = require('http').createServer(app);
var io = require('socket.io')(http);
var ejs = require('ejs');
var fs = require('fs');
var index_file = fs.readFileSync(__dirname + '/index.ejs', 'utf8');
QueryUtils = require(__dirname + '/QueryUtils');
var QueryUtils = new QueryUtils();


var tokens = [];

/**
 * send stylesheet
 */
app.get('/srchat/css', function(req, res) {
	res.sendFile(__dirname + '/chat.css');
});

/**
 * check token and get old messages
 */
app.get('/srchat/:token', function(req, res){
	QueryUtils.checkTokenAndFetchMessages(req.params.token, function(response, success) {
		if (success) {
			tokens[req.params.token] = {
				public_name: response.public_name,
				usr_id: response.usr_id,
				chat_room_id: response.chat_room_id
			};
			// response.style_sheet_location = __dirname + '/chat.css';
			response.client_id = client_id;
			return res.send(ejs.render(index_file, response));
		} else {
			console.log(response);
			res.sendFile(__dirname + '/error.html');
		}
	});
});

/**
 * open socket / authenticate
 */
io.use(function(socket, next) {
	if (typeof socket.handshake.query === 'undefined' || typeof socket.handshake.query.token !== 'string') {
		console.log('missing token in handshake');
		return next(new Error('missing token in handshake'));
	}

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

/**
 * build socket
 */
io.on('connection', function(socket){
	console.log('user connected');
	socket.join('sr_chat_' + socket.chat_room_id);

	socket.on('disconnect', function(){
		console.log('user disconnected');
	});

	socket.on('chat_msg', function(msg){
		var today = new Date();
		var date = today.getFullYear()+'-'+(today.getMonth()+1)+'-'+today.getDate();
		var time = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();
		var sent_at = date+' '+time;

		io.to('sr_chat_' + socket.chat_room_id).emit('chat_msg', {public_name: socket.public_name, msg: msg, sent_at: time, usr_id: socket.usr_id});

		QueryUtils.insertMessage(socket.chat_room_id, socket.usr_id, msg, sent_at);
	});
});

/**
 * listen
 */
http.listen(3000, function(){
	console.log('listening on *:3000');
});