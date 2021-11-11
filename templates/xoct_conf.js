var XoctConf = {
  reset_ToU: function () {
    event.preventDefault();
    display_result = $('span#reset_ToU_status');
    display_result.text('Sending Request...');
    ajax_url = $('button#xoct_reset_ToU').attr('href');

    $.ajax({
      url: ajax_url,
      type: "GET",
      data: ""
      // timeout: 5000
    }).always(function(data, textStatus, jqXHR) {
      display_result.text(data);
    });
  }
}