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

use dcCore;
use Dotclear\Plugin\enhancePostContent\Epc;
use Dotclear\Plugin\enhancePostContent\EpcFilter;

class EpcFilterSearch extends EpcFilter
{
    protected string $id = 'search';

    protected function initProperties(): array
    {
        return [
            'priority' => 100,
            'name'     => __('Search'),
            'help'     => __('Highlight searched words.'),
            'htmltag'  => '',
            'class'    => ['span.epc-search'],
            'replace'  => '<span class="epc-search" title="' . __('Search') . '">%s</span>',
        ];
    }

    protected function initSettings(): array
    {
        return [
            'nocase'    => true,
            'plural'    => true,
            'style'     => ['color: #FFCC66;'],
            'notag'     => 'h1,h2,h3',
            'tplValues' => ['EntryContent'],
            'pubPages'  => ['search.html'],
        ];
    }

    public function publicContent(string $tag, array $args): void
    {
        if (empty(dcCore::app()->public->search)) {
            return;
        }

        $searchs = explode(' ', dcCore::app()->public->search);

        foreach ($searchs as $k => $v) {
            $args[0] = Epc::replaceString(
                $v,
                sprintf($this->replace, '\\1'),
                $args[0],
                $this
            );
        }
    }
}
