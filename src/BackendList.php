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

use adminGenericFilterV2;
use adminGenericListV2;
use dcPager;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Html;

/**
 * Backend filters values list.
 */
class BackendList extends adminGenericListV2
{
    /**
     * Display list.
     *
     * @param   adminGenericFilterV2    $filter     The filter
     * @param   string                  $url        The pager URL
     * @param   string                  $block      The enclose bloc
     */
    public function display(adminGenericFilterV2 $filter, string $url, string $block): void
    {
        if ($this->rs->isEmpty()) {
            echo '<p><strong>' . ($filter->show() ? __('No record matches the filter') : __('No record')) . '</strong></p>';

            return;
        }

        $pager           = new dcPager($filter->value('page'), $this->rs_count, $filter->value('nb'), 10);
        $pager->base_url = $url;

        $epc_id = [];
        if (isset($_REQUEST['epc_id'])) {
            foreach ($_REQUEST['epc_id'] as $v) {
                $epc_id[(int) $v] = true;
            }
        }

        $cols = [
            'key'   => '<th colspan="2" class="first">' . __('Key') . '</th>',
            'value' => '<th scope="col">' . __('Value') . '</th>',
            'date'  => '<th scope="col">' . __('Date') . '</th>',
        ];

        $content = '<div class="table-outer"><table><caption>' . (
            $filter->show() ?
            sprintf(__('List of %s records matching the filter.'), $this->rs_count) :
            sprintf(__('List of %s records.'), $this->rs_count)
        ) . '</caption>' .
        '<tr>' . implode($cols) . '</tr>%s</table>%s</div>';

        $blocks = explode('%s', sprintf($block, $content));

        echo $pager->getLinks() . $blocks[0];

        while ($this->rs->fetch()) {
            $this->line(isset($epc_id[$this->rs->epc_id]));
        }

        echo $blocks[1] . $blocks[2] . $pager->getLinks();
    }

    /**
     * Dispay a list line.
     *
     * @param   bool    $checked    Checkbox checked
     */
    private function line(bool $checked): void
    {
        $cols = [
            'check' => '<td class="nowrap">' . (new Checkbox(['epc_id[]'], $checked))->value($this->rs->epc_id)->render() . '</td>',
            'key'   => '<td class="nowrap">' . Html::escapeHTML($this->rs->epc_key) . '</td>',
            'value' => '<td class="maximal">' . Html::escapeHTML($this->rs->epc_value) . '</td>',
            'date'  => '<td class="nowrap count">' . Date::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->epc_upddt) . '</td>',
        ];

        echo
        '<tr class="line" id="p' . $this->rs->epc_id . '">' .
        implode($cols) .
        '</tr>';
    }
}
