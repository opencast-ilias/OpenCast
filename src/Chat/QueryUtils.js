function QueryUtils(client_id) {
	var fs = require('fs'),
		ini = require('ini');
	var client_ini = ini.parse(fs.readFileSync(__dirname + '/../../../../../../../../../data/' + client_id + '/client.ini.php', 'utf-8'));
	this.uuidv4 = require('uuid/v4');
	this.moment = require('moment');
	this.mysql = require('mysql');
	this.con = this.mysql.createPool({
		host: client_ini.db.host,
		user: client_ini.db.user,
		database: client_ini.db.name,
		password: client_ini.db.pass
	});

}

QueryUtils.prototype.getOldMessages = function(chat_room_id, callback) {
	var con = this.con;
	var moment = this.moment;
	con.query('SELECT' +
		' m.*, u.login, u.firstname, u.lastname, p.value' +
		' FROM' +
		' sr_chat_message m' +
		' LEFT JOIN' +
		' usr_data u ON u.usr_id = m.usr_id' +
		' LEFT JOIN' +
		' usr_pref p ON p.usr_id = u.usr_id AND p.keyword = "public_profile" AND (p.value = "y" OR p.value = "g")' +
		' WHERE m.chat_room_id = ' + chat_room_id +
		' ORDER BY sent_at ASC', function (err, rows, fields) {
		if (!err) {
			rows = rows.map(function (row) {
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
			callback({
				messages: rows
			}, true);
		} else {
			callback({error: 'error while performing query. ' + err.message}, false);
		}
	});
}

QueryUtils.prototype.checkTokenAndFetchMessages = function(token, callback) {
	var ts = Math.round(new Date().getTime() / 1000);
	var con = this.con;
	var utils = this;
	con.query('SELECT * FROM sr_chat_token WHERE token = "' + token + '"', function(err, rows, fields) {
		if (!err) {
			if (rows.length === 0) {
				callback({error: 'invalid token (not found in db): ' + token}, false);
			} else if (rows[0].valid_until_unix < ts) {
				callback({error: 'invalid token (expired): ' + token}, false);
			} else {
				utils.getOldMessages(rows[0].chat_room_id, function(response, success) {
					if (success) {
						callback({
							token: token,
							public_name: rows[0].public_name,
							usr_id: rows[0].usr_id,
							chat_room_id: rows[0].chat_room_id,
							messages: response.messages
						}, true);
					} else {
						callback(response, false);
					}

				});
			}
		} else {
			callback({error: 'error while performing query. ' + err.message}, false);
		}

		con.query('DELETE FROM sr_chat_token WHERE token = "' + token + '"');
		con.query('DELETE FROM sr_chat_token WHERE valid_until_unix < ' + ts);
	});


}

QueryUtils.prototype.insertMessage = function(chat_room_id, usr_id, msg, sent_at) {
	this.con.query('INSERT INTO sr_chat_message (id, chat_room_id, usr_id, message, sent_at) ' +
		'VALUES ("' + this.uuidv4() + '",' + chat_room_id + ',' + usr_id + ',"' + msg + '", "' + sent_at + '")');
}

module.exports = QueryUtils;