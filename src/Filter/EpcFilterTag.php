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
declare(strict_types=1);

namespace Dotclear\Plugin\enhancePostContent\Filter;

use dcCore;
use Dotclear\Plugin\enhancePostContent\Epc;
use Dotclear\Plugin\enhancePostContent\EpcFilter;
use Dotclear\Plugin\widgets\WidgetsElement;

class EpcFilterTag extends EpcFilter
{
    protected function init(): string
    {
        $this->setProperties([
            'priority' => 900,
            'name'     => __('Tag'),
            'help'     => __('Highlight tags of your blog.'),
            'htmltag'  => 'a',
            'class'    => ['a.epc-tag'],
            'replace'  => '<a class="epc-tag" href="%s" title="' . __('Tag') . '">%s</a>',
            'widget'   => '<a href="%s" title="' . __('Tag') . '">%s</a>',
        ]);

        $this->setSettings([
            'style'     => ['text-decoration: none; border-bottom: 3px double #CCCCCC;'],
            'notag'     => 'a,h1,h2,h3',
            'tplValues' => ['EntryContent'],
            'pubPages'  => ['post.html'],
        ]);

        return 'tag';
    }

    public function publicContent(string $tag, array $args): void
    {
        if (!dcCore::app()->plugins->moduleExists('tags')) {
            return;
        }

        $metas = dcCore::app()->meta->getMetadata(['meta_type' => 'tag']);

        while ($metas->fetch()) {
            $args[0] = Epc::replaceString(
                $metas->meta_id,
                sprintf($this->replace, dcCore::app()->blog->url . dcCore::app()->url->getBase('tag') . '/' . $metas->meta_id, '\\1'),
                $args[0],
                $this
            );
        }
    }

    public function widgetList(string $content, WidgetsElement $w, array &$list): void
    {
        if (!dcCore::app()->plugins->moduleExists('tags')) {
            return;
        }

        $metas = dcCore::app()->meta->getMetadata(['meta_type' => 'tag']);

        while ($metas->fetch()) {
            $list[] = Epc::matchString(
                $metas->meta_id,
                sprintf($this->widget, dcCore::app()->blog->url . dcCore::app()->url->getBase('tag') . '/' . $metas->meta_id, '\\1'),
                $content,
                $this
            );
        }
    }
}
