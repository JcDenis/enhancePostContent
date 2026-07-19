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
        $_ = $w->get('epclist');
        if (!($_ instanceof WidgetsElement)) {
            return;
        }
        # Title
        $_->addTitle(__('In this article'));
        # Text
        $_->setting(
            'text',
            __('Description:'),
            '',
            'text'
        );
        # Type
        $_->setting(
            'type',
            __('Type:'),
            'Definition',
            'combo',
            Epc::getFilters()->nid(true)
        );
        # Content
        foreach (Epc::widgetAllowedTemplateValue() as $name => $info) {
            if (is_string($info['id'])) {
                $_->setting(
                    'content' . $info['id'],
                    sprintf(__('Enable filter on %s'), __($name)),
                    1,
                    'check'
                );
            }
        }
        # Show count
        $_->setting(
            'show_total',
            __('Show the number of appearance'),
            1,
            'check'
        );
        # widget options
        $_
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
        if ($w->get('offline')) {
            return '';
        }

        # Page
        if (!My::settings()->getBool('active', false)
            || !in_array(App::frontend()->context()->__get('current_tpl'), ['post.html', 'page.html'])
        ) {
            return '';
        }

        # Content
        $content = '';
        foreach (Epc::widgetAllowedTemplateValue() as $info) {
            if (is_string($info['id'])) {
                $ns = 'content' . $info['id'];
                if ($w->get($ns) && is_callable($info['cb'])) {
                    $tmp = call_user_func(
                        $info['cb'],
                        $w
                    );
                    if (is_string($tmp)) {
                        $content .= $tmp;
                    }
                }
            }
        }

        if (empty($content) || !is_string($w->get('type'))) {
            return '';
        }

        # Filter
        $list   = new ArrayObject();
        $filter = Epc::getFilters()->get($w->get('type'));

        if (!is_null($filter)) {
            $filter->widgetList($content, $w, $list);
        }

        if (!count($list)) {
            return '';
        }

        # Parse result
        $res = '';
        foreach ($list as $line) {
            if (!is_numeric($line['total']) || (int) $line['total'] === 0 || !is_string($line['replacement'])) {
                continue;
            }

            $res .= '<li>' . $line['replacement'] .
            ($w->get('show_total') ? ' (' . $line['total'] . ')' : '') .
            '</li>';
        }

        return empty($res) ? '' : $w->renderDiv(
            (bool) $w->get('content_only'),
            is_string($w->get('class')) ? $w->get('class') : '',
            'id="epc_' . (is_string($w->get('type')) ? $w->get('type') : '') . '"',
            (is_string($w->get('title')) && !empty($w->get('title')) ? $w->renderTitle(Html::escapeHTML($w->get('title'))) : '') .
            (is_string($w->get('text')) && !empty($w->get('text')) ? '<p>' . Html::escapeHTML($w->get('text')) . '</p>' : '') .
            '<ul>' . $res . '</ul>'
        );
    }
}
