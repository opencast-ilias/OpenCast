/**
 *
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 *
 */
//$(document).ready(function () {

var xoctWaiter = {
	count: 0,
	init: function () {
		$('body').append('<div id="xoct_waiter" style="display: none; z-index: 9999"></div>')
	},

	show: function () {
		if (this.count == 0) {
			$('#xoct_waiter').show();
		}
		this.count = this.count + 1;
	},

	hide: function () {
		this.count = this.count - 1;
		if (this.count == 0) {
			$('#xoct_waiter').hide();
		}
	}
};
//});
