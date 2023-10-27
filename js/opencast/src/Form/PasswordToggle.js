/**
 * PasswordToggle
 *
 * PasswordToggle class
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
export default class PasswordToggle {
    /**
     * @type {jQuery}
     */
    jquery;
    /**
     * @type {Array<string>}
     */
    password_input_ids;

    constructor(
        jquery,
    ){
        this.jquery = jquery;
        this.password_input_ids = [];
    }


    /**
     * Init
     * This function is designed to handle multiple password inputs, masking all at the same time.
     * @param {string} password_input_ids_json_string possible values for this are:
     * 		string: json encoded array of inputs like '["curl_password"]'
     *		string: single input id like: 'curl_password'
     */
    init(password_input_ids_json_string) {
        try {
            let password_input_ids = JSON.parse(password_input_ids_json_string);
            if (Array.isArray(password_input_ids)) {
                this.password_input_ids = password_input_ids;
            }
        } catch (e) {
            if (password_input_ids_json_string !== '') {
                this.password_input_ids = [password_input_ids_json_string];
            }
        }

        if (!Array.isArray(this.password_input_ids) || this.password_input_ids.length === 0) {
            console.warn('Unable to find any input to mask!');
            return;
        }

        var self = this;

        $(document).ready(function () {
            $('.xoct_pw_toggle_item').click( function(e) {
                let element = e.target.nodeName == 'IMG' ? e.target.parentNode : e.element;
                let toggle_element = $(element).siblings('.xoct_pw_toggle_item');
                let input_siblings = $(element.parentNode).siblings('input');
                if (input_siblings && input_siblings.length > 0) {
                    let password_input = input_siblings[0];
                    if ($(element).hasClass('toggle-show')) {
                        $(password_input).attr('type', 'text');
                    } else {
                        $(password_input).attr('type', 'password');
                    }
                }

                $(element).hide();
                $(toggle_element).show();
            });
        });

        this.password_input_ids.forEach(function (password_input_id, index) {
            self.wrapper(password_input_id);
        });
    }


    /**
     * Wrapper function
     * This function prepares the masking elements and wrap them around the password input element.
     * @param {string} password_input_id The id of the password input element
     */
    wrapper (password_input_id) {
        let password_input = document.getElementById(password_input_id);
        if (!password_input) {
            return;
        }
        password_input.setAttribute('type', 'password');

        let show_icon = document.createElement("img");
        show_icon.setAttribute('alt', 'show password');
        show_icon.setAttribute('class', 'xoct_pw_icon xoct_pw_eye');
        show_icon.setAttribute('src', './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/images/eye.svg');
        let show_div = document.createElement('div');
        show_div.setAttribute('class', 'xoct_pw_toggle_item toggle-show');
        show_div.appendChild(show_icon);
        let hide_icon = document.createElement("img");
        hide_icon.setAttribute('alt', 'hide password');
        hide_icon.setAttribute('class', 'xoct_pw_icon xoct_pw_eye-slash');
        hide_icon.setAttribute('src', './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/images/eye-slash.svg');
        let hide_div = document.createElement('div');
        hide_div.setAttribute('class', 'xoct_pw_toggle_item toggle-hide');
        hide_div.appendChild(hide_icon);

        let container_div = document.createElement('div');
        container_div.setAttribute('class','xoct_pw_toggle_container');

        container_div.appendChild(show_div);
        container_div.appendChild(hide_div);

        let current_parent = password_input.parentElement;
        current_parent.classList.add('xoct_pw_main_container');

        let wrapper_div = document.createElement('div');
        wrapper_div.setAttribute('class', 'xoct_pw_wrapper');

        wrapper_div.appendChild(password_input);
        wrapper_div.appendChild(container_div);

        current_parent.prepend(wrapper_div);
    }

}
