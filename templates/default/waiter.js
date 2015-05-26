/**
 *
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 *
 */
//$(document).ready(function () {

var xoctWaiter = {
	count: 0,
	timer: null,
	init: function () {
		$('body').append('<div id="xoct_waiter" style="display: none; z-index: 9999"></div>')
	},

	show: function () {
		if (this.count == 0) {
			this.timer = setTimeout(function () {
				$('#xoct_waiter').fadeIn(200)
			}, 500);

		}
		this.count = this.count + 1;
	},

	hide: function () {
		this.count = this.count - 1;
		if (this.count == 0) {
			window.clearTimeout(this.timer);
			$('#xoct_waiter').fadeOut(200);
		}
	}
};
//});
