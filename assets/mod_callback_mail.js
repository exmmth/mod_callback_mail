/*
 * @package     mod_callback_mail
 * @copyright   Copyright (C) 2019 Aleksey A. Morozov (AlekVolsk). All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */
document.addEventListener('DOMContentLoaded', function () {

	mct_sendform = function (form) {
		
		var formID = form.getAttribute('id');

		document.querySelector('#' + formID + ' [type="submit"]').setAttribute('disabled', 'disabled');

		var
			alrt = document.querySelector('#' + formID + ' > .mod_callback_mail_alert'),
			alertSuccess = formID + '_params_alert_success_class',
			alertError = formID + '_params_alert_error_class',
			moduleHash = formID + '_params_hash',
			formData = new FormData(form),
			request = new XMLHttpRequest(),
			response = false;

		alrt.style.display = 'none';
		alrt.classList.remove(window[alertSuccess]);
		alrt.classList.remove(window[alertError]);

		formData.append('rsl', screen.width + ':' + screen.height);
		formData.append('rst', location.href);
		formData.append('mh', moduleHash.replace(/[^0-9]/gim,'') + ':' + window[moduleHash]);

		request.open('POST', location.protocol + '//' + location.host + '/index.php?option=com_ajax&module=callback_mail&format=raw');
		request.send(formData);

		request.onreadystatechange = function () {
			if (this.readyState === 4 && this.status === 200) {
				try {
					response = JSON.parse(this.response);
					alrt.innerHTML = response.message;
					alrt.classList.add(
						response.result ?
							window[alertSuccess] :
							window[alertError]
					);
					alrt.style.display = 'block';
					if (response.result) {
						document.dispatchEvent(new Event('DOMContentLoaded', { 'bubbles': true }));
						setTimeout(function () {
							document.querySelectorAll('[type="text"], [type="number"], select, textarea').forEach(el => el.value = '');
						}, 100);
					}
				} catch (e) {
					response = false;
					alrt.innerHTML = this.response;
					alrt.classList.add(window[alertError]);
					alrt.style.display = 'block';
				}
			}
			document.querySelector('#' + formID + ' [type="submit"]').removeAttribute('disabled');
		};
	}

	Array.prototype.slice.call(document.querySelectorAll('.mod_callback_mail > form')).forEach(function (e) {
		e.addEventListener('submit', function (el) {
			el.preventDefault();
			mct_sendform(el.target);
		});
	});

});
