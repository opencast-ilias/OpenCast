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
		console.log('xoctWaiter: added xoct_waiter to body');
		$('body').append('<div id="xoct_waiter"></div>')
	},

	show: function () {
		if (this.count == 0) {
			this.timer = setTimeout(function () {
				$('#xoct_waiter').show();
			}, 10);

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
			$(dom_selector_string).on("click", function () {

				self.show();
			});
		});
	},
	addLinkOverlay: function (dom_selector_string) {
		var self = this;
		console.log('xoctWaiter: registred LinkOverlay: ' + dom_selector_string);
		$(document).ready(function () {
			$(dom_selector_string).on("click", function (e) {
				e.preventDefault();
				console.log('xoctWaiter: clicked on registred link');
				self.show();
				var href = $(this).attr('href');
				setTimeout(function () {
					document.location.href = href;
				}, 1000);
			});
		});
	},
};