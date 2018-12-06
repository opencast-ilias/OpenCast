/**
 * xoctInvitation JS Lib
 *
 * @type {{init: Function, selected_id: number, data_url: string, load: Function, deleteGroup: Function, selectGroup: Function, deselectAll: Function, create: Function}}
 */
var xoctInvitation = {
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
	init: function (data_url, container_invited, container_available, before_load, after_load) {
		if (typeof before_load != 'undefined') {
			this.before_load = before_load;
		}
		if (typeof after_load != 'undefined') {
			this.after_load = after_load;
		}

		this.data_url = data_url;
		$(container_invited).html('<ul id="xoct_invitations" class="list-group"></ul>');
		$(container_available).html('<ul id="xoct_available" class="list-group"></ul>');
		this.container_invited = $('#xoct_invitations');
		this.container_available = $('#xoct_available');
        this.filter_container = $('#xoct_participant_filter');
		this.load();

		$(document).on('click', '.xoct_remove', function () {
			xoctInvitation.removeInvitation($(this).parent().data('invitation-id'));
		});

		$(document).on('click', '.xoct_invite', function () {
			xoctInvitation.addInvitation($(this).parent().data('invitation-id'));
		});

        this.filter_container.keyup(function () {
            xoctInvitation.filter($(this).val());
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
		this.container_invited.empty();
		this.container_available.empty();
	},

	load: function () {
		var self = this;
		this.before_load();
		var url = this.data_url;
		$.ajax({url: url, type: "GET", data: {"cmd": "getAll"}}).done(function (data) {
			self.clear();
			for (var i in data.available) {
				self.container_available.append('<li class="list-group-item xoct_participant_available" data-invitation-id="' + data.available[i].id + '">'
					+ '<div style="margin-right:30px;">'
					+ data.available[i].name
					+ '</div>'
					+ '<button class="btn btn-primary xoct_invite pull-right"><span class="glyphicon glyphicon-plus"></span></button>'
					+ '</li>');
			}

			for (var i in data.invited) {
				self.container_invited.append('<li class="list-group-item" data-invitation-id="' + data.invited[i].id + '">'
					+ '<div style="margin-right:30px;">'
					+ data.invited[i].name
					+ '</div>'
					+ '<button class="btn btn-default xoct_remove pull-right"><span class="glyphicon glyphicon-minus"></span></button>'
					+ '</li>');
			}

			if (!data.available || data.available.length == 0) {
				self.container_available.html('<li class="list-group-item">' + self.lng.none_available + '</li>');
			}

			if (!data.invited || data.invited.length == 0) {
				self.container_invited.html('<li class="list-group-item">' + self.lng.none_available + '</li>');
			}

			self.after_load();
		});
	},

	addInvitation:function (id) {
		var url = this.data_url;
		var self = this;
		this.before_load();

		$.ajax({url: url + "&cmd=create", type: "POST", data: {"id": id}}).done(function (data) {
			console.log(data);
			self.load();
			self.after_load();
		});
	},

	removeInvitation:function (id) {
		var url = this.data_url;
		var self = this;
		this.before_load();
		$.ajax({url: url + "&cmd=delete", type: "POST", data: {"id": id}}).done(function (data) {
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
        console.log('filtering: ' + string);
        $('.xoct_participant_available:not(:contains("' + string + '"))').hide();
        $('.xoct_participant_available:contains("' + string + '")').show();
    }

	//deleteGroup: function (id, fallback) {
	//	var url = this.data_url;
	//	var self = this;
	//	if (confirm(this.lng['delete_group'])) {
	//		this.before_load();
	//		$.ajax({url: url, type: "GET", data: {"cmd": "delete", "id": id}}).done(function (data) {
	//			if (data) {
	//				$('[data-invitation-id="' + id + '"]').remove();
	//				self.load();
	//			}
	//			self.after_load();
	//		});
	//	}
	//},
	//
	//selectGroup: function (id, force) {
	//	force = typeof(force) == 'undefined' ? false : force;
	//	if (this.selected_id == id && !force) {
	//		this.deselectAll();
	//		this.selected_id = 0;
	//	} else {
	//		this.selected_id = id;
	//		this.deselectAll();
	//		xoctInvitationParticipant.loadForGroupId(id);
	//		$('[data-invitation-id="' + id + '"]').addClass('active');
	//	}
	//},
	//
	//deselectAll: function () {
	//	$('.xoct_group').each(function () {
	//		$(this).removeClass('active');
	//	});
	//	xoctInvitationParticipant.clear();
	//},
	///**
	// * Create a New Group
	// * @param title
	// * @param before
	// * @param after
	// */
	//addInvitation: function (title, fallback) {
	//	if (!title) {
	//		alert(this.lng['no_title']);
	//		return;
	//	}
	//	var self = this;
	//	var url = this.data_url;
	//	this.deselectAll();
	//	this.before_load();
	//	$.ajax({url: url + "&cmd=create", type: "POST", data: {"title": title}}).done(function (data) {
	//		self.load(function () {
	//			self.selectGroup(data.id, true);
	//		});
	//		self.after_load();
	//		fallback(data);
	//	});
	//}
};
