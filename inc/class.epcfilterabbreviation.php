<?php
/**
 * @brief enhancePostContent, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugin
 *
 * @author Jean-Christian Denis and Contributors
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
if (!defined('DC_RC_PATH')) {
    return null;
}

class epcFilterAbbreviation extends epcFilter
{
    protected function init(): string
    {
        $this->setProperties([
            'priority' => 400,
            'name'     => __('Abbreviation'),
            'help'     => __('Explain some abbreviation. First term of the list is the abbreviation and second term the explanation.'),
            'has_list' => true,
            'htmltag'  => 'a',
            'class'    => ['abbr.epc-abbr'],
            'replace'  => '<abbr class="epc-abbr" title="%s">%s</abbr>',
            'widget'   => '<abbr title="%s">%s</abbr>',
        ]);

        $this->setSettings([
            'style'     => ['font-weight: bold;'],
            'notag'     => 'a,acronym,abbr,dfn,h1,h2,h3',
            'tplValues' => ['EntryContent'],
            'pubPages'  => ['post.html'],
        ]);

        return 'abbreviation';
    }

    public function publicContent($tag, $args)
    {
        while ($this->records()->fetch()) {
            $args[0] = enhancePostContent::replaceString(
                $this->records()->epc_key,
                sprintf($this->replace, __($this->records()->epc_value), '\\1'),
                $args[0],
                $this
            );
        }

        return null;
    }

    public function widgetList($content, $w, &$list)
    {
        while ($this->records()->fetch()) {
            $list[] = enhancePostContent::matchString(
                $this->records()->epc_key,
                sprintf($this->widget, __($this->records()->epc_value), '\\1'),
                $content,
                $this
            );
        }

        return null;
    }
}
