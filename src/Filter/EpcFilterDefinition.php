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

use Dotclear\Plugin\enhancePostContent\Epc;
use Dotclear\Plugin\enhancePostContent\EpcFilter;
use Dotclear\Plugin\widgets\WidgetsElement;

class EpcFilterDefinition extends EpcFilter
{
    protected function init(): string
    {
        $this->setProperties([
            'priority' => 800,
            'name'     => __('Definition'),
            'help'     => __('Explain some definition. First term of the list is the sample to define and second term the explanation.'),
            'has_list' => true,
            'htmltag'  => 'dfn',
            'class'    => ['dfn.epc-dfn'],
            'replace'  => '<dfn class="epc-dfn" title="%s">%s</dfn>',
            'widget'   => '<dfn class="epc-dfn" title="%s">%s</dfn>',
        ]);

        $this->setSettings([
            'style'     => ['font-weight: bold;'],
            'notag'     => 'a,acronym,abbr,dfn,h1,h2,h3',
            'tplValues' => ['EntryContent'],
            'pubPages'  => ['post.html'],
        ]);

        return 'definition';
    }

    public function publicContent(string $tag, array $args): void
    {
        while ($this->records()->fetch()) {
            $args[0] = Epc::replaceString(
                $this->records()->epc_key,
                sprintf($this->replace, __($this->records()->epc_value), '\\1'),
                $args[0],
                $this
            );
        }
    }

    public function widgetList(string $content, WidgetsElement $w, array &$list): void
    {
        while ($this->records()->fetch()) {
            $list[] = Epc::matchString(
                $this->records()->epc_key,
                sprintf($this->widget, __($this->records()->epc_value), '\\1'),
                $content,
                $this
            );
        }
    }
}
