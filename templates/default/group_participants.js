/**
 * xoctGroupParticipant
 *
 * @type {{init: Function, selected_id: number, data_url: string, load: Function}}
 */
var xoctGroupParticipant = {
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
		$.ajax({url: url, type: "GET", data: {"cmd": "getAvailable"}}).done(function (data) {
			$('#xoct_groups').empty();
			for (var i in data) {
				$('#xoct_groups').append('<a class="list-group-item xoct_group" data-group-id="'
				+ data[i].id
				+ '">' + data[i].title + ''
				+ '<span class="badge xoct_group_delete"><span class="glyphicon glyphicon-remove"></span></span></a>');
			}
			after();
		});
	}
};