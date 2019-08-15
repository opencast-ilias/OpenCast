/**
 * Opencast Chatserver main
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */

/**
 * Initialisation
 */
const yargs = require('yargs');
const argv = yargs
	.option('client-id', {
		description: 'ILIAS Client ID',
		alias: 'c',
		type: 'string'
	})
	.option('ilias-dir', {
		description: 'root dir of this ILIAS installation',
		alias: 'd',
		type: 'string',
		default: '/var/www/ilias'
	})
	.option('port', {
		description: 'port which the chat server listens to',
		alias: 'p',
		type: 'number',
		default: 3000
	})
	.option('ip', {
		description: 'IP address which the chat server listens to',
		type: 'string',
		default: '0.0.0.0'
	})
	.option('use-http', {
		description: 'set if your ILIAS installation uses http (e.g. for local development or reverse proxies)',
		type: 'boolean',
		default: 0,
	})
	.demandOption(['client-id'])
	.help()
	.alias('help', 'h')
	.argv;

const client_id = argv.clientId;
const ilias_installation_dir = argv.iliasDir ? argv.iliasDir.replace(/\/+$/,'') : '/var/www/ilias';
const port = argv.port;
const ip = argv.ip;
const protocol = argv.useHttp ? 'http' : 'https';

const express = require('express');
const app = express();
const http = require(protocol).createServer(app);
const io = require('socket.io')(http);
const ejs = require('ejs');
const fs = require('fs');
const index_file = fs.readFileSync(__dirname + '/templates/index.ejs', 'utf8');
const QueryUtils = require('./modules/QueryUtils.js');
QueryUtils.init(client_id, ilias_installation_dir);

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
			response.base_url = protocol + '://' + req.hostname;
			return res.send(ejs.render(index_file, response));
		} else {
			console.log(response);
			res.sendFile(__dirname + '/templates/error.html');
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
http.listen(port, ip, 511, function(){
	console.log('listening on ' + ip + ':' + port );
});