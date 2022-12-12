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
    # Version
    if (!dcCore::app()->newVersion(
        basename(__DIR__), 
        dcCore::app()->plugins->moduleInfo(basename(__DIR__), 'version')
    )) {
        return null;
    }

    # Database
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

    # Settings
    dcCore::app()->blog->settings->addNamespace(basename(__DIR__));
    $s = dcCore::app()->blog->settings->__get(basename(__DIR__));

    $s->put('enhancePostContent_active', false, 'boolean', 'Enable enhancePostContent', false, true);
    $s->put('enhancePostContent_list_sortby', 'epc_key', 'string', 'Admin records list field order', false, true);
    $s->put('enhancePostContent_list_order', 'desc', 'string', 'Admin records list order', false, true);
    $s->put('enhancePostContent_list_nb', 20, 'integer', 'Admin records list nb per page', false, true);
    $s->put('enhancePostContent_allowedtplvalues', serialize(enhancePostContent::defaultAllowedTplValues()), 'string', 'List of allowed template values', false, true);
    $s->put('enhancePostContent_allowedpubpages', serialize(enhancePostContent::defaultAllowedPubPages()), 'string', 'List of allowed template pages', false, true);

    # Filters settings
    $filters = enhancePostContent::getFilters();
    foreach ($filters as $id => $filter) {
        # Only editable options
        $opt = [
            'nocase'    => $filter->nocase,
            'plural'    => $filter->plural,
            'style'     => $filter->style,
            'notag'     => $filter->notag,
            'tplValues' => $filter->tplValues,
            'pubPages'  => $filter->pubPages,
        ];
        $s->put('enhancePostContent_' . $id, serialize($opt), 'string', 'Settings for ' . $id, false, true);
        /*        # only tables
                if (isset($filter['list'])) {
                    $s->put('enhancePostContent_' . $id . 'List', serialize($filter['list']), 'string', 'List for ' . $id, false, true);
                }
        */
    }

    # Update old versions
    $old_version = dcCore::app()->getVersion($mod_id);
    if ($old_version && version_compare('2021.10.05', $old_version, '>=')) {
        include_once dirname(__FILE__) . '/inc/lib.epc.update.php';
    }

    return true;
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());
}

return false;
