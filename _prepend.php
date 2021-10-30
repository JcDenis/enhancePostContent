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

$d = dirname(__FILE__) . '/inc/';

$__autoload['libEPC']       = $d . 'lib.epc.php';
$__autoload['epcRecords']   = $d . 'lib.epc.records.php';
$__autoload['adminEpcList'] = $d . 'lib.epc.pager.php';

$core->url->register(
    'epccss',
    'epc.css',
    '^epc\.css',
    ['publicEnhancePostContent', 'css']
);