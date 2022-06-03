/**
 * xoctEvent JS Lib
 *
 * @type {{init: Function, selected_id: number, data_url: string, load: Function, deleteGroup: Function, selectGroup: Function, deselectAll: Function, create: Function}}
 */
var xoctEvent = {

  lang: [
    msg_link_copied = "Link copied",
    tooltip_copy_link = "Copy link to clipboard"
  ],

  init: function (lang) {
    this.lang = JSON.parse(lang);
  },

  copyLink: function(element, event) {
    var copyText = $(element).attr("data-url");

    var $temp = $("<input type='text'>");
    $("body").append($temp);
    $temp.val(copyText).select();
    document.execCommand("copy");
    $temp.remove();
    
    var tooltip = $(element).find('span.xoct_tooltiptext')[0];
    tooltip.innerHTML = this.lang['msg_link_copied'];

    event.preventDefault();
  },

  outFunc: function(element) {
    var tooltip = $(element).find('span.xoct_tooltiptext')[0];
    tooltip.innerHTML = this.lang['tooltip_copy_link'];
  },

  previewPlay: function(element, event) {
    event.preventDefault();
    var play_link_obj = $('a[data-preview_link="' + $(element).data('id') + '"]');
    if (play_link_obj.length) {
      var play_link = play_link_obj[0];
      var href = $(play_link).attr('href');
      if (href && href != '#') {
        var target = $(play_link).attr('target') ? $(play_link).attr('target') : '_blank';
        window.open(href, target);
      } else {
        $(play_link).click();
      }
    }
  }
};
