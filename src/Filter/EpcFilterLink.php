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

class epcFilterLink extends epcFilter
{
    protected function init(): string
    {
        $this->setProperties([
            'priority' => 500,
            'name'     => __('Link'),
            'help'     => __('Link some words. First term of the list is the term to link and second term the link.'),
            'has_list' => true,
            'htmltag'  => 'a',
            'class'    => ['a.epc-link'],
            'replace'  => '<a class="epc-link" title="%s" href="%s">%s</a>',
            'widget'   => '<a title="%s" href="%s">%s</a>',
        ]);

        $this->setSettings([
            'style'     => ['text-decoration: none; font-style: italic; color: #0000FF;'],
            'notag'     => 'a,h1,h2,h3',
            'tplValues' => ['EntryContent'],
            'pubPages'  => ['post.html'],
        ]);

        return 'link';
    }

    public function publicContent($tag, $args)
    {
        while ($this->records()->fetch()) {
            $args[0] = enhancePostContent::replaceString(
                $this->records()->epc_key,
                sprintf($this->replace, '\\1', $this->records()->epc_value, '\\1'),
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
                sprintf($this->widget, $this->records()->epc_value, $this->records()->epc_value, '\\1'),
                $content,
                $this
            );
        }

        return null;
    }
}
