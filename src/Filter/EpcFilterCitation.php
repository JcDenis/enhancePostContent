<?php

declare(strict_types=1);

namespace Dotclear\Plugin\enhancePostContent\Filter;

use ArrayObject;
use Dotclear\Plugin\enhancePostContent\Epc;
use Dotclear\Plugin\enhancePostContent\EpcFilter;
use Dotclear\Plugin\widgets\WidgetsElement;

/**
 * @brief   enhancePostContent citaion filter.
 * @ingroup enhancePostContent
 *
 * @author      Jean-Christian Denis
 * @copyright   Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class EpcFilterCitation extends EpcFilter
{
    protected string $id = 'citation';

    protected function initProperties(): array
    {
        return [
            'priority'    => 600,
            'name'        => __('Citation'),
            'description' => __('Highlight citation of people. First term of the list is the citation and second term the author.'),
            'has_list'    => true,
            'ignore'      => ['pre','code','cite'],
            'class'       => ['cite.epc-cite'],
            'replace'     => '<cite class="epc-cite" title="%s">%s</cite>',
            'widget'      => '<cite title="%s">%s</cite>',
        ];
    }

    protected function initSettings(): array
    {
        return [
            'nocase'   => true,
            'style'    => ['font-style: italic;'],
            'notag'    => ['a','h1','h2','h3'],
            'template' => ['EntryContent'],
            'page'     => ['post.html'],
        ];
    }

    public function publicContent(string $tag, array $args): void
    {
        while ($this->records()->fetch()) {
            $args[0] = Epc::replaceString(
                $this->records()->f('epc_key'),
                sprintf($this->replace, __($this->records()->f('epc_value')), '\\1'),
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
                sprintf($this->widget, __($this->records()->f('epc_value')), '\\1'),
                $content,
                $this
            );
        }
    }
}
