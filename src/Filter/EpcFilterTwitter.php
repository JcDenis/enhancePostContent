<?php

declare(strict_types=1);

namespace Dotclear\Plugin\enhancePostContent\Filter;

use Dotclear\Plugin\enhancePostContent\Epc;
use Dotclear\Plugin\enhancePostContent\EpcFilter;

/**
 * @brief   enhancePostContent twitter filter.
 * @ingroup enhancePostContent
 *
 * @author      Jean-Christian Denis
 * @copyright   Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class EpcFilterTwitter extends EpcFilter
{
    protected string $id = 'twitter';

    protected function initProperties(): array
    {
        return [
            'priority'    => 1000,
            'name'        => __('Twitter'),
            'description' => __('Add link to twitter user page. Every word started with "@" will be considered as twitter user.'),
            'ingore'      => ['pre','code','a'],
            'class'       => ['a.epc-twitter'],
            'replace'     => '<a class="epc-twitter" title="' . __("View this user's twitter page") . '" href="%s">%s</a>',
        ];
    }

    protected function initSettings(): array
    {
        return [
            'style'    => ['text-decoration: none; font-weight: bold; font-style: italic; color: #0000FF;'],
            'notag'    => ['h1','h2','h3'],
            'template' => ['EntryContent'],
            'page'     => ['post.html'],
        ];
    }

    public function publicContent(string $tag, array $args): void
    {
        $args[0] = Epc::replaceString(
            '[A-Za-z0-9_]{2,}',
            sprintf($this->replace, 'http://twitter.com/\\1', '\\1'),
            $args[0],
            $this,
            '[^@]@',
            '\b'
        );
    }
}
