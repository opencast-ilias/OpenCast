var client_id = process.argv[2];
var ilias_installation_dir = process.argv[3];
if (!(typeof client_id === "string") || !(typeof ilias_installation_dir === "string")) {
	console.log('Usage: node [path_to]/index.js [ilias_client_id] [ilias_installation_dir]');
	process.exit();
}
ilias_installation_dir.replace(/\/+$/,''); // remove trailing '/'
var express = require('express');
var app = express();
var http = require('http').createServer(app);
var io = require('socket.io')(http);
var ejs = require('ejs');
var fs = require('fs');
var index_file = fs.readFileSync(__dirname + '/index.ejs', 'utf8');
QueryUtils = require(__dirname + '/QueryUtils');
var QueryUtils = new QueryUtils(client_id, ilias_installation_dir);


var tokens = [];

app.use(express.static(__dirname + '/public'));

/**
 * get profile picture of user
 */
app.get('/srchat/get_profile_picture/:usr_id', function(req, res) {
	var path = ilias_installation_dir + "/data/" + client_id + "/usr_images/usr_" + req.params.usr_id + "_xsmall.jpg";
	try {
		if (fs.existsSync(path)) {
			res.sendFile(path);
		} else {
			// fallback picture
			res.sendFile(ilias_installation_dir + "/templates/default/images/no_photo_xsmall.jpg")
		}
	} catch(err) {
		// fallback picture
		res.sendFile(ilias_installation_dir + "/templates/default/images/no_photo_xsmall.jpg")
	}
});

/**
 * check token and get old messages
 */
app.get('/srchat/open_chat/:token', function(req, res){
	QueryUtils.checkTokenAndFetchMessages(req.params.token, function(response, success) {
		if (success) {
			tokens[req.params.token] = {
				public_name: response.public_name,
				usr_id: response.usr_id,
				chat_room_id: response.chat_room_id
			};
			// response.style_sheet_location = __dirname + '/chat.css';
			response.client_id = client_id;
			response.base_url = req.protocol + '://' + req.hostname;
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

		io.to('sr_chat_' + socket.chat_room_id).emit('chat_msg', {
			public_name: socket.public_name,
			msg: msg,
			sent_at: time,
			usr_id: socket.usr_id
		});

		QueryUtils.insertMessage(socket.chat_room_id, socket.usr_id, msg, sent_at);
	});
});

/**
 * listen
 */
http.listen(3000, function(){
	console.log('listening on *:3000');
});