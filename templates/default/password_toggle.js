$(document).ready(function() {
	let password_input = document.getElementById('curl_password');
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
	container_div.setAttribute('id','xoct_pw_toggle_container');

	container_div.appendChild(show_div);
	container_div.appendChild(hide_div);

	let current_parent = password_input.parentElement;
	current_parent.classList.add('xoct_pw_main_container');

	let wrapper_div = document.createElement('div');
	wrapper_div.setAttribute('class', 'xoct_pw_wrapper');

	wrapper_div.appendChild(password_input);
	wrapper_div.appendChild(container_div);

	current_parent.prepend(wrapper_div);

	$('.xoct_pw_toggle_item').click( function(e) {
		let element = e.target.nodeName == 'IMG' ? e.target.parentNode : e.element;
		let toggle_element = $(element).siblings('.xoct_pw_toggle_item');
		if ($(element).hasClass('toggle-show')) {
			$('#curl_password').attr('type', 'text');
		} else {
			$('#curl_password').attr('type', 'password');
		}

		$(element).hide();
		$(toggle_element).show();
	});
});
