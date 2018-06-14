<?php defined('_JEXEC') or die;
/*
 * @package     mod_callback_mail
 * @copyright   Copyright (C) 2018 Aleksey A. Morozov (AlekVolsk). All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */

class ModCallbackMailHelper
{
	
	static public function getFields($params)
	{
		$items = (array)$params->get('items');
		foreach ($items as $key => $item)
		{
			$items[$key]->fname = JFilterOutput::stringURLSafe($item->fname);
		}
		return $items;
	}

	static protected function checkForm()
	{
		$rsl = explode(':', filter_input(INPUT_POST, 'rsl', FILTER_SANITIZE_STRING));
		$base = JUri::base();
		$current = JUri::current();
		return 
			!empty($_POST) && 
			( 
				( $_SERVER['HTTP_REFERER'] == $base || $_SERVER['HTTP_REFERER'] == $base . 'index.php' || $_SERVER['HTTP_REFERER'] == $current ) && 
				( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ? $_SERVER['HTTP_X_REQUESTED_WITH'] == XMLHttpRequest : true ) &&
				( count($rsl) === 2 && (int)$rsl[0] > 0 && (int)$rsl[1] > 0 )
			);
	}
	
	static protected function getLayoutPath($extension, $layout = 'default')
	{
		$template = \JFactory::getApplication()->getTemplate();
		$defaultLayout = $layout;

		if (strpos($layout, ':') !== false)
		{
			$temp = explode(':', $layout);
			$template = $temp[0] === '_' ? $template : $temp[0];
			$layout = $temp[1];
			$defaultLayout = $temp[1] ?: 'default';
		}

		$tPath = JPATH_THEMES . '/' . $template . '/html/layouts/' . $extension . '/' . $layout . '.php';
		$bPath = JPATH_BASE . '/modules/' . $extension . '/layouts/' . $defaultLayout . '.php';
		$dPath = JPATH_BASE . '/modules/' . $extension . '/layouts/default.php';

		if (file_exists($tPath))
		{
			return $tPath;
		}

		if (file_exists($bPath))
		{
			return $bPath;
		}

		return $dPath;
	}

	static public function getAjax()
	{
		$uri = JFactory::getURI();
		$extension = 'mod_callback_mail';
		
		JLog::addLogger( ['text_file' => $extension . '.php', 'text_entry_format' => '{DATETIME}	{PRIORITY}	{MESSAGE}'], JLog::ALL );

		if (!JSession::checkToken()) 
		{
			$data = [
				'referer' => $_SERVER['HTTP_REFERER'],
				'result' => 'Invalid token'
			];
			JLog::add(json_encode($data), JLog::ERROR);
			jexit(JText::_('JINVALID_TOKEN'));
		}

		if (!self::checkForm())
		{
			$data = [
				'referer' => $_SERVER['HTTP_REFERER'],
				'result' => 'Invalid checkform'
			];
			JLog::add(json_encode($data), JLog::ERROR);
			jexit(JText::_('JINVALID_TOKEN'));
		}
		
		$app = JFactory::getApplication();
		$language = JFactory::getLanguage();
		$language->load($extension, JPATH_BASE, null, true);
		
		$module = JModuleHelper::getModule($extension);
		$params = new JRegistry();
		$params->loadString($module->params);
		$fields = self::getFields($params);
		$sender_type = $params->get('sender_type', 1);
		
		$data = [
			'title' => trim($params->get('mailtitle', JText::sprintf('MOD_CALLBACK_MAIL_PRM_MAILTITLE_DEFAULT', JUri::base()))),
			'prependText' => explode("\n", trim($params->get('premsg', ''))),
			'appendText' => explode("\n", trim($params->get('postmsg', ''))),
			'items' => [],
		];
		
		$c = count($fields);
		for($i = 0; $i < $c; $i++)
		{
			$val = trim(filter_input(INPUT_POST, $fields['items' . $i]->fname, FILTER_SANITIZE_STRING));
			if ($fields['items' . $i]->ftype == 'checkbox')
			{
				$val = $val ? JText::_('JYES') : JText::_('JNO');
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

		$config = JFactory::getConfig();
		$sender_mail = $sender_type ? $config->get('mailfrom') : $params->get('sender_mail');
		$sender_name = $sender_type ? $config->get('fromname') : $params->get('sender_name');
		$mailer = JFactory::getMailer();
		$mailer->setSender([$mailfrom, $mailfromname]);
		$mailer->addRecipient($params->get('recipient_mail'), $params->get('recipient_name'));
		$mailer->isHtml(true);
		$mailer->encoding = 'base64';
		$mailer->setSubject($data['title']);
		$mailer->setBody($out);
		$send = $mailer->send();
		
		if ($send === false)
		{
			$data['result'] = false;
			$logPriority = JLog::ERROR;
			$app->enqueueMessage(JText::_('MOD_CALLBACK_MAIL_SUBMIT_FAILED_MSG'), 'error');
		}
		elseif ($send !== true)
		{
			$data['result'] = $send->getMessage();
			$logPriority = JLog::ERROR;
			$app->enqueueMessage(JText::_('MOD_CALLBACK_MAIL_SUBMIT_FAILED_MSG'), 'error');
		}
		else
		{
			$data['result'] = true;
			$logPriority = JLog::INFO;
			$app->enqueueMessage(JText::_('MOD_CALLBACK_MAIL_SUBMIT_SUCCESSFULLY_MSG'));
		}
		
		JLog::add(json_encode($data), $logPriority);

		$app->redirect($uri);
	}
}
