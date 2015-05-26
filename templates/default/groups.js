/**
 * xoctGroup JS Lib
 *
 * @type {{init: Function, selected_id: number, data_url: string, load: Function, deleteGroup: Function, selectGroup: Function, deselectAll: Function, create: Function}}
 */
var xoctGroup = {

	init: function (data_url) {
		this.data_url = data_url;
	},
	selected_id: 0,
	data_url: '',
	load: function (before, after) {
		if (typeof before == 'undefined') {
			before = function () {
			};
		}
		if (typeof after == 'undefined') {
			after = function () {
			};
		}
		before();
		var url = this.data_url;
		$.ajax({url: url, type: "GET", data: {"cmd": "getAll"}}).done(function (data) {
			$('#xoct_groups').empty();
			for (var i in data) {
				$('#xoct_groups').append('<a class="list-group-item xoct_group" data-group-id="'
				+ data[i].id
				+ '">' + data[i].title + ''
				+ '<span class="badge xoct_group_delete"><span class="glyphicon glyphicon-remove"></span></span></a>');
			}
			after();
		});
	},
	deleteGroup: function (id, after_confirm, fallback) {
		var url = this.data_url;
		if (confirm("{CONFIRM_MESSAGE}")) {
			after_confirm();
			$.ajax({url: url, type: "GET", data: {"cmd": "delete", "id": id}}).done(function (data) {
				if (data) {
					$('[data-group-id="' + id + '"]').remove();
				}
				fallback();
			});
		}
	},

	selectGroup: function (id) {
		this.selected_id = id;
		this.deselectAll();
		$('[data-group-id="' + id + '"]').addClass('active');
	},

	deselectAll: function () {
		$('.xoct_group').each(function () {
			$(this).removeClass('active');
		});
	},
	/**
	 * Create a New Group
	 * @param title
	 * @param before
	 * @param after
	 */
	create: function (title, before, after) {
		if (!title) {
			return;
		}
		var self = this;
		var url = this.data_url;
		this.deselectAll();
		before();
		$.ajax({url: url + "&cmd=create", type: "POST", data: {"title": title}}).done(function (data) {
			after();
			self.load();
		});
	},
	addUser: function (group_id, user_id, before, after) {

	}
};
