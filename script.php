<?php defined('_JEXEC') or die;

class mod_callback_mailInstallerScript
{

	public function preflight($type, $parent)
	{
		if ($type != 'uninstall') {
			$app = JFactory::getApplication();
			
			$jversion = new JVersion();
			if (!$jversion->isCompatible('3.6')) {
				$app->enqueueMessage('Please upgrade to at least Joomla! 3.6 before continuing!', 'error');
				return false;
			}
		}
		
		return true;
	}

}