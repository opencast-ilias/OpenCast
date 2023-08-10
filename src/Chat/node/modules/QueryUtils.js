/**
 *  some database query functions
 *  initializes itself by reading the client.ini.php
 */
var QueryUtils = {

    uuidv4: require('uuid/v4'),
    moment: require('moment'),
    mysql: require('mysql'),
    con: '',
    tokens: [],

    init: function (client_id, ilias_installation_dir) {
        var fs = require('fs'),
            ini = require('ini'),
            util = require('util');
        var client_ini = ini.parse(fs.readFileSync(ilias_installation_dir + '/data/' + client_id + '/client.ini.php', 'utf-8'));
        this.con = this.mysql.createPool({
            host: client_ini.db.host,
            user: client_ini.db.user,
            database: client_ini.db.name,
            password: client_ini.db.pass
        });
        this.con.query = util.promisify(this.con.query);
    },

    writeChatServerConfig: async function (ip, port, protocol, host) {
        await this.con.query('INSERT INTO sr_chat_config (name, value) VALUES ("ip", "' + ip + '") ON DUPLICATE KEY UPDATE value = "' + ip + '"');
        await this.con.query('INSERT INTO sr_chat_config (name, value) VALUES ("port", "' + port + '") ON DUPLICATE KEY UPDATE value = "' + port + '"');
        await this.con.query('INSERT INTO sr_chat_config (name, value) VALUES ("protocol", "' + protocol + '") ON DUPLICATE KEY UPDATE value = "' + protocol + '"');
        await this.con.query('INSERT INTO sr_chat_config (name, value) VALUES ("host", "' + host + '") ON DUPLICATE KEY UPDATE value = "' + host + '"');
    },

    getOldMessages: async function (chat_room_id) {
        var con = this.con;
        var moment = this.moment;
        var rows = await con.query('SELECT' +
            ' m.*, u.login, u.firstname, u.lastname, p.value' +
            ' FROM' +
            ' sr_chat_message m' +
            ' LEFT JOIN' +
            ' usr_data u ON u.usr_id = m.usr_id' +
            ' LEFT JOIN' +
            ' usr_pref p ON p.usr_id = u.usr_id AND p.keyword = "public_profile" AND (p.value = "y" OR p.value = "g")' +
            ' WHERE m.chat_room_id = ' + chat_room_id +
            ' ORDER BY sent_at ASC');

        rows = rows.map(function (row) {
            if (row.login == null) {
                var public_name = '[deleted]';
            } else if (row.value == null) {
                var public_name = row.login;
            } else {
                var public_name = row.firstname + ' ' + row.lastname;
            }

            return Object.assign(
                {},
                row,
                {
                    sent_at: moment(row.sent_at).format('HH:mm'),
                    public_name: public_name
                });
        });
        return rows;
    },

    loadTokens: async function () {
        let utils = this;
        var rows = await this.con.query('SELECT * FROM sr_chat_token');
        utils.tokens = [];
        rows.forEach(function (element) {
            utils.tokens[element.token] = element;
        });
        await utils.cleanupTokens();

    },

    cleanupTokens: async function () {
        var ts = Math.round(new Date().getTime() / 1000);
        await this.con.query('DELETE FROM sr_chat_token WHERE valid_until_unix < ' + ts);
    },

    checkAndFetchToken: async function (token) {
        var ts = Math.round(new Date().getTime() / 1000);
        if (!(token in this.tokens)) {
            await this.loadTokens();
        }
        var loaded_token = this.tokens[token];
        if ((typeof loaded_token !== 'undefined') && (loaded_token.valid_until_unix > ts)) {
            return loaded_token;
        } else {
            throw new Error('Token invalid' + ((typeof loaded_token !== 'undefined') ? ' (expired)' : ''));
        }
    },

    insertMessage: async function (chat_room_id, usr_id, msg, sent_at) {
        await this.con.query('INSERT INTO sr_chat_message (id, chat_room_id, usr_id, message, sent_at) ' +
            'VALUES ("' + this.uuidv4() + '",' + chat_room_id + ',' + usr_id + ',"' + msg + '", "' + sent_at + '")');
    }
}

module.exports = QueryUtils;
