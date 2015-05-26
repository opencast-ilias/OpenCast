/**
 * xoctGroup JS Lib
 *
 * @type {{init: Function, selected_id: number, data_url: string, load: Function, deleteGroup: Function, selectGroup: Function, deselectAll: Function, create: Function}}
 */
var xoctGroup = {
	selected_id: 0,
	data_url: '',
	container: null,
	lng: [
		delete_group = "Delete Group?",
		no_title = "Please insert title",
		none_available = "None available"
	],
	before_load: function () {
	},
	after_load: function () {
	},
	lngFromJson: function (lng_json) {
		this.lng = JSON.parse(lng_json);
	},
	init: function (data_url, container, before_load, after_load) {
		if (typeof before_load != 'undefined') {
			this.before_load = before_load;
		}
		if (typeof after_load != 'undefined') {
			this.after_load = after_load;
		}

		this.data_url = data_url;
		$(container).html('<ul id="xoct_groups" class="list-group"></ul>');
		this.container = $('#xoct_groups');
		this.load();


		$(document).on('click', '.xoct_group_delete', function () {
			xoctGroup.deleteGroup($(this).parent().data('group-id'));
		});

		$(document).on('click', '.xoct_group', function () {
			xoctGroup.selectGroup($(this).data('group-id'));
		});

	},
	clear: function () {
		this.container.empty();
		this.selected_id = 0;
	},

	load: function (fallback, select_current) {
		fallback = typeof(fallback) == 'undefined' ? function () {
		} : fallback;
		select_current = typeof(select_current) == 'undefined' ? false : select_current;
		if (select_current) {
			var selected_storage = this.selected_id;
		}
		var self = this;
		this.before_load();
		var url = this.data_url;
		$.ajax({url: url, type: "GET", data: {"cmd": "getAll"}}).done(function (data) {
			self.clear();
			for (var i in data) {
				self.container.append('<a class="list-group-item xoct_group" data-group-id="' + data[i].id + '">'
				+ data[i].title
				+ '<button class="btn btn-danger xoct_group_delete pull-right"><span class="glyphicon glyphicon-remove"></span></button>'
				+ '<Button class="btn pull-right">' + data[i].user_count + '</button>'
				+ '</li>');
			}
			if (!data || data.length == 0) {
				self.container.html('<li class="list-group-item">' + self.lng['none_available'] + '</li>');
			}
			if (data && data.length == 1) {
				self.selectGroup(data[0].id);
			}

			xoctGroupParticipant.clear();
			xoctGroupParticipant.load();
			self.after_load();
			fallback();
			if (select_current) {
				self.selectGroup(selected_storage);
			}
		});
	},
	deleteGroup: function (id, fallback) {
		var url = this.data_url;
		var self = this;
		if (confirm(this.lng['delete_group'])) {
			this.before_load();
			$.ajax({url: url, type: "GET", data: {"cmd": "delete", "id": id}}).done(function (data) {
				if (data) {
					$('[data-group-id="' + id + '"]').remove();
					self.load();
				}
				self.after_load();
			});
		}
	},

	selectGroup: function (id) {
		if (this.selected_id == id) {
			this.deselectAll();
			this.selected_id = 0;
		} else {
			this.selected_id = id;
			this.deselectAll();
			xoctGroupParticipant.loadForGroupId(id);
			$('[data-group-id="' + id + '"]').addClass('active');
		}
	},

	deselectAll: function () {
		$('.xoct_group').each(function () {
			$(this).removeClass('active');
		});
		xoctGroupParticipant.clear();
	},
	/**
	 * Create a New Group
	 * @param title
	 * @param before
	 * @param after
	 */
	create: function (title, fallback) {
		if (!title) {
			alert(this.lng['no_title']);
			return;
		}
		var self = this;
		var url = this.data_url;
		this.deselectAll();
		this.before_load();
		$.ajax({url: url + "&cmd=create", type: "POST", data: {"title": title}}).done(function (data) {
			self.load(function () {
				self.selectGroup(data.id);
			});
			self.after_load();
			fallback(data);
		});
	}
};
