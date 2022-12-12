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
if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

try {
    // Version
    if (!dcCore::app()->newVersion(
        basename(__DIR__), 
        dcCore::app()->plugins->moduleInfo(basename(__DIR__), 'version')
    )) {
        return null;
    }

    // Uppgrade
    epcUpgrade::preUpgrade();

    // Database
    $s = new dbStruct(dcCore::app()->con, dcCore::app()->prefix);
    $s->{initEnhancePostContent::TABLE_NAME}
        ->epc_id('bigint', 0, false)
        ->blog_id('varchar', 32, false)
        ->epc_type('varchar', 32, false, "'epc'")
        ->epc_filter('varchar', 64, false)
        ->epc_key('varchar', 255, false)
        ->epc_value('text', 0, false)
        ->epc_upddt('timestamp', 0, false, 'now()')

        ->primary('pk_epc', 'epc_id')
        ->index('idx_epc_blog_id', 'btree', 'blog_id')
        ->index('idx_epc_type', 'btree', 'epc_type')
        ->index('idx_epc_filter', 'btree', 'epc_filter')
        ->index('idx_epc_key', 'btree', 'epc_key');

    $si      = new dbStruct(dcCore::app()->con, dcCore::app()->prefix);
    $changes = $si->synchronize($s);
    $s       = null;

    // Settings
    dcCore::app()->blog->settings->addNamespace(basename(__DIR__));
    $s = dcCore::app()->blog->settings->__get(basename(__DIR__));

    $s->put('active', false, 'boolean', 'Enable enhancePostContent', false, true);
    $s->put('list_sortby', 'epc_key', 'string', 'Admin records list field order', false, true);
    $s->put('list_order', 'desc', 'string', 'Admin records list order', false, true);
    $s->put('list_nb', 20, 'integer', 'Admin records list nb per page', false, true);
    $s->put('allowedtplvalues', json_encode(enhancePostContent::defaultAllowedTplValues()), 'string', 'List of allowed template values', false, true);
    $s->put('allowedpubpages', json_encode(enhancePostContent::defaultAllowedPubPages()), 'string', 'List of allowed template pages', false, true);

    // Filters settings
    $filters = enhancePostContent::getFilters();
    foreach ($filters as $id => $filter) {
        // Only editable options
        $opt = [
            'nocase'    => $filter->nocase,
            'plural'    => $filter->plural,
            'style'     => $filter->style,
            'notag'     => $filter->notag,
            'tplValues' => $filter->tplValues,
            'pubPages'  => $filter->pubPages,
        ];
        $s->put($id, json_encode($opt), 'string', 'Settings for ' . $id, false, true);
    }

    // Upgrade
    epcUpgrade::postUpgrade();

    return true;
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());
}

return false;
