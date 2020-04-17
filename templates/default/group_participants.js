/**
 * xoctGroupParticipant JS Lib
 *
 * @type {{init: Function, selected_id: number, data_url: string, load: Function, deleteGroup: Function, selectGroup: Function, deselectAll: Function, create: Function}}
 */
var xoctGroupParticipant = {
    selected_id: 0,
    data_url: '',
    container_available: null,
    container_per_group: null,
    "lng": [
        delete_participant = "Delete Participant?",
        select_group = "Select Group",
        none_available = "No Participants in this Group",
        none_available_all = "No Participants available"
    ],
    filter_container: null,
    filtering: false,
    before_load: function () {
    },
    after_load: function () {
    },
    lngFromJson: function (lng_json) {
        this.lng = JSON.parse(lng_json);
    },
    /**
     *
     * @param data_url
     * @param container_available
     * @param container_per_group
     * @param before_load
     * @param after_load
     */
    init: function (data_url, container_available, container_per_group, before_load, after_load) {
        if (typeof before_load != 'undefined') {
            this.before_load = before_load;
        }
        if (typeof after_load != 'undefined') {
            this.after_load = after_load;
        }

        this.data_url = data_url;
        $(container_available).html('<ul id="xoct_available_participant" class="list-group"></ul>');
        $(container_per_group).html('<ul id="xoct_group_participant" class="list-group"></ul>');
        this.container_available = $('#xoct_available_participant');
        this.container_per_group = $('#xoct_group_participant');
        this.filter_container = $('#xoct_participant_filter');
        this.load();

        //$('.xoct_available_group_participant').
        var self = this;
        $(document).on('click', '.xoct_add_user', function () {
            var user_id = $(this).parent().data('user-id');
            self.addUser(user_id);
        });
        $(document).on('click', '.xoct_remove_user', function () {
            var id = $(this).parent().data('id');
            self.removeUser(id);
        });


        $.expr[':'].Contains = function (a, i, m) {
            return $(a).text().toUpperCase()
                    .indexOf(m[3].toUpperCase()) >= 0;
        };
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
    /**
     *
     * @param id
     */
    removeUser: function (id) {
        if (confirm(this.lng['delete_participant'])) {
            this.before_load();
            var self = this;
            var url = this.data_url;
            $.ajax({url: url + "&cmd=delete", type: "POST", data: {"id": id, "group_id": xoctGroup.selected_id}}).done(function (data) {
                xoctGroup.removeParticipant(id);
                self.after_load();
                console.log('load');
                self.load();
                self.loadForGroupId();
                // xoctGroup.load(function () {
                // }, true);
            });

        }

    },
    /**
     *
     * @param user_id
     */
    addUser: function (user_id) {
        var group_id = xoctGroup.selected_id;
        if (group_id == 0) {
            alert(this.lng['select_group']);
            return;
        }
        this.before_load();
        var self = this;
        var url = this.data_url;
        $.ajax({url: url + "&cmd=create", type: "POST", data: {"user_id": user_id, "group_id": xoctGroup.selected_id}}).done(function (data) {
            xoctGroup.addParticipant(user_id);
            self.after_load();
            self.load();
            self.loadForGroupId(group_id);
        });

    },
    clear: function () {
        this.container_per_group.empty();
        this.container_per_group.html('<li class="list-group-item">' + this.lng['select_group'] + '</li>');
    },
    /**
     *
     * @param optional_group_id
     */
    load: function () {
        var self = this;
        this.before_load();
        self.container_available.empty();
        var participants = xoctGroup.getAvailableParticipantsForSelectedGroup();

        participants.forEach(function(participant) {
            var checkmark = '';
            if (xoctGroup.isInAnyGroup(participant.user_id)) {
                checkmark = '<img class="xoct_checkmark" width="10px" height="10px" ' +
                    'src="./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/images/checkmark.svg"' +
                    '>';
            }
            // if ()
            self.container_available.append(
                '<li class="list-group-item xoct_participant_available" data-user-id="' + participant.user_id + '">'
                + checkmark
                + '<div style="margin-right:30px;">'
                + participant.name + ''
                + '</div>'
                + '<button class="btn btn-primary xoct_add_user pull-right"><span class="glyphicon glyphicon-plus"></span></button>'
                + '</li>');
        });
        if (!participants || participants.length == 0) {
            self.container_available.html('<li class="list-group-item">' + self.lng['none_available_all'] + '</li>');
        }
        self.filter($(self.filter_container).val());
        self.after_load();
    },
    /**
     *
     * @param group_id
     */
    loadForGroupId: function (group_id) {
        var self = this;
        this.before_load();
        self.container_per_group.empty();
        var participants = xoctGroup.getSelectedGroupParticipants();
        console.log('participants:');
        console.log(participants);
        participants.forEach(function(participant) {
            self.container_per_group.append('<li class="list-group-item" data-id="'
                + participant.user_id
                + '"><div style="margin-right:30px;">'
                + participant.name
                + '</div>'
                + '<button class="btn btn-default xoct_remove_user pull-right xoct_admin_only"><span class="glyphicon glyphicon-minus"></span></button></li>');
        });
        if (!participants || participants.length == 0) {
            self.container_per_group.html('<li class="list-group-item">' + self.lng['none_available'] + '</li>');
        }
        self.after_load();
        if (!xoctGroup.is_admin) {
            $('.xoct_admin_only').hide();
        }
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
