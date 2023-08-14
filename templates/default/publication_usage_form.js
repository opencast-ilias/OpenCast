$(document).ready(function() {
    $('select#md_type').on('change', function() {
        if (this.value === "0") {
            $('#il_prop_cont_search_key').hide();
            $('#il_prop_cont_allow_multiple').hide();
            $('#il_prop_cont_mediatype').hide();
        } else {
            $('#il_prop_cont_search_key').show();
            $('#il_prop_cont_allow_multiple').show();
            $('#il_prop_cont_mediatype').show();
        }
    });

    $('select#md_type').change();
});
