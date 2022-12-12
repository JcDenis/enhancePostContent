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

/**
 * @ingroup DC_PLUGIN_PERIODICAL
 * @brief Periodical - admin pager methods.
 * @since 2.6
 */
class adminEpcList extends adminGenericList
{
    public function display($filter, $pager_url, $enclose_block = '')
    {
        if ($this->rs->isEmpty()) {
            if ($filter->show()) {
                echo '<p><strong>' . __('No record matches the filter') . '</strong></p>';
            } else {
                echo '<p><strong>' . __('No record') . '</strong></p>';
            }
        } else {
            $pager           = new dcPager($filter->page, $this->rs_count, $filter->nb, 10);
            $pager->base_url = $pager_url;

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

            $html_block = '<div class="table-outer"><table><caption>' .
                (
                    $filter->show() ?
                    sprintf(__('List of %s records matching the filter.'), $this->rs_count) :
                    sprintf(__('List of %s records.'), $this->rs_count)
                ) . '</caption>' .
                '<tr>' . implode($cols) . '</tr>%s</table>%s</div>';

            if ($enclose_block) {
                $html_block = sprintf($enclose_block, $html_block);
            }
            $blocks = explode('%s', $html_block);

            echo $pager->getLinks() . $blocks[0];

            while ($this->rs->fetch()) {
                echo $this->line(isset($epc_id[$this->rs->epc_id]));
            }

            echo $blocks[1] . $blocks[2] . $pager->getLinks();
        }
    }

    private function line($checked)
    {
        $cols = [
            'check' => '<td class="nowrap">' . form::checkbox(['epc_id[]'], $this->rs->epc_id, ['checked' => $checked]) . '</td>',
            'key'   => '<td class="nowrap">' . html::escapeHTML($this->rs->epc_key) . '</td>',
            'value' => '<td class="maximal">' . html::escapeHTML($this->rs->epc_value) . '</td>',
            'date'  => '<td class="nowrap count">' . dt::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->epc_upddt) . '</td>',
        ];

        return
            '<tr class="line" id="p' . $this->rs->epc_id . '">' .
            implode($cols) .
            '</tr>';
    }
}
