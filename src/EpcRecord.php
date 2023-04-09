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

use cursor;
use dcCore;
use dcRecord;

class EpcRecord
{
    public static function getRecords(array $params, bool $count_only = false): dcRecord
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

        $strReq .= "WHERE E.blog_id = '" . dcCore::app()->con->escapeStr((string) dcCore::app()->blog->id) . "' ";

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

        return new dcRecord(dcCore::app()->con->select($strReq));
    }

    public static function addRecord(cursor $cur): int
    {
        dcCore::app()->con->writeLock(dcCore::app()->prefix . My::TABLE_NAME);

        try {
            $cur->setField('epc_id', self::getNextId());
            $cur->setField('blog_id', dcCore::app()->blog->id);
            $cur->setField('epc_upddt', date('Y-m-d H:i:s'));

            self::getCursor($cur);

            $cur->insert();
            dcCore::app()->con->unlock();
        } catch (Exception $e) {
            dcCore::app()->con->unlock();

            throw $e;
        }
        self::trigger();

        # --BEHAVIOR-- enhancePostContentAfterAddRecord
        dcCore::app()->callBehavior('enhancePostContentAfterAddRecord', $cur);

        return (int) $cur->getField('epc_id');
    }

    public static function updRecord(int $id, cursor $cur): void
    {
        if (empty($id)) {
            throw new Exception(__('No such record ID'));
        }

        $cur->setField('epc_upddt', date('Y-m-d H:i:s'));

        $cur->update('WHERE epc_id = ' . $id . " AND blog_id = '" . dcCore::app()->con->escapeStr((string) dcCore::app()->blog->id) . "' ");
        self::trigger();

        # --BEHAVIOR-- enhancePostContentAfterUpdRecord
        dcCore::app()->callBehavior('enhancePostContentAfterUpdRecord', $cur, $id);
    }

    public static function isRecord(string $filter, string $key, int $not_id = null): bool
    {
        return 0 < self::getRecords([
            'epc_filter' => $filter,
            'epc_key'    => $key,
            'not_id'     => $not_id,
        ], true)->f(0);
    }

    public static function delRecord(int $id): void
    {
        if (empty($id)) {
            throw new Exception(__('No such record ID'));
        }

        # --BEHAVIOR-- enhancePostContentBeforeDelRecord
        dcCore::app()->callBehavior('enhancePostContentbeforeDelRecord', $id);

        dcCore::app()->con->execute(
            'DELETE FROM ' . dcCore::app()->prefix . My::TABLE_NAME . ' ' .
            'WHERE epc_id = ' . $id . ' ' .
            "AND blog_id = '" . dcCore::app()->con->escapeStr((string) dcCore::app()->blog->id) . "' "
        );

        self::trigger();
    }

    private static function getNextId(): int
    {
        return (int) dcCore::app()->con->select(
            'SELECT MAX(epc_id) FROM ' . dcCore::app()->prefix . My::TABLE_NAME . ' '
        )->f(0) + 1;
    }

    public static function openCursor(): cursor
    {
        return dcCore::app()->con->openCursor(dcCore::app()->prefix . My::TABLE_NAME);
    }

    private static function getCursor(cursor $cur): void
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

    private static function trigger(): void
    {
        dcCore::app()->blog->triggerBlog();
    }
}
