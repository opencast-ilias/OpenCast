/**
 * xoctUploadProgress JS Lib
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
var xoctUploadProgress = {
  interval: null,
  waiter: null,

  init: function (cmd) {
    xoctWaiter.init('percentage');
    this.interval = setInterval(() => {
      this.getProgress(cmd);
    }, 1000);
  },

  getProgress: function (cmd) {
    $.ajax({
      url: cmd,
      type: "GET",
      dataType: 'json',
    }).success(progress => {
      if (progress > 0 && this.waiter != true) {
        xoctWaiter.show();
        this.waiter = true;
      }
      xoctWaiter.setPercentage(progress);
      if (progress == 100) {
        xoctWaiter.hide();
        this.waiter = false;
        clearInterval(this.interval);
      }
    });
  }
};
