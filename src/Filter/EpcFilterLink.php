<?php

declare(strict_types=1);

namespace Dotclear\Plugin\enhancePostContent\Filter;

use ArrayObject;
use Dotclear\Plugin\enhancePostContent\Epc;
use Dotclear\Plugin\enhancePostContent\EpcFilter;
use Dotclear\Plugin\widgets\WidgetsElement;

/**
 * @brief   enhancePostContent link filter.
 * @ingroup enhancePostContent
 *
 * @author      Jean-Christian Denis
 * @copyright   Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class EpcFilterLink extends EpcFilter
{
    protected string $id = 'link';

    protected function initProperties(): array
    {
        return [
            'priority'    => 500,
            'name'        => __('Link'),
            'description' => __('Link some words. First term of the list is the term to link and second term the link.'),
            'has_list'    => true,
            'ignore'      => ['pre','code','a'],
            'class'       => ['a.epc-link'],
            'replace'     => '<a class="epc-link" title="%s" href="%s">%s</a>',
            'widget'      => '<a title="%s" href="%s">%s</a>',
        ];
    }

    protected function initSettings(): array
    {
        return [
            'style'    => ['text-decoration: none; font-style: italic; color: #0000FF;'],
            'notag'    => ['h1','h2','h3'],
            'template' => ['EntryContent'],
            'page'     => ['post.html'],
        ];
    }

    public function publicContent(string $tag, array $args): void
    {
        while ($this->records()->fetch()) {
            $args[0] = Epc::replaceString(
                $this->records()->f('epc_key'),
                sprintf($this->replace, '\\1', $this->records()->f('epc_value'), '\\1'),
                $args[0],
                $this
            );
        }
    }

    public function widgetList(string $content, WidgetsElement $w, ArrayObject $list): void
    {
        while ($this->records()->fetch()) {
            $list[] = Epc::matchString(
                $this->records()->f('epc_key'),
                sprintf($this->widget, $this->records()->f('epc_value'), $this->records()->f('epc_value'), '\\1'),
                $content,
                $this
            );
        }
    }
}
