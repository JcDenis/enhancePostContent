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

use ArrayObject;
use dcCore;
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\widgets\WidgetsStack;
use Dotclear\Plugin\widgets\WidgetsElement;

/**
 * @ingroup DC_PLUGIN_ENHANCEPOSTCONTENT
 * @brief Filter posts content - widgets methods.
 * @since 2.6
 */
class Widgets
{
    /**
     * Admin part for widget that show extracted content
     *
     * @param  WidgetsStack $w WidgetsStack instance
     */
    public static function initWidgets(WidgetsStack $w): void
    {
        $w->create(
            'epclist',
            My::name(),
            [self::class, 'parseWidget'],
            null,
            __('List filtered contents.')
        );
        # Title
        $w->epclist->addTitle(__('In this article'));
        # Text
        $w->epclist->setting(
            'text',
            __('Description:'),
            '',
            'text'
        );
        # Type
        $types = [];
        foreach (Epc::getFilters() as $id => $filter) {
            if ($filter->widget != '') {
                $types[$filter->name] = $id;
            }
        }
        $w->epclist->setting(
            'type',
            __('Type:'),
            'Definition',
            'combo',
            $types
        );
        # Content
        foreach (Epc::defaultAllowedWidgetValues() as $k => $v) {
            $w->epclist->setting(
                'content' . $v['id'],
                sprintf(__('Enable filter on %s'), __($k)),
                1,
                'check'
            );
        }
        # Show count
        $w->epclist->setting(
            'show_total',
            __('Show the number of appearance'),
            1,
            'check'
        );
        # widget options
        $w->epclist
            ->addContentOnly()
            ->addClass()
            ->addOffline();
    }

    /**
     * Public part for widget that show extracted content
     *
     * @param  WidgetsElement $w WidgetsElement instance
     */
    public static function parseWidget(WidgetsElement $w): string
    {
        if ($w->offline) {
            return '';
        }

        # Page
        if (!dcCore::app()->blog?->settings->get(My::id())->get('active')
            || !in_array(dcCore::app()->ctx?->__get('current_tpl'), ['post.html', 'page.html'])
        ) {
            return '';
        }

        # Content
        $content = '';
        foreach (Epc::defaultAllowedWidgetValues() as $k => $v) {
            $ns = 'content' . $v['id'];
            if ($w->$ns && is_callable($v['cb'])) {
                $content .= call_user_func(
                    $v['cb'],
                    $w
                );
            }
        }

        if (empty($content)) {
            return '';
        }

        # Filter
        $list    = new ArrayObject();
        $filters = Epc::getFilters();

        if (isset($filters[$w->type])) {
            $filters[$w->type]->widgetList($content, $w, $list);
        }

        if (!count($list)) {
            return '';
        }

        # Parse result
        $res = '';
        foreach ($list as $line) {
            if (empty($line['matches'][0]['match'])) {
                continue;
            }

            $res .= '<li>' . $line['matches'][0]['match'] .
            ($w->show_total ? ' (' . $line['total'] . ')' : '') .
            '</li>';
        }

        return empty($res) ? '' : $w->renderDiv(
            (bool) $w->content_only,
            $w->class,
            'id="epc_' . $w->type . '"',
            ($w->title ? $w->renderTitle(Html::escapeHTML($w->title)) : '') .
            ($w->text ? '<p>' . Html::escapeHTML($w->text) . '</p>' : '') .
            '<ul>' . $res . '</ul>'
        );
    }
}
