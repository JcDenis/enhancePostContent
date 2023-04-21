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

namespace Dotclear\Plugin\enhancePostContent;

use dcCore;
use dcNsProcess;
use dcUtils;

class Frontend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = My::phpCompliant();

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        if (!dcCore::app()->blog?->settings->get(My::id())->get('active')) {
            return false;
        }

        dcCore::app()->addBehaviors([
            // Add CSS URL to frontend header
            'publicHeadContent' => function (): void {
                echo dcUtils::cssLoad(dcCore::app()->blog?->url . dcCore::app()->url->getURLFor('epccss'));
            },
            // Filter template blocks content
            'publicBeforeContentFilterV2' => function (string $tag, array $args): void {
                foreach (Epc::getFilters()->dump() as $filter) {
                    // test context
                    if (in_array((string) dcCore::app()->ctx?->__get('current_tpl'), $filter->page)
                        && in_array($tag, $filter->template)
                        && $args[0] != '' //content
                        && empty($args['encode_xml'])
                        && empty($args['encode_html'])
                        && empty($args['remove_html'])
                        && empty($args['strip_tags'])
                    ) {
                        $filter->publicContent($tag, $args);
                    }
                }
            },
            // Widgets
            'initWidgets' => [Widgets::class, 'initWidgets'],
        ]);

        return true;
    }
}
