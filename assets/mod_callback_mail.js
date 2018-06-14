/*
 * @package     mod_callback_mail
 * @copyright   Copyright (C) 2018 Aleksey A. Morozov (AlekVolsk). All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */

jQuery(document).ready(function($)
{
    $('.mod_mail_callback [type=submit]').click(function(ev)
    {
        ev.preventDefault();
        $(this).prop('disabled', true);
        $(this).parent()
            .after('<input type="hidden" name="rsl" value="' + screen.width + ':' + screen.height + '">')
            .closest('form').submit();
    });
});
