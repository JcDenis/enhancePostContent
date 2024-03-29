<?php

declare(strict_types=1);

namespace Dotclear\Plugin\enhancePostContent;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief       enhancePostContent frontend class.
 * @ingroup     enhancePostContent
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status() || !My::settings()->get('active')) {
            return false;
        }

        App::behavior()->addBehaviors([
            // Add CSS URL to frontend header
            'publicHeadContent' => function (): void {
                echo App::plugins()->cssLoad(App::blog()->url() . App::url()->getURLFor('epccss'));
            },
            // Filter template blocks content
            'publicBeforeContentFilterV2' => function (string $tag, array $args): void {
                foreach (Epc::getFilters()->dump() as $filter) {
                    // test context
                    if (in_array((string) App::frontend()->context()->__get('current_tpl'), $filter->page)
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
            'initWidgets' => Widgets::initWidgets(...),
        ]);

        return true;
    }
}
