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

class epcUpgrade
{
    public static function preUpgrade()
    {
        $current = dcCore::app()->getVersion(basename(dirname('../' . __DIR__)));
        if ($current && version_compare($current, '2022.12.10', '<')) {
            self::preUpgrade20221210();
        }
    }

    public static function postUpgrade()
    {
        $current = dcCore::app()->getVersion(basename(dirname('../' . __DIR__)));
        if ($current && version_compare($current, '0.6.6', '<')) {
            self::postUpgrade00060607();
        }

        if ($current && version_compare($current, '2021.10.06', '<')) {
            self::postUpgrade20211006();
        }
    }

    private static function preUpgrade20221210()
    {
        // Rename settings
        $setting_ids = [
            'enhancePostContent_active'           => 'active',
            'enhancePostContent_list_sortby'       => 'list_sortby',
            'enhancePostContent_list_order'  => 'list_order',
            'enhancePostContent_list_nb'      => 'list_nb',
            'enhancePostContent_allowedtplvalues'     => 'allowedtplvalues',
            'enhancePostContent_allowedpubpages'  => 'allowedpubpages',
        ];

        foreach ($setting_ids as $old => $new) {
            $cur             = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME);
            $cur->setting_id = $new;
            $cur->setting_ns = basename(dirname('../' . __DIR__));
            $cur->update("WHERE setting_id = '" . $old . "' and setting_ns = 'enhancePostContent' ");
        }

        // use json rather than serialise for settings array
        $setting_values = [
            'allowedtplvalues' => json_encode(enhancePostContent::defaultAllowedTplValues()),
            'allowedpubpages'  =>json_encode(enhancePostContent::defaultAllowedPubPages()),
        ];

        $record = dcCore::app()->con->select(
            'SELECT * FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
            "WHERE setting_ns = '" . dcCore::app()->con->escape(basename(dirname('../' . __DIR__))) . "' "
        );

        while ($record->fetch()) {
            foreach ($setting_values as $key => $default) {
                try {
                    $value = @unserialize($record->__get($key));
                } catch(Exception) {
                    $value = $default;
                }

                $cur                = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME);
                $cur->setting_value = json_encode(!is_array($value) ? $default : $value);
                $cur->update(
                    "WHERE setting_id = '" . $key . "' and setting_ns = '" . dcCore::app()->con->escape($record->setting_ns) . "' " .
                    'AND blog_id ' . (null === $record->blog_id ? 'IS NULL ' : ("= '" . dcCore::app()->con->escape($record->blog_id) . "' "))
                );
            }
        }
    }

    private static function postUpgrade00060607()
    {
        # Move old filters lists from settings to database
        $f = dcCore::app()->con->select('SELECT * FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . " WHERE setting_ns='enhancePostContent' AND blog_id IS NOT NULL ");

        while ($f->fetch()) {
            if (preg_match('#enhancePostContent_(.*?)List#', $f->setting_id, $m)) {
                $curlist = @unserialize($f->setting_value);
                if (is_array($curlist)) {
                    foreach ($curlist as $k => $v) {
                        $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . initEnhancePostContent::TABLE_NAME);
                        dcCore::app()->con->writeLock(dcCore::app()->prefix . initEnhancePostContent::TABLE_NAME);

                        $cur->epc_id     = dcCore::app()->con->select('SELECT MAX(epc_id) FROM ' . dcCore::app()->prefix . initEnhancePostContent::TABLE_NAME . ' ')->f(0) + 1;
                        $cur->blog_id    = $f->blog_id;
                        $cur->epc_filter = strtolower($m[1]);
                        $cur->epc_key    = $k;
                        $cur->epc_value  = $v;

                        $cur->insert();
                        dcCore::app()->con->unlock();
                    }
                }
                dcCore::app()->con->execute('DELETE FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . " WHERE setting_id='" . $f->setting_id . "' AND setting_ns='enhancePostContent' AND blog_id='" . $f->blog_id . "' ");
            }
        }
    }

    private static function postUpgrade20211006()
    {
        # Move old filter name to filter id
        $rs = dcCore::app()->con->select('SELECT epc_id, epc_filter FROM ' . dcCore::app()->prefix . initEnhancePostContent::TABLE_NAME);
        while ($rs->fetch()) {
            $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . initEnhancePostContent::TABLE_NAME);

            $cur->epc_filter = strtolower($rs->epc_filter);

            $cur->update('WHERE epc_id = ' . $rs->epc_id . ' ');
            dcCore::app()->blog->triggerBlog();
        }
    }
}
