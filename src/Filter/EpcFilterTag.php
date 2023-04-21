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

use ArrayObject;
use dcCore;
use Dotclear\Plugin\enhancePostContent\Epc;
use Dotclear\Plugin\enhancePostContent\EpcFilter;
use Dotclear\Plugin\widgets\WidgetsElement;

class EpcFilterTag extends EpcFilter
{
    protected string $id = 'tag';

    protected function initProperties(): array
    {
        return [
            'priority' => 900,
            'name'     => __('Tag'),
            'help'     => __('Highlight tags of your blog.'),
            'htmltag'  => 'pre,code,a',
            'class'    => ['a.epc-tag'],
            'replace'  => '<a class="epc-tag" href="%s" title="' . __('Tag') . '">%s</a>',
            'widget'   => '<a href="%s" title="' . __('Tag') . '">%s</a>',
        ];
    }

    protected function initSettings(): array
    {
        return [
            'style'     => ['text-decoration: none; border-bottom: 3px double #CCCCCC;'],
            'notag'     => 'pre,code,a,h1,h2,h3',
            'tplValues' => ['EntryContent'],
            'pubPages'  => ['post.html'],
        ];
    }

    public function publicContent(string $tag, array $args): void
    {
        if (!dcCore::app()->plugins->moduleExists('tags')) {
            return;
        }

        $metas = dcCore::app()->meta->getMetadata(['meta_type' => 'tag']);

        while ($metas->fetch()) {
            $args[0] = Epc::replaceString(
                $metas->f('meta_id'),
                sprintf($this->replace, dcCore::app()->blog?->url . dcCore::app()->url->getBase('tag') . '/' . $metas->f('meta_id'), '\\1'),
                $args[0],
                $this
            );
        }
    }

    public function widgetList(string $content, WidgetsElement $w, ArrayObject $list): void
    {
        if (!dcCore::app()->plugins->moduleExists('tags')) {
            return;
        }

        $metas = dcCore::app()->meta->getMetadata(['meta_type' => 'tag']);

        while ($metas->fetch()) {
            $list[] = Epc::matchString(
                $metas->f('meta_id'),
                sprintf($this->widget, dcCore::app()->blog?->url . dcCore::app()->url->getBase('tag') . '/' . $metas->f('meta_id'), '\\1'),
                $content,
                $this
            );
        }
    }
}
