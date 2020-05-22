<?php
/**
 * JUArticleSave
 *
 * @package          Joomla.Site
 * @subpackage       plg_content_juarticlesave
 *
 * @author           Denys Nosov, denys@joomla-ua.org
 * @copyright        2018-2020 (C) Joomla! Ukraine, https://joomla-ua.org. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/lib/vendor/autoload.php';

use Emuravjev\Mdash\Typograph;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * JUArticleSave Content Plugin
 *
 * @since  1.0
 */
class PlgContentJUarticlesave extends CMSPlugin
{
	private $app;

	/**
	 * PlgContentJUarticlesave constructor.
	 *
	 * @param $subject
	 * @param $config
	 *
	 * @throws \Exception
	 *
	 * @since  1.0
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$this->app = Factory::getApplication();
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
		if($context !== 'com_content.article')
		{
			return true;
		}

		if($this->params->def('fix_date', 0) == 1)
		{
			date_default_timezone_set('UTC');

			$datenow = date('Y-m-d H:i:s');

			if($article->publish_up === '0000-00-00 00:00:00' || $article->publish_up === null || $article->publish_up == '')
			{
				$article->publish_up = $datenow;
				$article->created    = $datenow;
			}
			else
			{
				$article->created = $article->publish_up;
			}

			$msg_publis_up = '<strong>' . Text::_('COM_CONTENT_FIELD_PUBLISH_UP_LABEL') . ':</strong>&nbsp;&nbsp;&nbsp;' . JHtml::date($article->publish_up, 'Y-m-d H:i:s') . '<br>';
			$msg_created   = '<strong>' . Text::_('COM_CONTENT_FIELD_CREATED_LABEL') . '</strong>:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . JHtml::date($article->created, 'Y-m-d H:i:s');

			$this->app->enqueueMessage($msg_publis_up . $msg_created, 'notice');
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
	 * @param     $text
	 *
	 * @param int $tags
	 *
	 * @return mixed|null|string|string[]
	 *
	 * @since 1.0
	 */
	public function _typo($text, $tags = 1)
	{
		$typograf = new Typograph();

		preg_match_all('!(\[socpost\].*?\[/socpost\])!si', $text, $pre);
		$text = preg_replace('!\[socpost\].*?\[/socpost\]!si', '#pre#', $text);

		$typograf->set_text($text);
		$typograf->setup([
			'Text.paragraphs'                   => 'off',
			'Text.breakline'                    => 'off',
			'OptAlign.all'                      => 'off',
			'Nobr.spaces_nobr_in_surname_abbr'  => 'off',
			'Nobr.nbsp_org_abbr'                => 'off',
			'Nobr.nbsp_in_the_end'              => 'off',
			'Nobr.phone_builder'                => 'off',
			'Nobr.phone_builder_v2'             => 'off',
			'Nobr.ip_address'                   => 'off',
			'Nobr.dots_for_surname_abbr'        => 'off',
			'Nobr.hyphen_nowrap_in_small_words' => 'off',
			'Abbr.nobr_abbreviation'            => 'off',
			'Abbr.nobr_acronym'                 => 'off',
			'Etc.unicode_convert'               => 'off',
			'Etc.split_number_to_triads'        => 'off'
		]);

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