<?php
/**
 * @package     Joomla.Legacy
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.filesystem.folder');

class JFormFieldLayoutsModule extends JFormField
{
	protected $type = 'LayoutsModule';

	protected function getInput()
	{
		$clientId = $this->element['client_id'];

		if ($clientId === null && $this->form instanceof JForm)
		{
			$clientId = $this->form->getValue('client_id');
		}

		$clientId = (int) $clientId;

		$client = JApplicationHelper::getClientInfo($clientId);

		$module = (string) $this->element['module'];

		if (empty($module) && ($this->form instanceof JForm))
		{
			$module = $this->form->getValue('module');
		}
		
		$module = preg_replace('#\W#', '', $module);
		
		$template = (string) $this->element['template'];
		$template = preg_replace('#\W#', '', $template);

		$template_style_id = '';
		if ($this->form instanceof JForm)
		{
			$template_style_id = $this->form->getValue('template_style_id');
			$template_style_id = preg_replace('#\W#', '', $template_style_id);
		}

		if ($module && $client)
		{
			$lang = JFactory::getLanguage();
			$lang->load($module . '.sys', $client->path, null, false, true) || $lang->load($module . '.sys', $client->path . '/modules/' . $module, null, false, true);

			$db = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query
				->select('element, name')
				->from('#__extensions as e')
				->where('e.client_id = ' . (int) $clientId)
				->where('e.type = ' . $db->quote('template'))
				->where('e.enabled = 1');

			if ($template)
			{
				$query->where('e.element = ' . $db->quote($template));
			}

			if ($template_style_id)
			{
				$query
					->join('LEFT', '#__template_styles as s on s.template=e.element')
					->where('s.id=' . (int) $template_style_id);
			}

			$db->setQuery($query);
			$templates = $db->loadObjectList('element');

			$module_path = JPath::clean($client->path . '/modules/' . $module . '/layouts');

			$module_layouts = [];

			$groups = [];

			if (is_dir($module_path) && ($module_layouts = JFolder::files($module_path, '^[^_]*\.php$')))
			{
				$groups['_'] = [];
				$groups['_']['id'] = $this->id . '__';
				$groups['_']['text'] = JText::sprintf('JOPTION_FROM_MODULE');
				$groups['_']['items'] = [];

				foreach ($module_layouts as $file)
				{
					$value = basename($file, '.php');
					$text = $lang->hasKey($key = strtoupper($module . '_LAYOUTS_LAYOUT_' . $value)) ? JText::_($key) : $value;
					$groups['_']['items'][] = JHtml::_('select.option', '_:' . $value, $text);
				}
			}

			if ($templates)
			{
				foreach ($templates as $template)
				{
					$lang->load('tpl_' . $template->element . '.sys', $client->path, null, false, true) || $lang->load('tpl_' . $template->element . '.sys', $client->path . '/templates/' . $template->element, null, false, true);

					$template_path = JPath::clean($client->path . '/templates/' . $template->element . '/html/layouts/' . $module);

					if (is_dir($template_path) && ($files = JFolder::files($template_path, '^[^_]*\.php$')))
					{
						foreach ($files as $i => $file)
						{
							if (in_array($file, $module_layouts))
							{
								unset($files[$i]);
							}
						}

						if (count($files))
						{
							$groups[$template->element] = [];
							$groups[$template->element]['id'] = $this->id . '_' . $template->element;
							$groups[$template->element]['text'] = JText::sprintf('JOPTION_FROM_TEMPLATE', $template->name);
							$groups[$template->element]['items'] = [];

							foreach ($files as $file)
							{
								$value = basename($file, '.php');
								$text = $lang->hasKey($key = strtoupper('TPL_' . $template->element . '_' . $module . '_LAYOUTS_LAYOUT_' . $value)) ? JText::_($key) : $value;
								$groups[$template->element]['items'][] = JHtml::_('select.option', $template->element . ':' . $value, $text);
							}
						}
					}
				}
			}
			$attr = $this->element['size'] ? ' size="' . (int) $this->element['size'] . '"' : '';
			$attr .= $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';

			$html = [];

			$selected = [$this->value];

			$html[] = JHtml::_(
				'select.groupedlist', $groups, $this->name,
				['id' => $this->id, 'group.id' => 'id', 'list.attr' => $attr, 'list.select' => $selected]
			);

			return implode($html);
		}
		else
		{
			return '';
		}
	}
}
