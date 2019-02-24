<?php defined('_JEXEC') or die;
/*
 * @package     mod_callback_mail
 * @copyright   Copyright (C) 2019 Aleksey A. Morozov (AlekVolsk). All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Log\Log;

class ModCallbackMailHelper
{

    static public function getFields($params)
    {
        $items = (array)$params->get('items');
        foreach ($items as $key => $item) {
            $items[$key]->fname = OutputFilter::stringURLSafe($item->fname);
        }
        return $items;
    }

    static protected function checkForm()
    {
        $rsl = explode(':', filter_input(INPUT_POST, 'rsl', FILTER_SANITIZE_STRING));
        $base = Uri::base();
        $current = Uri::current();
        return
            !empty($_POST) && (
				($_SERVER['HTTP_REFERER'] == $base || $_SERVER['HTTP_REFERER'] == $base . 'index.php' || $_SERVER['HTTP_REFERER'] == $current) && 
				(isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] == XMLHttpRequest : true) && 
				(count($rsl) === 2 && (int)$rsl[0] > 0 && (int)$rsl[1] > 0)
			);
    }

    static protected function getLayoutPath($extension, $layout = 'default')
    {
        $template = Factory::getApplication()->getTemplate();
        $defaultLayout = $layout;

        if (strpos($layout, ':') !== false) {
            $temp = explode(':', $layout);
            $template = $temp[0] === '_' ? $template : $temp[0];
            $layout = $temp[1];
            $defaultLayout = $temp[1] ?: 'default';
        }

        $tPath = JPATH_THEMES . '/' . $template . '/html/layouts/' . $extension . '/' . $layout . '.php';
        $bPath = JPATH_BASE . '/modules/' . $extension . '/layouts/' . $defaultLayout . '.php';
        $dPath = JPATH_BASE . '/modules/' . $extension . '/layouts/default.php';

        if (file_exists($tPath)) {
            return $tPath;
        }

        if (file_exists($bPath)) {
            return $bPath;
        }

        return $dPath;
    }

    static private function printJson($message, $result = false, $custom = [])
    {
        if (empty($message)) {
            $message = '< empty message >';
        }

        $jsonData = ['result' => $result, 'message' => $message];

        foreach ($custom as $key => $value) {
            $jsonData[$key] = $value;
        }

        echo json_encode($jsonData);

        exit;
    }

    static public function getAjax()
    {
        $extension = 'mod_callback_mail';

        Log::addLogger(['text_file' => $extension . '.php', 'text_entry_format' => '{DATETIME}	{PRIORITY}	{MESSAGE}'], Log::ALL);

        if (!Session::checkToken()) {
            $data = [
                'referer' => $_SERVER['HTTP_REFERER'],
                'result' => 'Invalid token'
            ];
            Log::add(json_encode($data), Log::ERROR);
            self::printJson(Text::_('JINVALID_TOKEN'));
        }

        if (!self::checkForm()) {
            $data = [
                'referer' => $_SERVER['HTTP_REFERER'],
                'result' => 'Invalid checkform'
            ];
            Log::add(json_encode($data), Log::ERROR);
            self::printJson(Text::_('JINVALID_TOKEN'));
        }

        $language = Factory::getLanguage();
        $language->load($extension, JPATH_BASE, null, true);

        $module = ModuleHelper::getModule($extension);
        $params = new Registry();
        $params->loadString($module->params);
        $fields = self::getFields($params);
        $sender_type = $params->get('sender_type', 1);

        $data = [
            'title' => trim($params->get('mailtitle', Text::sprintf('MOD_CALLBACK_MAIL_PRM_MAILTITLE_DEFAULT', Uri::base()))),
            'prependText' => explode("\n", trim($params->get('premsg', ''))),
            'appendText' => explode("\n", trim($params->get('postmsg', ''))),
            'items' => [],
        ];

        $c = count($fields);
        for ($i = 0; $i < $c; $i++) {
            $val = trim(filter_input(INPUT_POST, $fields['items' . $i]->fname, FILTER_SANITIZE_STRING));
            if ($fields['items' . $i]->ftype == 'checkbox') {
                $val = $val ? Text::_('JYES') : Text::_('JNO');
            }
            $item = new StdClass();
            $item->name = $fields['items' . $i]->ftitle;
            $item->value = $val;
            $data['items'][] = $item;
        }
        unset($item);

        $layout = str_replace('\\', '/', self::getLayoutPath($extension, $params->get('msgtemplate')));
        ob_start();
        include $layout;
        $out = ob_get_clean();

        $data['referer'] = $_SERVER['HTTP_REFERER'];
        $data['out'] = $out;
        unset($data['prependText'], $data['appendText'], $data['items']);

        $config = Factory::getConfig();
        $sender_mail = $sender_type ? $config->get('mailfrom') : $params->get('sender_mail');
        $sender_name = $sender_type ? $config->get('fromname') : $params->get('sender_name');
        $mailer = Factory::getMailer();
        $mailer->setSender([$sender_mail, $sender_name]);
        $mailer->addRecipient($params->get('recipient_mail'), $params->get('recipient_name'));
        $mailer->isHtml(true);
        $mailer->encoding = 'base64';
        $mailer->setSubject($data['title']);
        $mailer->setBody($out);
        $send = $mailer->send();

        if ($send === false) {
            $data['result'] = false;
            Log::add(json_encode($data), Log::ERROR);
            self::printJson(Text::_('MOD_CALLBACK_MAIL_SUBMIT_FAILED_MSG'));
        } elseif ($send !== true) {
            $data['result'] = $send->getMessage();
            Log::add(json_encode($data), Log::ERROR);
            self::printJson(Text::_('MOD_CALLBACK_MAIL_SUBMIT_FAILED_MSG'));
        } else {
            $data['result'] = true;
            Log::add(json_encode($data), Log::INFO);
            self::printJson(Text::_('MOD_CALLBACK_MAIL_SUBMIT_SUCCESSFULLY_MSG'), true);
        }
    }
}
