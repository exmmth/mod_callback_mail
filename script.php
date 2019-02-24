<?php defined('_JEXEC') or die;
/*
 * @package     mod_callback_mail
 * @copyright   Copyright (C) 2019 Aleksey A. Morozov (AlekVolsk). All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Version;

class mod_callback_mailInstallerScript
{

	public function preflight($type, $parent)
	{
		if ($type != 'uninstall') {
			$app = Factory::getApplication();
			
			$jversion = new Version();
			if (!$jversion->isCompatible('3.7')) {
				$app->enqueueMessage('Please upgrade to at least Joomla! 3.6 before continuing!', 'error');
				return false;
			}
		}
		
		return true;
	}

}