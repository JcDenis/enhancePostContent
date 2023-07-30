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
use Dotclear\Module\MyPlugin;

/**
 * This module definitions.
 */
class My extends MyPlugin
{
    /** @var    string  Plugin table name */
    public const TABLE_NAME = 'epc';

    /** @var    array   Distributed filters */
    public const DEFAULT_FILTERS = [
        Filter\EpcFilterTag::class,
        Filter\EpcFilterSearch::class,
        Filter\EpcFilterAcronym::class,
        Filter\EpcFilterAbbreviation::class,
        Filter\EpcFilterDefinition::class,
        Filter\EpcFilterCitation::class,
        Filter\EpcFilterLink::class,
        Filter\EpcFilterReplace::class,
        Filter\EpcFilterUpdate::class,
        Filter\EpcFilterTwitter::class,
    ];

    public static function checkCustomContext(int $context): ?bool
    {
        return !in_array($context, [My::BACKEND, My::MANAGE, My::MENU]) ? null :
            dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id);
    }
}
