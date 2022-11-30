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
if (!isset($old_version)) {
    return null;
}

# Move old filters lists from settings to database
if ($old_version && version_compare('0.6.6', $old_version, '>=')) {
    $f = dcCore::app()->con->select('SELECT * FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . " WHERE setting_ns='enhancePostContent' AND blog_id IS NOT NULL ");

    while ($f->fetch()) {
        if (preg_match('#enhancePostContent_(.*?)List#', $f->setting_id, $m)) {
            $curlist = @unserialize($f->setting_value);
            if (is_array($curlist)) {
                foreach ($curlist as $k => $v) {
                    $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . initEnhancePostContent::EPC_TABLE_NAME);
                    dcCore::app()->con->writeLock(dcCore::app()->prefix . initEnhancePostContent::EPC_TABLE_NAME);

                    $cur->epc_id     = dcCore::app()->con->select('SELECT MAX(epc_id) FROM ' . dcCore::app()->prefix . initEnhancePostContent::EPC_TABLE_NAME . ' ')->f(0) + 1;
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

# Move old filter name to filter id
} elseif ($old_version && version_compare('2021.10.05', $old_version, '>=')) {
    $rs = dcCore::app()->con->select('SELECT epc_id, epc_filter FROM ' . dcCore::app()->prefix . initEnhancePostContent::EPC_TABLE_NAME);
    while ($rs->fetch()) {
        $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . initEnhancePostContent::EPC_TABLE_NAME);

        $cur->epc_filter = strtolower($rs->epc_filter);

        $cur->update('WHERE epc_id = ' . $rs->epc_id . ' ');
        dcCore::app()->blog->triggerBlog();
    }
}
