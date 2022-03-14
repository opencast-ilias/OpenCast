var xoctSortable = {

	base_link: '',

	init: function(base_link) {
		xoctSortable.base_link = base_link;
		$("div.ilTableOuter table tbody").sortable({
			helper: xoctSortable.fixHelper,
			items: '.xoctSortable',
			stop: xoctSortable.reSort
		}).disableSelection();
	},

	fixHelper: function (e, ui) {
		ui.children().each(function () {
			$(this).width($(this).width());
		});
		return ui;
	},

	reSort: function (e, ui) {
		xoctWaiter.show();
		var order = [];
		$("div.ilTableOuter table tbody tr.xoctSortable").each(function () {
			order.push($(this).attr('data-id'));
		});

		ajax_url = xoctSortable.base_link + '&cmd=reorder';
		$.ajax({
			url: xoctSortable.base_link,
			type: "POST",
			data: {
				"ids": order
			}
		}).always(function(data, textStatus, jqXHR) {
			xoctWaiter.hide();
		});
	}
}
