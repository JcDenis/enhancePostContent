<?php

declare(strict_types=1);

namespace Dotclear\Plugin\enhancePostContent\Filter;

use Dotclear\Plugin\enhancePostContent\Epc;
use Dotclear\Plugin\enhancePostContent\EpcFilter;

/**
 * @brief   enhancePostContent update filter.
 * @ingroup enhancePostContent
 *
 * @author      Jean-Christian Denis
 * @copyright   Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class EpcFilterUpdate extends EpcFilter
{
    protected string $id = 'update';

    protected function initProperties(): array
    {
        return [
            'priority'    => 300,
            'name'        => __('Update'),
            'description' => __('Update and show terms. First term of the list is the term to update and second term the new term.'),
            'has_list'    => true,
            'ignore'      => ['pre','code','del','ins'],
            'class'       => ['del.epc-update', 'ins.epc-update'],
            'replace'     => '<del class="epc-update">%s</del> <ins class="epc-update">%s</ins>',
        ];
    }

    protected function initSettings(): array
    {
        return [
            'nocase'   => true,
            'plural'   => true,
            'style'    => ['text-decoration: line-through;', 'font-style: italic;'],
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
                sprintf($this->replace, '\\1', $this->records()->f('epc_value')),
                $args[0],
                $this
            );
        }
    }
}
