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
use dcNamespace;
use dcNsProcess;
use Dotclear\Database\Structure;
use Exception;

class Install extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN')
            && dcCore::app()->newVersion(My::id(), dcCore::app()->plugins->moduleInfo(My::id(), 'version'));

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        try {
            // Database
            $s = new Structure(dcCore::app()->con, dcCore::app()->prefix);
            $s->__get(My::TABLE_NAME)
                ->field('epc_id', 'bigint', 0, false)
                ->field('blog_id', 'varchar', 32, false)
                ->field('epc_type', 'varchar', 32, false, "'epc'")
                ->field('epc_filter', 'varchar', 64, false)
                ->field('epc_key', 'varchar', 255, false)
                ->field('epc_value', 'text', 0, false)
                ->field('epc_upddt', 'timestamp', 0, false, 'now()')

                ->primary('pk_epc', 'epc_id')
                ->index('idx_epc_blog_id', 'btree', 'blog_id')
                ->index('idx_epc_type', 'btree', 'epc_type')
                ->index('idx_epc_filter', 'btree', 'epc_filter')
                ->index('idx_epc_key', 'btree', 'epc_key');

            (new Structure(dcCore::app()->con, dcCore::app()->prefix))->synchronize($s);
            $s = null;

            // Uppgrade
            self::growUp();

            // Settings
            $s = dcCore::app()->blog?->settings->get(My::id());
            if (is_null($s)) {
                return false;
            }

            $s->put('active', false, 'boolean', 'Enable enhancePostContent', false, true);
            $s->put('list_sortby', 'epc_key', 'string', 'Admin records list field order', false, true);
            $s->put('list_order', 'desc', 'string', 'Admin records list order', false, true);
            $s->put('list_nb', 20, 'integer', 'Admin records list nb per page', false, true);
            $s->put('allowedtplvalues', json_encode(Epc::defaultAllowedTemplateValue()), 'string', 'List of allowed template values', false, true);
            $s->put('allowedpubpages', json_encode(Epc::defaultAllowedTemplatePage()), 'string', 'List of allowed template pages', false, true);

            // Filters settings
            foreach (Epc::getFilters()->dump() as $filter) {
                // Only editable options
                $opt = [
                    'nocase'   => $filter->nocase,
                    'plural'   => $filter->plural,
                    'style'    => $filter->style,
                    'notag'    => $filter->notag,
                    'template' => $filter->template,
                    'page'     => $filter->page,
                ];
                $s->put($filter->id(), json_encode($opt), 'string', 'Settings for ' . $filter->id(), false, true);
            }

            return true;
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());

            return false;
        }
    }

    /**
     * Check upgrade to apply
     */
    public static function growUp(): void
    {
        $current = dcCore::app()->getVersion(My::id());

        if ($current && version_compare($current, '0.6.6', '<=')) {
            self::upTo00060607();
        }

        if ($current && version_compare($current, '2021.10.06', '<=')) {
            self::upTo20211006();
        }

        if ($current && version_compare($current, '2022.11.20', '<=')) {
            self::upTo20221120();
        }

        // 2023.04.22: not replaced: tplValues->template and pubPages->page
    }

    /**
     * Upgrade from 0.6.6
     *
     * - filters move from settings to dedicated table
     */
    private static function upTo00060607(): void
    {
        # Move old filters lists from settings to database
        $record = dcCore::app()->con->select('SELECT * FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . " WHERE setting_ns='enhancePostContent' AND blog_id IS NOT NULL ");

        while ($record->fetch()) {
            if (preg_match('#enhancePostContent_(.*?)List#', $record->f('setting_id'), $m)) {
                $curlist = @unserialize($record->f('setting_value'));
                if (is_array($curlist)) {
                    foreach ($curlist as $k => $v) {
                        $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . My::TABLE_NAME);
                        dcCore::app()->con->writeLock(dcCore::app()->prefix . My::TABLE_NAME);

                        $cur->setField('epc_id', (int) dcCore::app()->con->select('SELECT MAX(epc_id) FROM ' . dcCore::app()->prefix . My::TABLE_NAME . ' ')->f(0) + 1);
                        $cur->setField('blog_id', $record->f('blog_id'));
                        $cur->setField('epc_filter', strtolower($m[1]));
                        $cur->setField('epc_key', $k);
                        $cur->setField('epc_value', $v);

                        $cur->insert();
                        dcCore::app()->con->unlock();
                    }
                }
                dcCore::app()->con->execute('DELETE FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . " WHERE setting_id='" . $record->f('setting_id') . "' AND setting_ns='enhancePostContent' AND blog_id='" . $record->f('blog_id') . "' ");
            }
        }
    }

    /**
     * Upgrade from 2021.10.06
     *
     * - filters change name to id
     */
    private static function upTo20211006(): void
    {
        # Move old filter name to filter id
        $record = dcCore::app()->con->select('SELECT epc_id, epc_filter FROM ' . dcCore::app()->prefix . My::TABLE_NAME);
        while ($record->fetch()) {
            $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . My::TABLE_NAME);

            $cur->setField('epc_filter', strtolower($record->f('epc_filter')));

            $cur->update('WHERE epc_id = ' . $record->f('epc_id') . ' ');
            dcCore::app()->blog?->triggerBlog();
        }
    }

    /**
     * Upgrade from 2022.11.20
     *
     * - setting id changes to shorter one,
     * - setting ns changes to abstract one (no real changes),
     * - setting value change from serialize to json_encode (if it's array)
     */
    private static function upTo20221120(): void
    {
        // list of settings using serialize values to move to json
        $ids = array_merge(
            [
                'allowedtplvalues',
                'allowedpubpages',
            ],
            array_values(Epc::getFilters()->nid())
        );

        // get all enhancePostContent settings
        $record = dcCore::app()->con->select(
            'SELECT * FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
            "WHERE setting_ns = 'enhancePostContent' "
        );

        // update settings id, ns, value
        while ($record->fetch()) {
            if (preg_match('/^enhancePostContent_(.*?)$/', $record->f('setting_id'), $match)) {
                $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME);
                $cur->setField('setting_id', $match[1]);
                $cur->setField('setting_ns', My::id());

                if (in_array($match[1], $ids)) {
                    $cur->setfield('setting_value', json_encode(unserialize($record->f('setting_value'))));
                }

                $cur->update("WHERE setting_id = '" . $record->f('setting_id') . "' and setting_ns = 'enhancePostContent' ");
            }
        }
    }
}
