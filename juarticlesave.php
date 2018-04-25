<?php
/**
 * JUArticleSave
 *
 * @package          Joomla.Site
 * @subpackage       plg_content_juarticlesave
 *
 * @author           Denys Nosov, denys@joomla-ua.org
 * @copyright        2018 (C) Joomla! Ukraine, https://joomla-ua.org. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

/**
 * Example Content Plugin
 *
 * @since  1.0
 */
class PlgContentJUarticlesave extends JPlugin
{
	/**
	 * PlgContentJUarticlesave constructor.
	 *
	 * @param $subject
	 * @param $config
	 *
	 * @throws \Exception
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$this->app = JFactory::getApplication();
	}

	/**
	 * @param $context
	 * @param $article
	 * @param $isNew
	 *
	 * @return bool
	 *
	 * @since 1.0
	 */
	public function onContentBeforeSave($context, $article, $isNew)
	{
		if($context != 'com_content.article')
		{
			return true;
		}

		if($this->params->def('fix_date', 0) == 1)
		{
			date_default_timezone_set('UTC');
			$datenow = date('Y-m-d H:i:s');

			if($article->publish_up == '0000-00-00 00:00:00' || $article->publish_up == null || $article->publish_up == '')
			{
				$article->publish_up = $datenow;
				$article->created    = $datenow;
			}
			else
			{
				$article->created = $article->publish_up;
			}

			$msg_publis_up = '<strong>' . JText::_('COM_CONTENT_FIELD_PUBLISH_UP_LABEL') . ':</strong>&nbsp;&nbsp;&nbsp;' . JHtml::date($article->publish_up, 'Y-m-d H:i:s') . '<br>';
			$msg_created   = '<strong>' . JText::_('COM_CONTENT_FIELD_CREATED_LABEL') . '</strong>:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . JHtml::date($article->created, 'Y-m-d H:i:s');

			$this->app->enqueueMessage($msg_publis_up . $msg_created, 'message');
		}

		if($this->params->def('typo', 0) == 1)
		{
			$article->title     = $this->_typo($article->title, 0);
			$article->introtext = $this->_typo($article->introtext);
			$article->fulltext  = $this->_typo($article->fulltext);
			$article->metadesc  = $this->_typo($article->metadesc, 0);
		}

		return true;
	}

	/**
	 * @param $text
	 *
	 * @return mixed|null|string|string[]
	 *
	 * @since 1.0
	 */
	public function _typo($text, $tags = 1)
	{
		require_once __DIR__ . '/lib/emt/EMT.php';

		preg_match_all('!(\[socpost\].*?\[/socpost\])!si', $text, $pre);
		$text = preg_replace('!\[socpost\].*?\[/socpost\]!si', '#pre#', $text);

		$typograf = new EMTypograph();

		$typograf->set_text($text);
		$typograf->setup(array(
			'Text.paragraphs' => 'off',
			'Text.breakline'  => 'off',
			'OptAlign.all'    => 'off',
		));

		if($tags == 0)
		{
			$result = html_entity_decode(strip_tags($typograf->apply()));
		}
		else
		{
			$result = $typograf->apply();
			$result = str_replace('<p></p>', '', $result);

			if(!empty($pre[ 0 ]))
			{
				foreach($pre[ 0 ] as $tag)
				{
					$result = preg_replace('!#pre#!', $tag, $result, 1);
				}
			}
		}

		return $result;
	}
}