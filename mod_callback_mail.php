<?php defined('_JEXEC') or die;
/*
 * @package     mod_callback_mail
 * @copyright   Copyright (C) 2019 Aleksey A. Morozov (AlekVolsk). All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;

require_once __DIR__ . '/helper.php';

$fields = ModCallbackMailHelper::getFields($params);

if ($fields) {

    $moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8');
    $moduleclass_sfx = $moduleclass_sfx ? ' ' . $moduleclass_sfx : '';
    
    $showlabels = $params->get('showlabels', true);

    $moduleHash = md5('mod_callback_mail_' . $module->id . '_' . date('c'));
    $session = Factory::getSession();
    $session->set('mod_callback_mail_' . $module->id, $moduleHash);
    Factory::getDocument()->addScriptDeclaration("var
        mod_callback_mail_" . $module->id . "_params_hash = '" . $moduleHash . "',
        mod_callback_mail_" . $module->id . "_params_alert_success_class = '" . $params->get('alert-success', 'alert-success') . "',
        mod_callback_mail_" . $module->id . "_params_alert_error_class = '" . $params->get('alert-error', 'alert-error') . "';");

    HTMLHelper::script('modules/mod_callback_mail/assets/mod_callback_mail.js');

    require ModuleHelper::getLayoutPath('mod_callback_mail', $params->get('layout', 'default'));
}
