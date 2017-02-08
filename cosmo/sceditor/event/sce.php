<?php
/**
 *
 * @author Tekin Birdüzen <t.birduezen@web-coding.eu>
 * @since 09.06.15
 * @version 1.7.8
 * @copyright Tekin Birdüzen
 */

namespace cosmo\sceditor\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener
 */
class sce implements EventSubscriberInterface
{
	/** @var \phpbb\template\template */
	private $template;
	/** @var \phpbb\user */
	private $user;
	/** @var \phpbb\config\config */
	private $config;
	/** @var \phpbb\db\driver\driver_interface */
	private $db;

	private $root_path;

	private $css_file = 'editarea.css';

	private $jsDir;

	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\template\template $template, \phpbb\config\config $config, \phpbb\user $user, $root_path)
	{
		$this->template = $template;
		$this->user = $user;
		$this->config = $config;
		$this->db = $db;
		$this->root_path = $root_path;
		$this->jsDir = realpath(__DIR__ . '/../styles/all/template/js/languages') . '/';
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.generate_smilies_after' => 'initialize_sceditor',
			'core.viewtopic_modify_page_title' => 'initialize_sceditor'
		);
	}

	public function initialize_sceditor()
	{
		// Activate the SCEditor
		$this->template->assign_vars(array('S_SCEDITOR' => true,
			'MAX_FONTSIZE' => $this->config['max_post_font_size'],
			'U_EMOTICONS_ROOT' => $this->root_path . $this->config['smilies_path'] . '/',
			'U_CSS' => $this->root_path . 'ext/cosmo/sceditor/styles/' . (is_dir($this->root_path . 'ext/cosmo/sceditor/styles/' . $this->user->style['style_path']) ? $this->user->style['style_path'] : 'all') . '/template/js/themes/' . $this->css_file));

		// Localize it maybe?
		$lang = $this->get_lang();
		if ($lang)
		{
			$this->template->assign_var('L_SCEDITOR_LANG', $lang);
		}
		// We need to get all smilies with url and code
		$sql = 'SELECT smiley_url, code
			FROM ' . SMILIES_TABLE . '
			GROUP BY smiley_url';
		// Caching the smilies for 10 minutes should be okay
		// they don't get changed so often
		$result = $this->db->sql_query($sql, 600);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('emoticons', array('code' => $row['code'], 'url' => $row['smiley_url']));
		}
	}

	private function get_lang()
	{
		$lang = substr($this->user->lang['USER_LANG'], 0, 2);

		// English is default and doesn't have to be loaded
		if ('en' === $lang)
		{
			return false;
		}
		if (is_readable($this->jsDir . "{$lang}.js"))
		{
			return $lang;
		}
		return false;
	}
}
