/**
 * xoctInvitation JS Lib
 *
 * @type {{init: Function, selected_id: number, data_url: string, load: Function, deleteGroup: Function, selectGroup: Function, deselectAll: Function, create: Function}}
 */
const xoctInvitation = {
  selected_id: 0,
  data_url: '',
  container: null,
  filter_container: null,
  filtering: false,
  lng: {
    delete_group: 'Delete Group?',
    no_title: 'Please insert title',
    none_available: 'None available',
    invite_all: 'Invite All',
    remove_all: 'Remove All',
  },
  before_load() {
  },
  after_load() {
  },
  lngFromJson(lng_json) {
    this.lng = JSON.parse(lng_json);
  },
  init(data_url, container_invited, container_available, before_load, after_load) {
    if (typeof before_load !== 'undefined') {
      this.before_load = before_load;
    }
    if (typeof after_load !== 'undefined') {
      this.after_load = after_load;
    }

    this.data_url = data_url;
    $(container_invited).html(
      `<button id="xoct_remove_all" class="btn btn-primary" style="display: none">${
        this.lng.remove_all
      }</button>`
            + '<ul id="xoct_invitations" class="list-group"></ul>',
    );

    $(container_available).html(
      `<button id="xoct_invite_all" class="btn btn-primary">${
        this.lng.invite_all
      }</button><ul id="xoct_available" class="list-group"></ul>`,
    );
    this.container_invited = $('#xoct_invitations');
    this.container_available = $('#xoct_available');
    this.filter_container = $('#xoct_participant_filter');
    this.load();

    const self = this;
    $(document).on('click', '.xoct_remove', function () {
      self.removeInvitation($(this).parent().data('invitation-id'));
    });

    $(document).on('click', '.xoct_invite', function () {
      self.addInvitation($(this).parent().data('invitation-id'));
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

    $(document).on('click', '#xoct_invite_all', () => {
      self.inviteAll();
    });

    $(document).on('click', '#xoct_remove_all', () => {
      self.removeAll();
    });
  },
  clear() {
    this.container_invited.empty();
    this.container_available.empty();
  },

  load() {
    const self = this;
    this.before_load();
    const url = this.data_url;
    $.ajax({ url, type: 'GET', data: { cmd: 'getAll' } }).done((data) => {
      self.clear();
      for (var i in data.available) {
        self.container_available.append(`<li class="list-group-item xoct_participant_available" data-invitation-id="${data.available[i].id}">`
                    + `<div style="margin-right:30px;">${
                      data.available[i].name
                    }</div>`
                    + '<button class="btn btn-primary xoct_invite pull-right"><span class="glyphicon glyphicon-plus"></span></button>'
                    + '</li>');
      }

      for (var i in data.invited) {
        self.container_invited.append(`<li class="list-group-item" data-invitation-id="${data.invited[i].id}">`
                    + `<div style="margin-right:30px;">${
                      data.invited[i].name
                    }</div>`
                    + '<button class="btn btn-default xoct_remove pull-right"><span class="glyphicon glyphicon-minus"></span></button>'
                    + '</li>');
      }

      if (!data.available || data.available.length == 0) {
        self.container_available.html(`<li class="list-group-item">${self.lng.none_available}</li>`);
      }

      if (!data.invited || data.invited.length == 0) {
        self.container_invited.html(`<li class="list-group-item">${self.lng.none_available}</li>`);
      }

      self.after_load();
    });
  },

  addInvitation(id) {
    const url = this.data_url;
    const self = this;
    this.before_load();

    $.ajax({ url: `${url}&cmd=create`, type: 'POST', data: { id } }).done((data) => {
      self.load();
      self.after_load();
    });
  },

  inviteAll() {
    const ids = [];
    $('#xoct_available li:visible').each(function (i) {
      ids.push($(this).attr('data-invitation-id'));
    });

    const url = this.data_url;
    const self = this;
    this.before_load();

    $.ajax({ url: `${url}&cmd=createMultiple`, type: 'POST', data: { ids } }).done((data) => {
      self.load();
      self.after_load();
    });
  },

  removeInvitation(id) {
    const url = this.data_url;
    const self = this;
    this.before_load();
    $.ajax({ url: `${url}&cmd=delete`, type: 'POST', data: { id } }).done((data) => {
      $(`[data-invitation-id="${id}"]`).remove();
      self.load();
      self.after_load();
    });
  },

  removeAll() {
    const ids = [];
    $('#xoct_invitations li:visible').each(function (i) {
      ids.push($(this).attr('data-invitation-id'));
    });

    const url = this.data_url;
    const self = this;
    this.before_load();

    $.ajax({ url: `${url}&cmd=deleteMultiple`, type: 'POST', data: { ids } }).done((data) => {
      self.load();
      self.after_load();
    });
  },

  /**
     *
     * @param string
     */
  filter(string) {
    this.filtering = (string != '');
    $(`.xoct_participant_available:not(:contains("${string}"))`).hide();
    $(`.xoct_participant_available:contains("${string}")`).show();
  },
};
