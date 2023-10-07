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
use Dotclear\App;
use Dotclear\Plugin\enhancePostContent\Epc;
use Dotclear\Plugin\enhancePostContent\EpcFilter;
use Dotclear\Plugin\widgets\WidgetsElement;

class EpcFilterTag extends EpcFilter
{
    protected string $id = 'tag';

    protected function initProperties(): array
    {
        return [
            'priority'    => 900,
            'name'        => __('Tag'),
            'description' => __('Highlight tags of your blog.'),
            'ignore'      => ['pre','code','a'],
            'class'       => ['a.epc-tag'],
            'replace'     => '<a class="epc-tag" href="%s" title="' . __('Tag') . '">%s</a>',
            'widget'      => '<a href="%s" title="' . __('Tag') . '">%s</a>',
        ];
    }

    protected function initSettings(): array
    {
        return [
            'style'    => ['text-decoration: none; border-bottom: 3px double #CCCCCC;'],
            'notag'    => ['h1','h2','h3'],
            'template' => ['EntryContent'],
            'page'     => ['post.html'],
        ];
    }

    public function publicContent(string $tag, array $args): void
    {
        if (!App::plugins()->moduleExists('tags')) {
            return;
        }

        $metas = App::meta()->getMetadata(['meta_type' => 'tag']);

        while ($metas->fetch()) {
            $args[0] = Epc::replaceString(
                $metas->f('meta_id'),
                sprintf($this->replace, App::blog()->url() . App::url()->getBase('tag') . '/' . $metas->f('meta_id'), '\\1'),
                $args[0],
                $this
            );
        }
    }

    public function widgetList(string $content, WidgetsElement $w, ArrayObject $list): void
    {
        if (!App::plugins()->moduleExists('tags')) {
            return;
        }

        $metas = App::meta()->getMetadata(['meta_type' => 'tag']);

        while ($metas->fetch()) {
            $list[] = Epc::matchString(
                $metas->f('meta_id'),
                sprintf($this->widget, App::blog()->url() . App::url()->getBase('tag') . '/' . $metas->f('meta_id'), '\\1'),
                $content,
                $this
            );
        }
    }
}
