var srChatroom = require('./srChatroom');

function srChatroomManager() {
	this.chatrooms = [];
}

srChatroomManager.prototype.getOrCreateChatroom = function(chatroom_id) {
	if (!(chatroom_id in this.chatrooms)) {
		var new_chatroom = new srChatroom(chatroom_id);
		this.chatrooms[chatroom_id] = new_chatroom;
	}
	return this.chatrooms(chatroom_id);
}

module.exports = srChatroomManager;