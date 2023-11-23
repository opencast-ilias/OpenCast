/**
 * PasswordToggle
 *
 * PasswordToggle class
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
export default class WaitOverlay {
    count;
    timer;


    constructor() {
        this.count = 0;
        this.timer = null;
    }

    init(type) {
        $('body').append('<div id="xoct_waiter" class="xoct_waiter"></div>')
    }


    show() {
        if (this.count === 0) {
            this.timer = setTimeout(function () {
                $('#xoct_waiter').show();
            }, 10);

        }
        this.count = this.count + 1;
    }


    hide() {
        this.count = this.count - 1;
        if (this.count === 0) {
            window.clearTimeout(this.timer);
            $('#xoct_waiter').fadeOut(200);
        }
    }

    addListener(dom_selector_string) {
        const self = this;
        $(document).ready(function () {
            $(dom_selector_string).on("click", function () {
                self.show();
            });
        });
    }


    addLinkOverlay(dom_selector_string) {
        const self = this;
        $(document).ready(function () {
            $(dom_selector_string).on("click", function (e) {
                e.preventDefault();
                self.show();
                let href = $(this).attr('href');
                setTimeout(function () {
                    document.location.href = href;
                }, 1000);
            });
        });
    }

    onFormSubmit(dom_selector_string) {
        const self = this;
        $(document).ready(function () {
            // check if element is form, otherwise search for parent form
            let form = $(dom_selector_string).is('form') ? $(dom_selector_string) : $(dom_selector_string).closest('form');
            if (form.length === 0) {
                console.warn('Unable to find form for selector: ' + dom_selector_string);
                return;
            }
            form.on("submit", function () {
                self.show();
            });
        });
    }
}
