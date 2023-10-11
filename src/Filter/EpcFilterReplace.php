<?php

declare(strict_types=1);

namespace Dotclear\Plugin\enhancePostContent\Filter;

use Dotclear\Plugin\enhancePostContent\Epc;
use Dotclear\Plugin\enhancePostContent\EpcFilter;

/**
 * @brief   enhancePostContent replacement filter.
 * @ingroup enhancePostContent
 *
 * @author      Jean-Christian Denis
 * @copyright   Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class EpcFilterReplace extends EpcFilter
{
    protected string $id = 'replace';

    protected function initProperties(): array
    {
        return [
            'priority'    => 200,
            'name'        => __('Replace'),
            'description' => __('Replace some text. First term of the list is the text to replace and second term the replacement.'),
            'has_list'    => true,
            'ignore'      => ['pre','code'],
            'class'       => ['span.epc-replace'],
            'replace'     => '<span class="epc-replace">%s</span>',
        ];
    }

    protected function initSettings(): array
    {
        return [
            'nocase'   => true,
            'plural'   => true,
            'style'    => ['font-style: italic;'],
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
                sprintf($this->replace, $this->records()->f('epc_value'), '\\2'),
                $args[0],
                $this
            );
        }
    }
}
