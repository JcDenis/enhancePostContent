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

class EpcFilterReplace extends EpcFilter
{
    protected function init(): string
    {
        $this->setProperties([
            'priority' => 200,
            'name'     => __('Replace'),
            'help'     => __('Replace some text. First term of the list is the text to replace and second term the replacement.'),
            'has_list' => true,
            'htmltag'  => '',
            'class'    => ['span.epc-replace'],
            'replace'  => '<span class="epc-replace">%s</span>',
        ]);

        $this->setSettings([
            'nocase'    => true,
            'plural'    => true,
            'style'     => ['font-style: italic;'],
            'notag'     => 'h1,h2,h3',
            'tplValues' => ['EntryContent'],
            'pubPages'  => ['post.html'],
        ]);

        return 'replace';
    }

    public function publicContent(string $tag, array $args): void
    {
        while ($this->records()->fetch()) {
            $args[0] = Epc::replaceString(
                $this->records()->epc_key,
                sprintf($this->replace, $this->records()->epc_value, '\\2'),
                $args[0],
                $this
            );
        }
    }
}
