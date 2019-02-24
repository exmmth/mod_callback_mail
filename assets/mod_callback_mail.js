/*
 * @package     mod_callback_mail
 * @copyright   Copyright (C) 2019 Aleksey A. Morozov (AlekVolsk). All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */
document.addEventListener('DOMContentLoaded', function () {
    
    mcm_sendform = function(form)
	{
		var alrt = document.querySelector('#' + form.getAttribute('id') + ' > .mod_callback_mail_alert');
		alrt.style.display = 'none';
		
		var formData = new FormData(form);
        formData.append('rsl', screen.width + ':' + screen.height);

        var request = new XMLHttpRequest();
        request.open('POST', location.protocol + '//' + location.host + '/index.php?option=com_ajax&module=callback_mail&format=raw');
		request.send(formData);

        var response = false;
        request.onreadystatechange = function () {
			if (this.readyState === 4 && this.status === 200) {
				try {
					response = JSON.parse(this.response);
					alrt.innerHTML = response.message;
					alrt.style.display = 'block';
				} catch (e) {
					response = false;
					alrt.innerHTML = this.response;
					alrt.style.display = 'block';
				}
			}
		};
	}

    document.querySelector('.mod_callback_mail [type=submit]').addEventListener('click', function (ev) {
        ev.preventDefault();
        mcm_sendform(ev.target.closest('form'));
    });

});
