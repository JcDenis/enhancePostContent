<?php

declare(strict_types=1);

namespace Dotclear\Plugin\enhancePostContent\Filter;

use ArrayObject;
use Dotclear\Plugin\enhancePostContent\Epc;
use Dotclear\Plugin\enhancePostContent\EpcFilter;
use Dotclear\Plugin\widgets\WidgetsElement;

/**
 * @brief   enhancePostContent abbreviation filter.
 * @ingroup enhancePostContent
 *
 * @author      Jean-Christian Denis
 * @copyright   Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class EpcFilterAbbreviation extends EpcFilter
{
    protected string $id = 'abbreviation';

    protected function initProperties(): array
    {
        return [
            'priority'    => 400,
            'name'        => __('Abbreviation'),
            'description' => __('Explain some abbreviation. First term of the list is the abbreviation and second term the explanation.'),
            'has_list'    => true,
            'ignore'      => ['pre','code','a'],
            'class'       => ['abbr.epc-abbr'],
            'replace'     => '<abbr class="epc-abbr" title="%s">%s</abbr>',
            'widget'      => '<abbr title="%s">%s</abbr>',
        ];
    }

    protected function initSettings(): array
    {
        return [
            'style'    => ['font-weight: bold;'],
            'notag'    => ['acronym','abbr','dfn','h1','h2','h3'],
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
