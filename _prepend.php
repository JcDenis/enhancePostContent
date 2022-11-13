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

$filters = [
    'Tag',
    'Search',
    'Acronym',
    'Abbreviation',
    'Definition',
    'Citation',
    'Link',
    'Replace',
    'Update',
    'Twitter',
];

$d = __DIR__ . '/inc/';

Clearbricks::lib()->autoload(['libEPC' => $d . 'lib.epc.php']);
Clearbricks::lib()->autoload(['epcFilter' => $d . 'lib.epc.filter.php']);
Clearbricks::lib()->autoload(['epcRecords' => $d . 'lib.epc.records.php']);
Clearbricks::lib()->autoload(['adminEpcList' => $d . 'lib.epc.pager.php']);

foreach ($filters as $f) {
    Clearbricks::lib()->autoload(['epcFilter' . $f => $d . 'lib.epc.filters.php']);
    dcCore::app()->addBehavior('enhancePostContentFilters', ['epcFilter' . $f, 'create']);
}

dcCore::app()->url->register(
    'epccss',
    'epc.css',
    '^epc\.css',
    ['publicEnhancePostContent', 'css']
);
