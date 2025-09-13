<?php

declare(strict_types=1);

namespace Dotclear\Plugin\enhancePostContent;

use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Exception;

/**
 * @brief       enhancePostContent filters records.
 * @ingroup     enhancePostContent
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class EpcRecord
{
    /**
     * Get records.
     *
     * @param   array<string, mixed>    $params         The query params
     * @param   bool                    $count_only     Count only
     *
     * @return  MetaRecord    The records instance
     */
    public static function getRecords(array $params, bool $count_only = false): MetaRecord
    {
        if ($count_only) {
            $strReq = 'SELECT count(E.epc_id) ';
        } else {
            $content_req = '';
            if (!empty($params['columns']) && is_array($params['columns'])) {
                $content_req .= implode(', ', $params['columns']) . ', ';
            }
            $strReq = 'SELECT E.epc_id, E.blog_id, E.epc_type, E.epc_upddt, ' .
            $content_req .
            'E.epc_filter, E.epc_key, E.epc_value ';
        }

        $strReq .= 'FROM ' . App::db()->con()->prefix() . Epc::TABLE_NAME . ' E ';

        if (!empty($params['from'])) {
            $strReq .= $params['from'] . ' ';
        }

        $strReq .= "WHERE E.blog_id = '" . App::db()->con()->escapeStr(App::blog()->id()) . "' ";

        if (isset($params['epc_type'])) {
            if (is_array($params['epc_type']) && !empty($params['epc_type'])) {
                $strReq .= 'AND E.epc_type ' . App::db()->con()->in($params['epc_type']);
            } elseif ($params['epc_type'] != '') {
                $strReq .= "AND E.epc_type = '" . App::db()->con()->escapeStr((string) $params['epc_type']) . "' ";
            }
        } else {
            $strReq .= "AND E.epc_type = 'epc' ";
        }

        if (isset($params['epc_filter'])) {
            if (is_array($params['epc_filter']) && !empty($params['epc_filter'])) {
                $strReq .= 'AND E.epc_filter ' . App::db()->con()->in($params['epc_filter']);
            } elseif ($params['epc_filter'] != '') {
                $strReq .= "AND E.epc_filter = '" . App::db()->con()->escapeStr((string) $params['epc_filter']) . "' ";
            }
        }

        if (!empty($params['epc_id'])) {
            if (is_array($params['epc_id'])) {
                array_walk($params['epc_id'], function (&$v, $k) { if ($v !== null) { $v = (int) $v; }});
            } else {
                $params['epc_id'] = [(int) $params['epc_id']];
            }
            $strReq .= 'AND E.epc_id ' . App::db()->con()->in($params['epc_id']);
        } elseif (isset($params['not_id']) && is_numeric($params['not_id'])) {
            $strReq .= "AND NOT E.epc_id = '" . $params['not_id'] . "' ";
        }

        if (isset($params['epc_key'])) {
            if (is_array($params['epc_key']) && !empty($params['epc_key'])) {
                $strReq .= 'AND E.epc_key ' . App::db()->con()->in($params['epc_key']);
            } elseif ($params['epc_key'] != '') {
                $strReq .= "AND E.epc_key = '" . App::db()->con()->escapeStr((string) $params['epc_key']) . "' ";
            }
        }

        if (!empty($params['sql'])) {
            $strReq .= $params['sql'] . ' ';
        }

        if (!$count_only) {
            if (!empty($params['order'])) {
                $strReq .= 'ORDER BY ' . App::db()->con()->escapeStr((string) $params['order']) . ' ';
            } else {
                $strReq .= 'ORDER BY E.epc_key ASC ';
            }
        }

        if (!$count_only && !empty($params['limit'])) {
            $strReq .= App::db()->con()->limit($params['limit']);
        }

        return new MetaRecord(App::db()->con()->select($strReq));
    }

    /**
     * Add record.
     *
     * @param   Cursor  $cur    The Cursor
     *
     * @return  int     The record ID
     */
    public static function addRecord(Cursor $cur): int
    {
        App::db()->con()->writeLock(App::db()->con()->prefix() . Epc::TABLE_NAME);

        try {
            $cur->setField('epc_id', self::getNextId());
            $cur->setField('blog_id', App::blog()->id());
            $cur->setField('epc_upddt', date('Y-m-d H:i:s'));

            self::getCursor($cur);

            $cur->insert();
            App::db()->con()->unlock();
        } catch (Exception $e) {
            App::db()->con()->unlock();

            throw $e;
        }
        App::blog()->triggerBlog();

        # --BEHAVIOR-- enhancePostContentAfterAddRecord : Cursor
        App::behavior()->callBehavior('enhancePostContentAfterAddRecord', $cur);

        return (int) $cur->getField('epc_id');
    }

    /**
     * Update a record.
     *
     * @param   int     $id     The record ID
     * @param   Cursor  $cur    The Cursor
     */
    public static function updRecord(int $id, Cursor $cur): void
    {
        if (empty($id)) {
            throw new Exception(__('No such record ID'));
        }

        $cur->setField('epc_upddt', date('Y-m-d H:i:s'));

        $cur->update('WHERE epc_id = ' . $id . " AND blog_id = '" . App::db()->con()->escapeStr(App::blog()->id()) . "' ");
        App::blog()->triggerBlog();

        # --BEHAVIOR-- enhancePostContentAfterUpdRecord : Cursor, int
        App::behavior()->callBehavior('enhancePostContentAfterUpdRecord', $cur, $id);
    }

    /**
     * Check if a record exists.
     *
     * @param   null|string     $filter     The filter ID
     * @param   null|string     $key        The record key
     * @param   null|int        $not_id     Exclude an id
     *
     * @return  bool    True if it exists
     */
    public static function isRecord(?string $filter, ?string $key, ?int $not_id = null): bool
    {
        return 0 < self::getRecords([
            'epc_filter' => $filter,
            'epc_key'    => $key,
            'not_id'     => $not_id,
        ], true)->f(0);
    }

    /**
     * Delete a record.
     *
     * @param   int     $id     The record ID
     */
    public static function delRecord(int $id): void
    {
        if (empty($id)) {
            throw new Exception(__('No such record ID'));
        }

        # --BEHAVIOR-- enhancePostContentBeforeDelRecord, int
        App::behavior()->callBehavior('enhancePostContentbeforeDelRecord', $id);

        App::db()->con()->execute(
            'DELETE FROM ' . App::db()->con()->prefix() . Epc::TABLE_NAME . ' ' .
            'WHERE epc_id = ' . $id . ' ' .
            "AND blog_id = '" . App::db()->con()->escapeStr(App::blog()->id()) . "' "
        );

        App::blog()->triggerBlog();
    }

    /**
     * Get next record ID.
     *
     * @return  int     The next record ID
     */
    private static function getNextId(): int
    {
        return (int) App::db()->con()->select(
            'SELECT MAX(epc_id) FROM ' . App::db()->con()->prefix() . Epc::TABLE_NAME . ' '
        )->f(0) + 1;
    }

    /**
     * Open filter Cursor.
     *
     * @return  Cursor  The Cursor
     */
    public static function openCursor(): Cursor
    {
        return App::db()->con()->openCursor(App::db()->con()->prefix() . Epc::TABLE_NAME);
    }

    /**
     * Clean up a Cursor.
     *
     * @param   Cursor  $cur    The Cursor
     */
    private static function getCursor(Cursor $cur): void
    {
        if ($cur->getField('epc_key') == '') {
            throw new Exception(__('No record key'));
        }
        if ($cur->getField('epc_value') == '') {
            throw new Exception(__('No record value'));
        }
        if ($cur->getField('epc_filter') == '') {
            throw new Exception(__('No record filter'));
        }
    }
}
