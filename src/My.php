<?php

declare(strict_types=1);

namespace Dotclear\Plugin\enhancePostContent;

use Dotclear\App;
use Dotclear\Module\MyPlugin;

/**
 * @brief   enhancePostContent My helper.
 * @ingroup enhancePostContent
 *
 * @author      Jean-Christian Denis
 * @copyright   Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class My extends MyPlugin
{
    public static function checkCustomContext(int $context): ?bool
    {
        return match ($context) {
            // Limit main backend featrues to content admin
            self::BACKEND, self::MANAGE, self::MENU => App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id()),

            default => null,
        };
    }
}
