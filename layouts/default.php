<?php defined('_JEXEC') or die;
/*
 * @package     mod_callback_mail
 * @copyright   Copyright (C) 2019 Aleksey A. Morozov (AlekVolsk). All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */

// preppend text
if ($data['prependText']) {
    echo '<div style="margin-bottom:30px;">';
    foreach ($data['prependText'] as $str) {
        echo '<p>' . $str . '</p>';
    }
    echo '</div>';
}

// fileds
foreach ($data['items'] as $item) {
    // hidden empty values
    if (trim($item->value)) {
        echo '<p><strong>' . $item->name . '</strong>: ' . $item->value . '</p>';
    }
}

// append text
if ($data['appendText']) {
    echo '<div style="margin-top:30px;">';
    foreach ($data['appendText'] as $str) {
        echo '<p>' . $str . '</p>';
    }
    echo '</div>';
}
