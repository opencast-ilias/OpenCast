/**
 * xoctGroup JS Lib
 *
 * @type {{init: Function, selected_id: number, data_url: string, load: Function, deleteGroup: Function, selectGroup: Function, deselectAll: Function, create: Function}}
 */
var xoctGroup = {
    selected_id: 0,
    data_url: '',
    container: null,
    groups: [],
    participants: [],
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
    /**
     *
     * @param data_url
     * @param container
     * @param before_load
     * @param after_load
     */
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
    /**
     *
     * @param fallback
     * @param select_current
     * @param load_group_id
     */
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
        $.ajax({url: url, type: "GET", data: {"cmd": "getParticipants"}}).done(function (data) {
            self.participants = data;
        });

        $.ajax({url: url, type: "GET", data: {"cmd": "getAll"}}).done(function (data) {
            self.clear();
            self.groups = data;
            for (var i in data) {
                self.container.append('<a class="list-group-item xoct_group" data-group-id="' + data[i].id + '">'
                    + data[i].name
                    + '<button class="btn btn-danger xoct_group_delete pull-right"><span class="glyphicon glyphicon-remove"></span></button>'
                    + '<Button class="btn pull-right">' + data[i].user_count + '</button>'
                    + '</li>');
            }
            if (!data || data.length == 0) {
                self.container.html('<li class="list-group-item">' + self.lng['none_available'] + '</li>');
            }
            if (data && data.length == 1) {
                self.selectGroup(data[0].id);
            } else {
                if (select_current) {
                    self.selectGroup(selected_storage);
                }
            }

            xoctGroupParticipant.clear();
            xoctGroupParticipant.load();
            self.after_load();
            fallback();
        });
    },
    /**
     *
     * @param id
     * @param fallback
     */
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
    /**
     *
     * @param id
     * @param force
     */
    selectGroup: function (id, force) {
        force = typeof(force) == 'undefined' ? false : force;
        if (this.selected_id == id && !force) {
            this.deselectAll();
            this.selected_id = 0;
        } else {
            this.selected_id = id;
            this.deselectAll();
            xoctGroupParticipant.load();
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

    getGroup: function (id) {
        var return_group = null;
        this.groups.forEach(function(group) {
            if (parseInt(group.id) === parseInt(id)) {
                return_group = group;
            }
        });
        return return_group;
    },

    getSelectedGroup: function () {
        return this.getGroup(this.selected_id);
    },

    isInAnyGroup: function (user_id) {
        var is_in_group = false;
        this.groups.forEach(function(group) {
            if (group.users.indexOf(user_id) !== -1) {
                is_in_group = true;
            }
        });
        return is_in_group;
    },

    getParticipant: function(user_id) {
        var return_participant = null;
        this.participants.forEach(function(participant) {
            if (parseInt(participant.user_id) === parseInt(user_id)) {
                return_participant = participant;
            }
        });
        return return_participant;
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
                self.selectGroup(data.id, true);
            });
            self.after_load();
            fallback(data);
        });
    }
};
