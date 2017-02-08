<?php
/**
 *
 * @author    Tekin Birdüzen <t.birduezen@web-coding.eu>
 * @since     09.06.15
 * @version   1.7.8
 * @copyright Tekin Birdüzen
 */


namespace cosmo\sceditor\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener
 */
class parser implements EventSubscriberInterface
{
	private $color_open;
	private $color_close;
	private $size_open;
	private $size_close;
	private $bbcode;
	private $bbcode_id;

	public static function getSubscribedEvents()
	{
		return array(
			'core.modify_bbcode_init' => 'initialize_fp',
			'core.bbcode_cache_init_end' => 'initialize_sp',
			'core.validate_bbcode_by_extension' => 'bbcode_first_pass',
			'core.bbcode_second_pass_by_extension' => 'bbcode_second_pass'
		);
	}

	public function initialize_fp($event)
	{
		$new_color = $event['bbcodes'];
		$new_color['size'] = array('bbcode_id' => 5, 'regexp' => array('!\[size=([0-9]+)\](.+)\[/size\]!uise' => "\$this->validate_bbcode_by_extension('\$0', \$this)"));
		$new_color['color'] = array('bbcode_id' => 6, 'regexp' => array('!\[color=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)\](.+)\[/color\]!uise' => "\$this->validate_bbcode_by_extension('\$0', \$this)"));

		$event['bbcodes'] = $new_color;
	}

	public function initialize_sp($event)
	{
		$tmp = $event['bbcode_cache'];
		$tmp[5] = array(
			'preg' => array(
				'/\[size=([0-9]+):$uid\]((?!\[size=([0-9]+):$uid\]).)?/ise' => "\$this->bbcode_second_pass_by_extension('size_open', \$this,  \$bbcode_id, '\$1', '\$2')",
				'/\[\/size:$uid\]/ie' => "\$this->bbcode_second_pass_by_extension('size_close', \$this, \$bbcode_id)"
			)
		);
		$tmp[6] = array(
			'preg' => array(
				'/\[color=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+):$uid\]((?!\[color=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+):$uid\]).)?/ise' => "\$this->bbcode_second_pass_by_extension('color_open', \$this,  \$bbcode_id, '\$1', '\$2')",
				'/\[\/color:$uid\]/ie' => "\$this->bbcode_second_pass_by_extension('color_close', \$this, \$bbcode_id)"
			)
		);
		$event['bbcode_cache'] = $tmp;
	}

	/**
	 * Firstpass color bbcode
	 */
	public function bbcode_first_pass($event)
	{
		$in = $event['params_array'][0];
		$this->bbcode = $event['params_array'][1];
		$in = str_replace("\r\n", "\n", str_replace('\"', '"', trim($in)));

		if (!$in) {
			$event['return'] = '';
			return;
		}

		$out = $in;
		do {
			$in = $out;
			$out = preg_replace('/\[color=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)\]((?:.(?!\[color=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)\]))*?)\[\/color\]/is', '[color=$1:' . $this->bbcode->bbcode_uid . ']$2[/color:' . $this->bbcode->bbcode_uid . ']', $in);
			$out = preg_replace('/\[size=([0-9]+)\]((?:.(?!\[size=([0-9]+)\]))*?)\[\/size\]/is', '[size=$1:' . $this->bbcode->bbcode_uid . ']$2[/size:' . $this->bbcode->bbcode_uid . ']', $out);
		} while ($out !== $in);

		$event['return'] = $out;
	}

	/**
	 * Secondpass color bbcode
	 */

	public function bbcode_second_pass($event)
	{
		$mode = $event['params_array'][0];
		$this->bbcode = $event['params_array'][1];
		$this->bbcode_id = $event['params_array'][2];

		// open or close?
		switch ($mode) {
			case 'color_open':
				// These two variables are not really needed
				// It's just to make clear what they are for
				$color = $event['params_array'][3];
				$text = $event['params_array'][4];
				$event['return'] = $this->bbcode_second_pass_open('color', $color, $text);
				break;
			case 'color_close':
				$event['return'] = $this->bbcode_second_pass_close('color');
				break;
			case 'size_open':
				$size = $event['params_array'][3];
				$text = $event['params_array'][4];
				$event['return'] = $this->bbcode_second_pass_open('size', $size, $text);
				break;
			case 'size_close':
				$event['return'] = $this->bbcode_second_pass_close('size');
				break;
		}
	}

	private function bbcode_second_pass_open($bbcode, $arg_val, $text)
	{
		// Already got the part?
		if (!is_string($this->color_open) || !is_string($this->size_open)) {
			$this->get_tpl_parts();
		}

		// when using the /e modifier, preg_replace slashes double-quotes but does not
		// seem to slash anything else
		$text = str_replace('\"', '"', $text);

		// remove newline at the beginning
		if ("\n" === $text) {
			$text = '';
		}
		$text = str_replace('$1', $arg_val, ($bbcode == 'color' ? $this->color_open : $this->size_open)) . $text;

		return $text;
	}

	private function bbcode_second_pass_close($bbcode)
	{
		// Already got the parts?
		if (!is_string($this->color_close) || !is_string($this->size_close)) {
			$this->get_tpl_parts();
		}

		return ($bbcode == 'color' ? $this->color_close : $this->size_close);
	}

	private function get_tpl_parts()
	{
		$tpl = $this->bbcode->bbcode_tpl('color', $this->bbcode_id);
		$strpos = strpos($tpl, '$2');
		$this->color_open = substr($tpl, 0, $strpos);
		$this->color_close = substr($tpl, $strpos + 2);

		// Same with size
		$tpl = $this->bbcode->bbcode_tpl('size', $this->bbcode_id);
		$strpos = strpos($tpl, '$2');
		$this->size_open = substr($tpl, 0, $strpos);
		$this->size_close = substr($tpl, $strpos + 2);
	}
}
