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
                $content_req .= implode(', ', array_filter($params['columns'], is_string(...))) . ', ';
            }
            $strReq = 'SELECT E.epc_id, E.blog_id, E.epc_type, E.epc_upddt, ' .
            $content_req .
            'E.epc_filter, E.epc_key, E.epc_value ';
        }

        $strReq .= 'FROM ' . App::db()->con()->prefix() . Epc::TABLE_NAME . ' E ';

        if (!empty($params['from'])) {
            if (!is_array($params['from'])) {
                $params['from'] = [$params['from']];
            }
            $params['from'] = array_filter($params['from'], is_string(...));
        }
        $strReq .= "WHERE E.blog_id = '" . App::db()->con()->escapeStr(App::blog()->id()) . "' ";

        if (isset($params['epc_type'])) {
            if (!is_array($params['epc_type'])) {
                $params['epc_type'] = [$params['epc_type']];
            }
            $strReq .= 'AND E.epc_type ' . App::db()->con()->in(array_filter($params['epc_type'],is_string(...)));
        } else {
            $strReq .= "AND E.epc_type = 'epc' ";
        }

        if (isset($params['epc_filter'])) {
            if (!is_array($params['epc_filter'])) {
                $params['epc_filter'] = [$params['epc_filter']];
            }
            $strReq .= 'AND E.epc_filter ' . App::db()->con()->in(array_filter($params['epc_filter'],is_string(...)));
        }

        if (!empty($params['epc_id'])) {
            if (!is_array($params['epc_id'])) {
                $params['epc_id'] = [$params['epc_id']];
            }
            array_walk($params['epc_id'], function (&$v) { $v = is_numeric($v) ? (int) $v : 0; });

            $strReq .= 'AND E.epc_id ' . App::db()->con()->in($params['epc_id']);
        } elseif (isset($params['not_id']) && is_numeric($params['not_id'])) {
            $strReq .= "AND NOT E.epc_id = '" . $params['not_id'] . "' ";
        }

        if (isset($params['epc_key'])) {
            if (!is_array($params['epc_key'])) {
                $params['epc_key'] = [$params['epc_key']];
            }
            $strReq .= 'AND E.epc_key ' . App::db()->con()->in(array_filter($params['epc_key'],is_string(...)));
        }

        if (!empty($params['sql']) && is_string($params['sql'])) {
            $strReq .= $params['sql'] . ' ';
        }

        if (!$count_only) {
            if (!empty($params['order']) && is_string($params['order'])) {
                $strReq .= 'ORDER BY ' . App::db()->con()->escapeStr($params['order']) . ' ';
            } else {
                $strReq .= 'ORDER BY E.epc_key ASC ';
            }
        }

        if (!$count_only && !empty($params['limit'])) {
            $values = is_array($params['limit']) ? array_values($params['limit']) : [$params['limit']];
            // Make $values an array of integer values
            $values = array_map(fn (mixed $v): int => is_numeric($v) ? (int) $v : 0, $values);

            /**
             * @var array{0: int, 1?: int}  $limit
             */
            $limit = [
                $values[0],
            ];
            if (isset($values[1])) {
                $limit[1] = $values[1];
            }

            $strReq .= App::db()->con()->limit($limit);
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

        return is_numeric($cur->getField('epc_id')) ? (int) $cur->getField('epc_id') : 0;
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
        ], true)->cardinal();
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
        $res = App::db()->con()->select(
            'SELECT MAX(epc_id) FROM ' . App::db()->con()->prefix() . Epc::TABLE_NAME . ' '
        )->f(0);

        return is_numeric($res) ? (int) $res + 1 : 1;
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
