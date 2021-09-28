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
		description: 'IP address which the chat server listens to - will be used as host address (if != 0.0.0.0)',
		type: 'string',
		default: '0.0.0.0'
	})
	.option('use-https', {
		description: 'set if the node server should use ssl',
		type: 'boolean',
		default: 0,
	})
	.option('ssl-key-path', {
		description: 'necessary for using https',
		type: 'string'
	})
	.option('ssl-cert-path', {
		description: 'necessary for using https',
		type: 'string'
	})
	.option('ssl-passphrase', {
		description: 'passphrase for ssl key',
		type: 'string'
	})
	.demandOption(['client-id'])
	.help()
	.alias('help', 'h')
	.argv;

const client_id = argv.clientId;
const ilias_installation_dir = argv.iliasDir ? argv.iliasDir.replace(/\/+$/,'') : '/var/www/ilias';
const port = argv.port;
const ip = argv.ip;
if (argv.useHttps && (!(typeof argv.sslKeyPath == 'string') || !(typeof argv.sslCertPath == 'string') || !(typeof argv.sslPassphrase == 'string'))) {
	console.error('please define a ssl-key-path, ssl-cert-path and ssl-passphrase when using https');
	process.exit(1);
}
const protocol = argv.useHttps ? 'https' : 'http';
require('console-stamp')(console, 'yyyy-mm-dd HH:MM:ss.l');
const fs = require('fs');
const util = require('util');
fs.readFile = util.promisify(fs.readFile);
const ejs = require('ejs');
const moment = require('moment');
const index_file = fs.readFileSync(__dirname + '/templates/index.ejs', 'utf8');
const QueryUtils = require('./modules/QueryUtils.js');
QueryUtils.init(client_id, ilias_installation_dir);
QueryUtils.writeChatServerConfig(ip, port, protocol);
const express = require('express');
const app = express();

if (argv.useHttps) {
	async function createServerHTTPS() {
		return require(protocol).createServer({
			key: await fs.readFile(argv.sslKeyPath),
			cert: await fs.readFile(argv.sslCertPath),
			passphrase: argv.sslPassphrase
		}, app);
	}
	createServerHTTPS().then(server => {initServer(server)});
} else {
	initServer(require(protocol).createServer(app));
}

function initServer(server) {
	const io = require('socket.io')(server);

	app.use(express.static(__dirname + '/public'));

	/**
	 * connection check
	 */
	app.get('/srchat/check_connection', function(req, res) {
		res.status(200).end();
	});

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
			console.error(err);
			// fallback picture
			res.sendFile(ilias_installation_dir + "/templates/default/images/no_photo_xsmall.jpg")
		}
	});

	/**
	 * check token and get old messages
	 */
	app.get('/srchat/open_chat/:token', async function(req, res){
		try {
			var token = await QueryUtils.checkAndFetchToken(req.params.token);
			var response = {
				token: req.params.token,
				client_id: client_id,
				base_url: protocol + '://' + req.hostname,
				public_name: token.public_name,
				usr_id: token.usr_id,
				chat_room_id: token.chat_room_id,
				messages: await QueryUtils.getOldMessages(token.chat_room_id)
			};
			return res.send(ejs.render(index_file, response));
		} catch (e) {
			console.warn('check failed for token ' + req.params.token + ' with error message: '  + e.message);
			res.sendFile(__dirname + '/templates/error.html');
		}
	});

	/**
	 * open socket / authenticate
	 */
	io.use(async function(socket, next) {
		if (typeof socket.handshake.query === 'undefined' || typeof socket.handshake.query.token !== 'string') {
			console.error('missing token in handshake');
			return next(new Error('missing token in handshake'));
		}

		try {
			var token = await QueryUtils.checkAndFetchToken(socket.handshake.query.token);
		} catch (e) {
			console.error(e.message);
			return next(new Error(e.message));
		}

		socket.usr_id = token.usr_id;
		socket.chat_room_id = token.chat_room_id;
		socket.public_name = token.public_name;

		return  next();
	});

	/**
	 * build socket
	 */
	io.on('connection', function(socket){
		socket.join('sr_chat_' + socket.chat_room_id);

		socket.on('disconnect', function(){
			// TODO: cleanup rooms
		});

		socket.on('chat_msg', function(msg){
			var sent_at_short = moment().format('HH:mm');
			var sent_at_long = moment().format('YYYY-MM-DD HH:mm:ss');
			
			io.to('sr_chat_' + socket.chat_room_id).emit('chat_msg', {
				public_name: socket.public_name,
				msg: msg,
				sent_at: sent_at_short,
				usr_id: socket.usr_id
			});


			QueryUtils.insertMessage(socket.chat_room_id, socket.usr_id, msg, sent_at_long);
		});
	});

	/**
	 * listen
	 */
	server.listen(port, ip, 511, function(){
		console.log('listening on ' + protocol + '://' + ip + ':' + port );
	});
}