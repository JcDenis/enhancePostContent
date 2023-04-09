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

class epcFilterAcronym extends epcFilter
{
    protected function init(): string
    {
        $this->setProperties([
            'priority' => 700,
            'name'     => __('Acronym'),
            'help'     => __('Explain some acronyms. First term of the list is the acornym and second term the explanation.'),
            'has_list' => true,
            'htmltag'  => 'acronym',
            'class'    => ['acronym.epc-acronym'],
            'replace'  => '<acronym class="epc-acronym" title="%s">%s</acronym>',
            'widget'   => '<acronym title="%s">%s</acronym>',
        ]);

        $this->setSettings([
            'style'     => ['font-weight: bold;'],
            'notag'     => 'a,acronym,abbr,dfn,h1,h2,h3',
            'tplValues' => ['EntryContent'],
            'pubPages'  => ['post.html'],
        ]);

        return 'acronym';
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
