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

class EpcFilterTwitter extends EpcFilter
{
    protected string $id = 'twitter';

    protected function initProperties(): array
    {
        return [
            'priority' => 1000,
            'name'     => __('Twitter'),
            'help'     => __('Add link to twitter user page. Every word started with "@" will be considered as twitter user.'),
            'htmltag'  => 'a',
            'class'    => ['a.epc-twitter'],
            'replace'  => '<a class="epc-twitter" title="' . __("View this user's twitter page") . '" href="%s">%s</a>',
        ];
    }

    protected function initSettings(): array
    {
        return [
            'style'     => ['text-decoration: none; font-weight: bold; font-style: italic; color: #0000FF;'],
            'notag'     => 'a,h1,h2,h3',
            'tplValues' => ['EntryContent'],
            'pubPages'  => ['post.html'],
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
