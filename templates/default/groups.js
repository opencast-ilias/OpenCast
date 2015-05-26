/**
 * xoctGroup JS Lib
 *
 * @type {{init: Function, selected_id: number, data_url: string, load: Function, deleteGroup: Function, selectGroup: Function, deselectAll: Function, create: Function}}
 */
var xoctGroup = {
	selected_id: 0,
	data_url: '',
	container: null,
	lng: {
		delete_group: "Delete Group?",
		no_title: "Please insert title"
	},
	before_load: function () {
	},
	after_load: function () {
	},
	language: function (lng) {
		this.lng = lng;
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

	load: function () {
		var self = this;
		this.before_load();
		var url = this.data_url;
		$.ajax({url: url, type: "GET", data: {"cmd": "getAll"}}).done(function (data) {
			self.container.empty();
			for (var i in data) {
				self.container.append('<a class="list-group-item xoct_group" data-group-id="'+ data[i].id + '">'
				+ data[i].title
				//+ '<button class="btn btn-danger xoct_group_delete pull-right"><span class="glyphicon glyphicon-remove"></span></button>'
				+ '<span class="glyphicon xoct_group_delete glyphicon-remove pull-right"></span>'
				+ '<span class="glyphicon pull-right">5</span>'
				+ '</li>');
				//+ '<span class="badge xoct_group_delete"><span class="glyphicon glyphicon-remove"></span></span></a>');
			}
			self.after_load();
		});
	},
	deleteGroup: function (id, fallback) {
		var url = this.data_url;
		var self = this;
		if (confirm(this.lng.delete_group)) {
			this.before_load();
			$.ajax({url: url, type: "GET", data: {"cmd": "delete", "id": id}}).done(function (data) {
				if (data) {
					$('[data-group-id="' + id + '"]').remove();
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
	create: function (title, before, after) {
		if (!title) {
			alert(this.lng.no_title);
			return;
		}
		var self = this;
		var url = this.data_url;
		this.deselectAll();
		this.before_load();
		$.ajax({url: url + "&cmd=create", type: "POST", data: {"title": title}}).done(function (data) {
			this.after_load();
			self.load();
		});
	}
};
