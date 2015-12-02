/**
 * xoctEvent JS Lib
 *
 * @type {{init: Function, selected_id: number, data_url: string, load: Function, deleteGroup: Function, selectGroup: Function, deselectAll: Function, create: Function}}
 */
var xoctEvent = {

	init: function (data_url, container, before_load, after_load) {

		$(document).on('click', '.xoct_group_delete', function () {
			xoctGroup.deleteGroup($(this).parent().data('group-id'));
		});

		$(document).on('click', '.xoct_group', function () {
			xoctGroup.selectGroup($(this).data('group-id'));
		});

	}
};
