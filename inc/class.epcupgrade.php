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
    public static function growUp()
    {
        $current = dcCore::app()->getVersion(basename(dirname('../' . __DIR__)));

        if ($current && version_compare($current, '0.6.6', '<=')) {
            self::upTo00060607();
        }

        if ($current && version_compare($current, '2021.10.06', '<=')) {
            self::upTo20211006();
        }

        if ($current && version_compare($current, '2022.11.20', '<=')) {
            self::upTo20221120();
        }
    }

    /**
     * 0.6.6
     *
     * - filters move from settings to dedicated table
     */
    private static function upTo00060607()
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

    /**
     * 2021.10.06
     *
     * - filters change name to id
     */
    private static function upTo20211006()
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

    /**
     * 2022.11.20
     *
     * - setting id changes to shorter one,
     * - setting ns changes to abstract one (no real changes),
     * - setting value change from serialize to json_encode (if it's array)
     */
    private static function upTo20221120()
    {
        // list of settings using serialize values to move to json
        $ids = [
            'allowedtplvalues',
            'allowedpubpages',
        ];
        foreach (enhancePostContent::getFilters() as $id => $f) {
            $ids[] = $id;
        }

        // get all enhancePostContent settings
        $record = dcCore::app()->con->select(
            'SELECT * FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
            "WHERE setting_ns = 'enhancePostContent' "
        );

        // update settings id, ns, value
        while ($record->fetch()) {
            if (preg_match('/^enhancePostContent_(.*?)$/', $record->setting_id, $match)) {
                $cur             = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME);
                $cur->setting_id = $match[1];
                $cur->setting_ns = basename(dirname('../' . __DIR__));

                if (in_array($match[1], $ids)) {
                    $cur->setting_value = json_encode(unserialize($record->setting_value));
                }

                $cur->update("WHERE setting_id = '" . $record->setting_id . "' and setting_ns = 'enhancePostContent' ");
            }
        }
    }
}
