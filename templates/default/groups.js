/**
 * xoctGroup JS Lib
 *
 * @type {{init: Function, selected_id: number, data_url: string, load: Function, deleteGroup: Function, selectGroup: Function, deselectAll: Function, create: Function}}
 */
var xoctGroup = {
    is_admin: false,
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
     * @param is_admin
     */
    init: function (data_url, container, is_admin, before_load, after_load) {
        this.is_admin = is_admin;
        if (!this.is_admin) {
            $('.xoct_admin_only').hide();
        }
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

            $.ajax({url: url, type: "GET", data: {"cmd": "getAll"}}).done(function (data) {
                self.groups = data;
                self.loadGroupGUI(select_current, selected_storage);

                self.after_load();
                fallback();
            });
        });
    },

    loadGroupGUI: function (select_current, selected_storage) {
        var self = this;
        self.clear();
        for (let i in self.groups) {
            self.container.append('<a class="list-group-item xoct_group" data-group-id="' + self.groups[i].id + '">'
                + self.groups[i].title
                + '<button class="btn btn-danger xoct_group_delete pull-right xoct_admin_only"><span class="glyphicon glyphicon-remove"></span></button>'
                + '<Button class="btn pull-right" id="xoct_user_counter_' + self.groups[i].id + '">' + self.groups[i].users.length + '</button>'
                + '</li>');
        }
        if (!self.groups || self.groups.length === 0) {
            self.container.html('<li class="list-group-item">' + self.lng['none_available'] + '</li>');
        }
        if (self.groups && self.groups.length === 1) {
            self.selectGroup(self.groups[0].id);
        } else {
            if (select_current) {
                self.selectGroup(selected_storage);
            } else {
                xoctGroupParticipant.clear();
                xoctGroupParticipant.load();
            }
        }

        if (!this.is_admin) {
            $('.xoct_admin_only').hide();
        }
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
                self.groups = self.groups.filter(function(value, index, arr) {
                    return value.id.toString() !== id.toString();
                });
                self.loadGroupGUI();
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

    removeParticipant: function(id) {
        this.getSelectedGroup().users = this.getSelectedGroup().users.filter(function(value, index, arr) {
            return value.toString() !== id.toString();
        });
        $('#xoct_user_counter_' + this.selected_id).html(this.getSelectedGroup().users.length);
    },

    addParticipant: function (id) {
        this.getSelectedGroup().users.push(id.toString());
        $('#xoct_user_counter_' + this.selected_id).html(this.getSelectedGroup().users.length);
    },

    getSelectedGroupParticipants: function () {
        var self = this;
        var group = this.getSelectedGroup();
        var participants = [];
        for (let i in group.users) {
            participants.push(self.getParticipant(group.users[i]));
        }
        return participants;
    },

    getAvailableParticipantsForSelectedGroup: function () {
        var self = this;
        if (self.selected_id === 0) {
            return self.participants;
        }
        var group = this.getSelectedGroup();
        var participants = [];
        for (let i in self.participants) {
            if (!group.users.includes(self.participants[i].user_id)) {
                participants.push(self.participants[i]);
            }
        }
        return participants;
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
        return this.participants.find(function(participant) {
            return parseInt(participant.user_id) === parseInt(user_id);
        });
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
            self.groups.push(data);
            self.loadGroupGUI();
            self.after_load();
            fallback(data);
        });
    }
};
