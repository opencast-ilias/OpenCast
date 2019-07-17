function srChatroom(id) {
	this.id = id;
	this.users = [];
}

srChatroom.prototype.addUser = function(user) {
	this.users.push(user);
}

module.exports = srChatroom;