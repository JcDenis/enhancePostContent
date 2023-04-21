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
use Dotclear\Plugin\enhancePostContent\Epc;
use Dotclear\Plugin\enhancePostContent\EpcFilter;
use Dotclear\Plugin\widgets\WidgetsElement;

class EpcFilterDefinition extends EpcFilter
{
    protected string $id = 'definition';

    protected function initProperties(): array
    {
        return [
            'priority'    => 800,
            'name'        => __('Definition'),
            'description' => __('Explain some definition. First term of the list is the sample to define and second term the explanation.'),
            'has_list'    => true,
            'ignore'      => ['pre','code','dfn'],
            'class'       => ['dfn.epc-dfn'],
            'replace'     => '<dfn class="epc-dfn" title="%s">%s</dfn>',
            'widget'      => '<dfn class="epc-dfn" title="%s">%s</dfn>',
        ];
    }

    protected function initSettings(): array
    {
        return [
            'style'    => ['font-weight: bold;'],
            'notag'    => ['a','acronym','abbr','dfn','h1','h2','h3'],
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
