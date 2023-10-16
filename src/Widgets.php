<?php

declare(strict_types=1);

namespace Dotclear\Plugin\enhancePostContent;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\widgets\WidgetsStack;
use Dotclear\Plugin\widgets\WidgetsElement;

/**
 * @brief       enhancePostContent widgets class.
 * @ingroup     enhancePostContent
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
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
            self::parseWidget(...),
            null,
            __('List filtered contents.')
        );
        # Title
        $w->__get('epclist')->addTitle(__('In this article'));
        # Text
        $w->__get('epclist')->setting(
            'text',
            __('Description:'),
            '',
            'text'
        );
        # Type
        $w->__get('epclist')->setting(
            'type',
            __('Type:'),
            'Definition',
            'combo',
            Epc::getFilters()->nid(true)
        );
        # Content
        foreach (Epc::widgetAllowedTemplateValue() as $name => $info) {
            $w->__get('epclist')->setting(
                'content' . $info['id'],
                sprintf(__('Enable filter on %s'), __($name)),
                1,
                'check'
            );
        }
        # Show count
        $w->__get('epclist')->setting(
            'show_total',
            __('Show the number of appearance'),
            1,
            'check'
        );
        # widget options
        $w->__get('epclist')
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
        if ($w->__get('offline')) {
            return '';
        }

        # Page
        if (!My::settings()->get('active')
            || !in_array(App::frontend()->context()->__get('current_tpl'), ['post.html', 'page.html'])
        ) {
            return '';
        }

        # Content
        $content = '';
        foreach (Epc::widgetAllowedTemplateValue() as $info) {
            $ns = 'content' . $info['id'];
            if ($w->__get($ns) && is_callable($info['cb'])) {
                $content .= call_user_func(
                    $info['cb'],
                    $w
                );
            }
        }

        if (empty($content)) {
            return '';
        }

        # Filter
        $list   = new ArrayObject();
        $filter = Epc::getFilters()->get($w->__get('type'));

        if (!is_null($filter)) {
            $filter->widgetList($content, $w, $list);
        }

        if (!count($list)) {
            return '';
        }

        # Parse result
        $res = '';
        foreach ($list as $line) {
            if ((int) $line['total'] == 0) {
                continue;
            }

            $res .= '<li>' . $line['replacement'] .
            ($w->__get('show_total') ? ' (' . $line['total'] . ')' : '') .
            '</li>';
        }

        return empty($res) ? '' : $w->renderDiv(
            (bool) $w->__get('content_only'),
            $w->__get('class'),
            'id="epc_' . $w->__get('type') . '"',
            ($w->__get('title') ? $w->renderTitle(Html::escapeHTML($w->__get('title'))) : '') .
            ($w->__get('text') ? '<p>' . Html::escapeHTML($w->__get('text')) . '</p>' : '') .
            '<ul>' . $res . '</ul>'
        );
    }
}
