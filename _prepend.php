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

Clearbricks::lib()->autoload([
    'libEPC'       => $d . 'lib.epc.php',
    'epcFilter'    => $d . 'lib.epc.filter.php',
    'epcRecords'   => $d . 'lib.epc.records.php',
    'adminEpcList' => $d . 'lib.epc.pager.php',
]);

foreach ($filters as $f) {
    Clearbricks::lib()->autoload(['epcFilter' . $f => $d . 'lib.epc.filters.php']);
    dcCore::app()->addBehavior('enhancePostContentFilters', ['epcFilter' . $f, 'create']);
}

dcCore::app()->url->register(
    'epccss',
    'epc.css',
    '^epc\.css',
    function ($args) {
        $css     = [];
        $filters = libEPC::getFilters();

        foreach ($filters as $id => $filter) {
            if ('' == $filter->class || '' == $filter->style) {
                continue;
            }

            $res = '';
            foreach ($filter->class as $k => $class) {
                $styles = $filter->style;
                $style  = html::escapeHTML(trim($styles[$k]));
                if ('' != $style) {
                    $res .= $class . ' {' . $style . '} ';
                }
            }

            if (!empty($res)) {
                $css[] = '/* CSS for enhancePostContent ' . $id . " */ \n" . $res . "\n";
            }
        }

        header('Content-Type: text/css; charset=UTF-8');

        echo implode("\n", $css);

        exit;
    }
);
