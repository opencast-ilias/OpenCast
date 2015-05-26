/**
 *
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 *
 */
//$(document).ready(function () {

var xoctWaiter = {
	init: function () {
		$('body').append('<div id="xoct_waiter" style="display: none; z-index: 9999"></div>')
	},

	show: function () {
		$('#xoct_waiter').show();
	},

	hide: function () {
		$('#xoct_waiter').hide();
	}
};
//});
