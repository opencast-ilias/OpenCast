/**
 * xoctChangeOwner JS Lib
 *
 * @type {{init: Function, selected_id: number, data_url: string, load: Function, deleteGroup: Function, selectGroup: Function, deselectAll: Function, create: Function}}
 */
var xoctChangeOwner = {
	selected_id: 0,
	data_url: '',
	container: null,
    filter_container: null,
    filtering: false,
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
	init: function (data_url, container_owner, container_available, before_load, after_load) {
		if (typeof before_load != 'undefined') {
			this.before_load = before_load;
		}
		if (typeof after_load != 'undefined') {
			this.after_load = after_load;
		}

		this.data_url = data_url;
		$(container_owner).html('<ul id="xoct_owner" class="list-group"></ul>');
		$(container_available).html('<ul id="xoct_available" class="list-group"></ul>');
		this.container_owner = $('#xoct_owner_container');
		this.container_available = $('#xoct_available');
        this.filter_container = $('#xoct_participant_filter');
		this.load();

		var self = this;
		$(document).on('click', '.xoct_remove', function () {
			self.removeOwner($(this).parent().data('invitation-id'));
		});

		$(document).on('click', '.xoct_invite', function () {
			self.setOwner($(this).parent().data('invitation-id'));
		});

		$.expr[':'].contains = function (a, i, m) {
			return $(a).text().toUpperCase()
			.indexOf(m[3].toUpperCase()) >= 0;
		};

			this.filter_container.keyup(function () {
					self.filter($(this).val());
					if (self.filtering && !$('#xoct_filter').length) {
							self.filter_container.after('<span class="input-group-btn"><button class="btn btn-default" id="xoct_filter" type="button"><span class="glyphicon glyphicon-remove"></span> </button></span>');
					} else if (!self.filtering) {
							$('#xoct_filter').remove();
					}
			});


			$(document).on('click', '#xoct_filter', function () {
					self.filter_container.val('');
					self.filter('');
					$(this).remove();
			});

	},
	clear: function () {
		this.container_owner.empty();
		this.container_available.empty();
	},

	load: function () {
		var self = this;
		this.before_load();
		var url = this.data_url;
		$.ajax({url: url, type: "GET", data: {"cmd": "getAll"}}).done(function (data) {
			self.clear();

			owner = data.owner;
			if (owner.name && owner.id) {
				self.container_owner.append('<li class="list-group-item" data-invitation-id="' + owner.id + '">'
					+ '<div style="margin-right:30px;">'
					+ owner.name
					+ '</div>'
					+ '<button class="btn btn-default xoct_remove pull-right"><span class="glyphicon glyphicon-minus"></span></button>'
					+ '</li>');
				self.container_available.html('<li class="list-group-item">' + self.lng.only_one_owner + '</li>');
			} else {
				for (var i in data.available) {
					self.container_available.append('<li class="list-group-item xoct_participant_available" data-invitation-id="' + data.available[i].id + '">'
						+ '<div style="margin-right:30px;">'
						+ data.available[i].name
						+ '</div>'
						+ '<button class="btn btn-primary xoct_invite pull-right"><span class="glyphicon glyphicon-plus"></span></button>'
						+ '</li>');

				}
			}

			if (!data.available || data.available.length == 0) {
				self.container_available.html('<li class="list-group-item">' + self.lng.none_available + '</li>');
			}

			if (!data.owner || data.owner.length == 0) {
				self.container_owner.html('<li class="list-group-item">' + self.lng.none_available + '</li>');
			}

			self.after_load();
		});
	},

	setOwner:function (id) {
		var url = this.data_url;
		var self = this;
		this.before_load();

		$.ajax({url: url + "&cmd=setOwner", type: "GET", data: {"user_id": id}}).done(function (data) {
			self.load();
			self.after_load();
		});
	},

	removeOwner:function (id) {
		var url = this.data_url;
		var self = this;
		this.before_load();
		$.ajax({url: url + "&cmd=removeOwner", type: "GET", data: {}}).done(function (data) {
			$('[data-invitation-id="' + id + '"]').remove();
			self.load();
			self.after_load();
		});
	},

    /**
     *
     * @param string
     */
    filter: function (string) {
        this.filtering = (string != '');
        $('.xoct_participant_available:not(:contains("' + string + '"))').hide();
        $('.xoct_participant_available:contains("' + string + '")').show();
    }
};
