<?php

namespace cosmo\sceditor\migrations;

class pcgf extends \phpbb\db\migration\migration
{

    public function update_data()
    {
        return array(
            array('custom', array(array($this, 'addbbcode'))),
        );
    }

    public function revert_data()
    {
        return array(
            array('custom', array(array($this, 'removebbcode'))),
        );
    }

    public function removebbcode()
    {
        $bbcodedata = array('spoiler',);

        $sql = 'DELETE FROM ' . $this->table_prefix . 'bbcodes WHERE ' . $this->db->sql_in_set('bbcode_tag', $bbcodedata);
        $this->db->sql_query($sql);
    }

    public function addbbcode()
    {
        $bbcodedata = array('spoiler',);

        $sql = 'DELETE FROM ' . $this->table_prefix . 'bbcodes WHERE ' . $this->db->sql_in_set('bbcode_tag', $bbcodedata);
        $this->db->sql_query($sql);

        $sql = 'SELECT MAX(bbcode_id) AS max_id
    				FROM ' . $this->table_prefix . 'bbcodes';
        $result = $this->db->sql_query($sql);

        $style_ids = 0;
        if ($styles_row = $this->db->sql_fetchrow()) {
            $style_ids = $styles_row['max_id'];
        }
        $this->db->sql_freeresult($result);

        // Make sure we don't start too low
        if ($style_ids <= NUM_CORE_BBCODES) {
            $style_ids = NUM_CORE_BBCODES;
        }

        $phpbb_bbcodes = array(
            array(
                   'bbcode_id' => ++$style_ids,
                   'bbcode_tag' => 'spoiler',
                   'bbcode_helpline' => '',
                   'display_on_posting' => 1,
                   'bbcode_match' => '[spoiler]{TEXT}[/spoiler]',
                   'bbcode_tpl' => '<div style="padding: 3px; background-color: #FFFFFF; border: 1px solid #d8d8d8; font-size: 1em;"><div style="text-transform: uppercase; border-bottom: 1px solid #CCCCCC; margin-bottom: 3px; font-size: 0.8em; font-weight: bold; display: block;"><span onClick="if (this.parentNode.parentNode.getElementsByTagName(\'div\')[1].getElementsByTagName(\'div\')[0].style.display != \'\') {  this.parentNode.parentNode.getElementsByTagName(\'div\')[1].getElementsByTagName(\'div\')[0].style.display = \'\'; this.innerHTML = \'<b>Spoiler: </b><a href=\\\'#\\\' onClick=\\\'return false;\\\'>ausblenden</a>\'; } else { this.parentNode.parentNode.getElementsByTagName(\'div\')[1].getElementsByTagName(\'div\')[0].style.display = \'none\'; this.innerHTML = \'<b>Spoiler: </b><a href=\\\'#\\\' onClick=\\\'return false;\\\'>anzeigen</a>\'; }" /><b>Spoiler: </b><a href="#" onClick="return false;">anzeigen</a></span></div><div class="quotecontent"><div style="display: none;">{TEXT}</div></div></div>',
                   'first_pass_match' => '!\[spoiler\](.*?)\[/spoiler\]!ies',
                   'first_pass_replace' => '\'[spoiler:$uid]\'.str_replace(array("\r\n", \'\"\', \'\\\'\', \'(\', \')\'), array("\n", \'"\', \'&#39;\', \'&#40;\', \'&#41;\'), trim(\'${1}\')).\'[/spoiler:$uid]\'',
                   'second_pass_match' => '!\[spoiler:$uid\](.*?)\[/spoiler:$uid\]!s',
                   'second_pass_replace' => '<div style="padding: 3px; background-color: #FFFFFF; border: 1px solid #d8d8d8; font-size: 1em;"><div style="text-transform: uppercase; border-bottom: 1px solid #CCCCCC; margin-bottom: 3px; font-size: 0.8em; font-weight: bold; display: block;"><span onClick="if (this.parentNode.parentNode.getElementsByTagName(\'div\')[1].getElementsByTagName(\'div\')[0].style.display != \'\') {  this.parentNode.parentNode.getElementsByTagName(\'div\')[1].getElementsByTagName(\'div\')[0].style.display = \'\'; this.innerHTML = \'<b>Spoiler: </b><a href=\\\'#\\\' onClick=\\\'return false;\\\'>ausblenden</a>\'; } else { this.parentNode.parentNode.getElementsByTagName(\'div\')[1].getElementsByTagName(\'div\')[0].style.display = \'none\'; this.innerHTML = \'<b>Spoiler: </b><a href=\\\'#\\\' onClick=\\\'return false;\\\'>anzeigen</a>\'; }" /><b>Spoiler: </b><a href="#" onClick="return false;">anzeigen</a></span></div><div class="quotecontent"><div style="display: none;">${1}</div></div></div>'
            ),
        );
        foreach ($phpbb_bbcodes as $eee) {
            $sql = 'INSERT INTO ' . $this->table_prefix . 'bbcodes' . $this->db->sql_build_array('INSERT', $eee);
            $this->db->sql_query($sql);
        }
    }
}
