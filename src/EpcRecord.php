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
use Dotclear\Database\{
    Cursor,
    MetaRecord
};
use Exception;

/**
 * Filter records.
 */
class EpcRecord
{
    /**
     * Get records.
     *
     * @param   array   $params         The query params
     * @param   bool    $count_only     Count only
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

        $strReq .= 'FROM ' . dcCore::app()->prefix . My::TABLE_NAME . ' E ';

        if (!empty($params['from'])) {
            $strReq .= $params['from'] . ' ';
        }

        $strReq .= "WHERE E.blog_id = '" . dcCore::app()->con->escapeStr((string) dcCore::app()->blog?->id) . "' ";

        if (isset($params['epc_type'])) {
            if (is_array($params['epc_type']) && !empty($params['epc_type'])) {
                $strReq .= 'AND E.epc_type ' . dcCore::app()->con->in($params['epc_type']);
            } elseif ($params['epc_type'] != '') {
                $strReq .= "AND E.epc_type = '" . dcCore::app()->con->escapeStr((string) $params['epc_type']) . "' ";
            }
        } else {
            $strReq .= "AND E.epc_type = 'epc' ";
        }

        if (isset($params['epc_filter'])) {
            if (is_array($params['epc_filter']) && !empty($params['epc_filter'])) {
                $strReq .= 'AND E.epc_filter ' . dcCore::app()->con->in($params['epc_filter']);
            } elseif ($params['epc_filter'] != '') {
                $strReq .= "AND E.epc_filter = '" . dcCore::app()->con->escapeStr((string) $params['epc_filter']) . "' ";
            }
        }

        if (!empty($params['epc_id'])) {
            if (is_array($params['epc_id'])) {
                array_walk($params['epc_id'], function (&$v, $k) { if ($v !== null) { $v = (int) $v; }});
            } else {
                $params['epc_id'] = [(int) $params['epc_id']];
            }
            $strReq .= 'AND E.epc_id ' . dcCore::app()->con->in($params['epc_id']);
        } elseif (isset($params['not_id']) && is_numeric($params['not_id'])) {
            $strReq .= "AND NOT E.epc_id = '" . $params['not_id'] . "' ";
        }

        if (isset($params['epc_key'])) {
            if (is_array($params['epc_key']) && !empty($params['epc_key'])) {
                $strReq .= 'AND E.epc_key ' . dcCore::app()->con->in($params['epc_key']);
            } elseif ($params['epc_key'] != '') {
                $strReq .= "AND E.epc_key = '" . dcCore::app()->con->escapeStr((string) $params['epc_key']) . "' ";
            }
        }

        if (!empty($params['sql'])) {
            $strReq .= $params['sql'] . ' ';
        }

        if (!$count_only) {
            if (!empty($params['order'])) {
                $strReq .= 'ORDER BY ' . dcCore::app()->con->escapeStr((string) $params['order']) . ' ';
            } else {
                $strReq .= 'ORDER BY E.epc_key ASC ';
            }
        }

        if (!$count_only && !empty($params['limit'])) {
            $strReq .= dcCore::app()->con->limit($params['limit']);
        }

        return new MetaRecord(dcCore::app()->con->select($strReq));
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
        dcCore::app()->con->writeLock(dcCore::app()->prefix . My::TABLE_NAME);

        try {
            $cur->setField('epc_id', self::getNextId());
            $cur->setField('blog_id', (string) dcCore::app()->blog?->id);
            $cur->setField('epc_upddt', date('Y-m-d H:i:s'));

            self::getCursor($cur);

            $cur->insert();
            dcCore::app()->con->unlock();
        } catch (Exception $e) {
            dcCore::app()->con->unlock();

            throw $e;
        }
        dcCore::app()->blog?->triggerBlog();

        # --BEHAVIOR-- enhancePostContentAfterAddRecord : Cursor
        dcCore::app()->callBehavior('enhancePostContentAfterAddRecord', $cur);

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

        $cur->update('WHERE epc_id = ' . $id . " AND blog_id = '" . dcCore::app()->con->escapeStr((string) dcCore::app()->blog?->id) . "' ");
        dcCore::app()->blog?->triggerBlog();

        # --BEHAVIOR-- enhancePostContentAfterUpdRecord : Cursor, int
        dcCore::app()->callBehavior('enhancePostContentAfterUpdRecord', $cur, $id);
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
        dcCore::app()->callBehavior('enhancePostContentbeforeDelRecord', $id);

        dcCore::app()->con->execute(
            'DELETE FROM ' . dcCore::app()->prefix . My::TABLE_NAME . ' ' .
            'WHERE epc_id = ' . $id . ' ' .
            "AND blog_id = '" . dcCore::app()->con->escapeStr((string) dcCore::app()->blog?->id) . "' "
        );

        dcCore::app()->blog?->triggerBlog();
    }

    /**
     * Get next record ID.
     *
     * @return  int     The next record ID
     */
    private static function getNextId(): int
    {
        return (int) dcCore::app()->con->select(
            'SELECT MAX(epc_id) FROM ' . dcCore::app()->prefix . My::TABLE_NAME . ' '
        )->f(0) + 1;
    }

    /**
     * Open filter Cursor.
     *
     * @return  Cursor  The Cursor
     */
    public static function openCursor(): Cursor
    {
        return dcCore::app()->con->openCursor(dcCore::app()->prefix . My::TABLE_NAME);
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
