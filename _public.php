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
if (!defined('DC_RC_PATH')) {
    return null;
}

require __DIR__ . '/_widgets.php';

dcCore::app()->blog->settings->addNamespace(basename(__DIR__));

if (!dcCore::app()->blog->settings->__get(basename(__DIR__))->active) {
    return null;
}

// Add filters CSS to page header
dcCore::app()->addBehavior('publicHeadContent', function () {
    echo dcUtils::cssLoad(dcCore::app()->blog->url . dcCore::app()->url->getURLFor('epccss'));
});
// Filter template blocks content
dcCore::app()->addBehavior('publicBeforeContentFilterV2', function ($tag, $args) {
    $filters = enhancePostContent::getFilters();

    foreach ($filters as $id => $filter) {
        if (!enhancePostContent::testContext($tag, $args, $filter)) {
            continue;
        }
        $filter->publicContent($tag, $args);
    }
});
