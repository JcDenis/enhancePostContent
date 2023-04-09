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

use Exception;

class ManageVars
{
    /**
     * @var ManageVars self instance
     */
    private static $container;

    public readonly EpcFilter $filter;
    public readonly string $action;
    public readonly string $part;
    public readonly array $combo;

    protected function __construct()
    {
        $_filters = Epc::getFilters();

        $filters_id = $filters_combo = [];
        foreach ($_filters as $id => $filter) {
            $filters_id[$id]              = $filter->name;
            $filters_combo[$filter->name] = $id;
        }

        $part = $_REQUEST['part'] ?? key($filters_id);

        if (!isset($filters_id[$part])) {
            throw new Exception(__('no filters'));
        }

        $this->action = $_POST['action'] ?? '';
        $this->part   = $part;
        $this->filter = $_filters[$part];
        $this->combo  = $filters_combo;
    }

    public static function init(): ManageVars
    {
        if (!(self::$container instanceof self)) {
            self::$container = new self();
        }

        return self::$container;
    }
}
