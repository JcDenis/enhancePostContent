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
class epcRecords
{
    public $core;
    public $con;
    public $table;
    public $blog;

    public function __construct($core)
    {
        $this->core  = $core;
        $this->con   = $core->con;
        $this->table = $core->prefix . 'epc';
        $this->blog  = $core->con->escape($core->blog->id);
    }

    public function getRecords($params, $count_only = false)
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

        $strReq .= 'FROM ' . $this->table . ' E ';

        if (!empty($params['from'])) {
            $strReq .= $params['from'] . ' ';
        }

        $strReq .= "WHERE E.blog_id = '" . $this->blog . "' ";

        if (isset($params['epc_type'])) {
            if (is_array($params['epc_type']) && !empty($params['epc_type'])) {
                $strReq .= 'AND E.epc_type ' . $this->con->in($params['epc_type']);
            } elseif ($params['epc_type'] != '') {
                $strReq .= "AND E.epc_type = '" . $this->con->escape($params['epc_type']) . "' ";
            }
        } else {
            $strReq .= "AND E.epc_type = 'epc' ";
        }

        if (isset($params['epc_filter'])) {
            if (is_array($params['epc_filter']) && !empty($params['epc_filter'])) {
                $strReq .= 'AND E.epc_filter ' . $this->con->in($params['epc_filter']);
            } elseif ($params['epc_filter'] != '') {
                $strReq .= "AND E.epc_filter = '" . $this->con->escape($params['epc_filter']) . "' ";
            }
        }

        if (!empty($params['epc_id'])) {
            if (is_array($params['epc_id'])) {
                array_walk($params['epc_id'], function (&$v, $k) { if ($v !== null) { $v = (integer) $v; }});
            } else {
                $params['epc_id'] = [(integer) $params['epc_id']];
            }
            $strReq .= 'AND E.epc_id ' . $this->con->in($params['epc_id']);
        } elseif (isset($params['not_id']) && is_numeric($params['not_id'])) {
            $strReq .= "AND NOT E.epc_id = '" . $params['not_id'] . "' ";
        }

        if (isset($params['epc_key'])) {
            if (is_array($params['epc_key']) && !empty($params['epc_key'])) {
                $strReq .= 'AND E.epc_key ' . $this->con->in($params['epc_key']);
            } elseif ($params['epc_key'] != '') {
                $strReq .= "AND E.epc_key = '" . $this->con->escape($params['epc_key']) . "' ";
            }
        }

        if (!empty($params['sql'])) {
            $strReq .= $params['sql'] . ' ';
        }

        if (!$count_only) {
            if (!empty($params['order'])) {
                $strReq .= 'ORDER BY ' . $this->con->escape($params['order']) . ' ';
            } else {
                $strReq .= 'ORDER BY E.epc_key ASC ';
            }
        }

        if (!$count_only && !empty($params['limit'])) {
            $strReq .= $this->con->limit($params['limit']);
        }

        return $this->con->select($strReq);
    }

    public function addRecord($cur)
    {
        $this->con->writeLock($this->table);

        try {
            $cur->epc_id    = $this->getNextId();
            $cur->blog_id   = $this->blog;
            $cur->epc_upddt = date('Y-m-d H:i:s');

            $this->getCursor($cur);

            $cur->insert();
            $this->con->unlock();
        } catch (Exception $e) {
            $this->con->unlock();

            throw $e;
        }
        $this->trigger();

        # --BEHAVIOR-- enhancePostContentAfterAddRecord
        $this->core->callBehavior('enhancePostContentAfterAddRecord', $cur);

        return $cur->epc_id;
    }

    public function updRecord($id, $cur)
    {
        $id = (integer) $id;

        if (empty($id)) {
            throw new Exception(__('No such record ID'));
        }

        $cur->epc_upddt = date('Y-m-d H:i:s');

        $cur->update('WHERE epc_id = ' . $id . " AND blog_id = '" . $this->blog . "' ");
        $this->trigger();

        # --BEHAVIOR-- enhancePostContentAfterUpdRecord
        $this->core->callBehavior('enhancePostContentAfterUpdRecord', $cur, $id);
    }

    public function isRecord($filter, $key, $not_id = null)
    {
        return 0 < $this->getRecords([
            'epc_filter' => $filter,
            'epc_key'    => $key,
            'not_id'     => $not_id
        ], true)->f(0);
    }

    public function delRecord($id)
    {
        $id = (integer) $id;

        if (empty($id)) {
            throw new Exception(__('No such record ID'));
        }

        # --BEHAVIOR-- enhancePostContentBeforeDelRecord
        $this->core->callBehavior('enhancePostContentbeforeDelRecord', $id);

        $this->con->execute(
            'DELETE FROM ' . $this->table . ' ' .
            'WHERE epc_id = ' . $id . ' ' .
            "AND blog_id = '" . $this->blog . "' "
        );

        $this->trigger();
    }

    private function getNextId()
    {
        return $this->con->select(
            'SELECT MAX(epc_id) FROM ' . $this->table . ' '
        )->f(0) + 1;
    }

    public function openCursor()
    {
        return $this->con->openCursor($this->table);
    }

    private function getCursor($cur, $epc_id = null)
    {
        if ($cur->epc_key == '') {
            throw new Exception(__('No record key'));
        }
        if ($cur->epc_value == '') {
            throw new Exception(__('No record value'));
        }
        if ($cur->epc_filter == '') {
            throw new Exception(__('No record filter'));
        }
        $epc_id = is_int($epc_id) ? $epc_id : $cur->epc_id;
    }

    private function trigger()
    {
        $this->core->blog->triggerBlog();
    }
}
