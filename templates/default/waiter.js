/**
 * xoctWaiter
 *
 * GUI-Overlay
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */

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
	},
	/**
	 * @param dom_selector_string
	 */
	addListener: function (dom_selector_string) {
		var self = this;
		$(document).ready(function () {
			$(dom_selector_string ).on( "click", function() {

				self.show();
			});
		});
	}
};
