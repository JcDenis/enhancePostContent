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
if (!defined('DC_RC_PATH')) {
    return null;
}

$core->addBehavior(
    'initWidgets',
    ['enhancePostContentWidget', 'adminContentList']
);

/**
 * @ingroup DC_PLUGIN_ENHANCEPOSTCONTENT
 * @brief Filter posts content - widgets methods.
 * @since 2.6
 */
class enhancePostContentWidget
{
    /**
     * Admin part for widget that show extracted content
     *
     * @param  dcWidgets $w dcWidgets instance
     */
    public static function adminContentList($w)
    {
        global $core;

        $w->create(
            'epclist',
            __('Enhance post content'),
            ['enhancePostContentWidget', 'publicContentList'],
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
        $filters = libEPC::getFilters();
        $types   = [];
        foreach ($filters as $id => $filter) {
            $types[$filter->name] = $id;
        }
        $w->epclist->setting(
            'type',
            __('Type:'),
            'Definition',
            'combo',
            $types
        );
        # Content
        $contents = libEPC::defaultAllowedWidgetValues();
        foreach ($contents as $k => $v) {
            $w->epclist->setting(
                'content' . $v['id'],
                sprintf(__('Enable filter on %s'), __($k)),
                1,
                'check'
            );
        }
        # Case sensitive
        $w->epclist->setting(
            'nocase',
            __('Search case insensitive'),
            0,
            'check'
        );
        # Plural
        $w->epclist->setting(
            'plural',
            __('Search also plural'),
            0,
            'check'
        );
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
     * @param  dcWidget $w dcWidget instance
     */
    public static function publicContentList($w)
    {
        global $core, $_ctx;

        if ($w->offline) {
            return null;
        }

        $core->blog->settings->addNamespace('enhancePostContent');

        # Page
        if (!$core->blog->settings->enhancePostContent->enhancePostContent_active
            || !in_array($_ctx->current_tpl, ['post.html', 'page.html'])
        ) {
            return null;
        }

        # Content
        $content = '';
        foreach (libEPC::defaultAllowedWidgetValues() as $k => $v) {
            $ns = 'content' . $v['id'];
            if ($w->$ns && is_callable($v['cb'])) {
                $content .= call_user_func_array(
                    $v['cb'],
                    [$core, $w]
                );
            }
        }

        if (empty($content)) {
            return null;
        }

        # Filter
        $list    = [];
        $filters = libEPC::getFilters();

        if (isset($filters[$w->type])) {
            $filters[$w->type]->nocase = $w->nocase;
            $filters[$w->type]->plural = $w->plural;
            $filters[$w->type]->widgetList($content, $w, $list);
        }

        if (empty($list)) {
            return null;
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

        if (empty($res)) {
            return null;
        }

        return $w->renderDiv(
            $w->content_only,
            $w->class,
            'id="epc_' . $w->type . '"',
            ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') .
            ($w->text ? '<p>' . html::escapeHTML($w->text) . '</p>' : '') .
            '<ul>' . $res . '</ul>'
        );
    }
}
