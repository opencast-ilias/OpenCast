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
    var file_id = this.getFileId();
    if (file_id) {
      $.ajax({
        url: cmd,
        type: "GET",
        data: {
          uid: file_id
        },
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
  },

  getFileId: function () {
    var file_input = $('input[type="hidden"][name="form_input_2[]"]');
    if (file_input.length) {
      return $(file_input).data('file-id');
    }
    return false;
  }
};
