$(document).ready(() => {
    $(".datatableui").each(function () {
        const table = this;

        const post_var = $("table input:checkbox", table).prop("name").slice(0, -2); // slice removes [] from the end

        // Select all
        $(".datatableui_multiple_actions input:checkbox", table).change(function () {
            il.Util.setChecked(table.id, post_var, this.checked);
        });

        // Form
        $(".datatableui_multiple_actions a[href]", table).click(function (e) {
            e.preventDefault(); // Prevents to execute the button action as get

            const action = this.href;

            // Instead create a form and send the selected checkboxs in the background as post
            const $form = $(`<form action="${action}" method="post"></form>`);

            $("table input:checkbox:checked", table).each(function () {
                $form.append($(this).clone().prop("type", "hidden"));
            });

            $(table).append($form);

            $form[0].submit();
        });
    });
});
