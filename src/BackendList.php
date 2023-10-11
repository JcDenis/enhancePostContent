<?php

declare(strict_types=1);

namespace Dotclear\Plugin\enhancePostContent;

use Dotclear\Core\Backend\Filter\Filters;
use Dotclear\Core\Backend\Listing\{
    Listing,
    Pager
};
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Html;

/**
 * @brief   enhancePostContent filters list class.
 * @ingroup enhancePostContent
 *
 * @author      Jean-Christian Denis
 * @copyright   Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class BackendList extends Listing
{
    /**
     * Display list.
     *
     * @param   Filters     $filter     The filter
     * @param   string      $url        The pager URL
     * @param   string      $block      The enclose bloc
     */
    public function display(Filters $filter, string $url, string $block): void
    {
        if ($this->rs->isEmpty()) {
            echo '<p><strong>' . ($filter->show() ? __('No record matches the filter') : __('No record')) . '</strong></p>';

            return;
        }

        $pager           = new Pager($filter->value('page'), (int) $this->rs_count, $filter->value('nb'), 10);
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
            $this->line(isset($epc_id[$this->rs->f('epc_id')]));
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
            'check' => '<td class="nowrap">' . (new Checkbox(['epc_id[]'], $checked))->value($this->rs->f('epc_id'))->render() . '</td>',
            'key'   => '<td class="nowrap">' . Html::escapeHTML($this->rs->f('epc_key')) . '</td>',
            'value' => '<td class="maximal">' . Html::escapeHTML($this->rs->f('epc_value')) . '</td>',
            'date'  => '<td class="nowrap count">' . Date::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->f('epc_upddt')) . '</td>',
        ];

        echo
        '<tr class="line" id="p' . $this->rs->f('epc_id') . '">' .
        implode($cols) .
        '</tr>';
    }
}
