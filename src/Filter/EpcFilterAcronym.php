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

class EpcFilterAcronym extends EpcFilter
{
    protected string $id = 'acronym';

    protected function initProperties(): array
    {
        return [
            'priority' => 700,
            'name'     => __('Acronym'),
            'help'     => __('Explain some acronyms. First term of the list is the acornym and second term the explanation.'),
            'has_list' => true,
            'htmltag'  => 'acronym',
            'class'    => ['acronym.epc-acronym'],
            'replace'  => '<acronym class="epc-acronym" title="%s">%s</acronym>',
            'widget'   => '<acronym title="%s">%s</acronym>',
        ];
    }

    protected function initSettings(): array
    {
        return [
            'style'     => ['font-weight: bold;'],
            'notag'     => 'a,acronym,abbr,dfn,h1,h2,h3',
            'tplValues' => ['EntryContent'],
            'pubPages'  => ['post.html'],
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
