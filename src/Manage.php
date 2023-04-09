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

use dcAdminFilters;
use adminGenericFilterV2;
use dcCore;
use dcNsProcess;
use dcPage;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;

use form;

class Manage extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN')
            && My::phpCompliant()
            && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id);

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        $current = ManageVars::init();

        if (dcCore::app()->error->flag()) {
            return true;
        }

        if (!empty($current->action)) {
            # --BEHAVIOR-- enhancePostContentAdminSave
            dcCore::app()->callBehavior('enhancePostContentAdminSave');
        }

        try {
            # Update filter settings
            if ($current->action == 'savefiltersetting') {
                # Parse filters options
                $f = [
                    'nocase'    => !empty($_POST['filter_nocase']),
                    'plural'    => !empty($_POST['filter_plural']),
                    'limit'     => abs((int) $_POST['filter_limit']),
                    'style'     => (array) $_POST['filter_style'],
                    'notag'     => (string) $_POST['filter_notag'],
                    'tplValues' => (array) $_POST['filter_tplValues'],
                    'pubPages'  => (array) $_POST['filter_pubPages'],
                ];

                dcCore::app()->blog->settings->get(My::id())->put($current->filter->id(), json_encode($f));

                dcCore::app()->blog->triggerBlog();

                dcPage::addSuccessNotice(
                    __('Filter successfully updated.')
                );

                dcCore::app()->adminurl->redirect(
                    'admin.plugin.' . My::id(),
                    ['part' => $current->part],
                    '#settings'
                );
            }

            # Add new filter record
            if ($current->action == 'savenewrecord'
                && !empty($_POST['new_key'])
                && !empty($_POST['new_value'])
            ) {
                $cur = EpcRecord::openCursor();
                $cur->setField('epc_filter', $current->filter->id());
                $cur->setField('epc_key', Html::escapeHTML($_POST['new_key']));
                $cur->setField('epc_value', Html::escapeHTML($_POST['new_value']));

                if (EpcRecord::isRecord($cur->getField('epc_filter'), $cur->getField('epc_key'))) {
                    dcPage::addErrorNotice(__('Key already exists for this filter'));
                } else {
                    EpcRecord::addRecord($cur);

                    dcCore::app()->blog->triggerBlog();

                    dcPage::addSuccessNotice(
                        __('Filter successfully updated.')
                    );
                }
                dcCore::app()->adminurl->redirect(
                    'admin.plugin.' . My::id(),
                    ['part' => $current->part],
                    '#record'
                );
            }

            # Update filter records
            if ($current->action == 'deleterecords' 
                && $current->filter->has_list
                && !empty($_POST['epc_id']) 
                && is_array($_POST['epc_id'])
            ) {
                foreach ($_POST['epc_id'] as $id) {
                    EpcRecord::delRecord((int) $id);
                }

                dcCore::app()->blog->triggerBlog();

                dcPage::addSuccessNotice(
                    __('Filter successfully updated.')
                );

                if (!empty($_REQUEST['redir'])) {
                    Http::redirect($_REQUEST['redir']);
                } else {
                    dcCore::app()->adminurl->redirect(
                        'admin.plugin.' . My::id(),
                        ['part' => $current->part],
                        '#record'
                    );
                }
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!static::$init) {
            return;
        }

        $current = ManageVars::init();

        # -- Prepare page --
        $header = '';
        if ($current->filter->has_list) {
            $sorts = new adminGenericFilterV2('epc');
            $sorts->add(dcAdminFilters::getPageFilter());
            $sorts->add('part', $current->part);

            $params               = $sorts->params();
            $params['epc_filter'] = $current->filter->id();

            try {
                $list    = EpcRecord::getRecords($params);
                $counter = EpcRecord::getRecords($params, true);
                $pager   = new BackendList($list, (int) $counter->f(0));
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }

            $header = $sorts->js(dcCore::app()->adminurl->get('admin.plugin.' . My::id(), ['part' => $current->part], '&') . '#record');
        }

        # Page headers
        dcPage::openModule(
            My::name(),
            dcPage::jsPageTabs() .
            dcPage::jsModuleLoad(My::id() . '/js/backend.js') .
            $header .

            # --BEHAVIOR-- enhancePostContentAdminHeader
            dcCore::app()->callBehavior('enhancePostContentAdminHeader')
        );

        # Page title
        echo 
        dcPage::breadcrumb([
            __('Plugins')          => '',
            My::name()             => '',
            $current->filter->name => '',
        ]) .
        dcPage::notices() .

        # Filters select menu list
        '<form method="get" action="' . dcCore::app()->adminurl->get('admin.plugin.' . My::id()) . '" id="filters_menu">' .
        '<p class="anchor-nav"><label for="part" class="classic">' . __('Select filter:') . ' </label>' .
        form::combo('part', $current->combo, $current->part) . ' ' .
        '<input type="submit" value="' . __('Ok') . '" />' .
        form::hidden('p', My::id()) . '</p>' .
        '</form>';

        # Filter title and description
        echo '
        <h3>' . $current->filter->name . '</h3>
        <p>' . $current->filter->help . '</p>';

        # Filter settings
        echo '
        <div class="multi-part" id="setting" title="' . __('Settings') . '">
        <form method="post" action="' . dcCore::app()->adminurl->get('admin.plugin.' . My::id()) . '#setting">

        <div class="two-boxes odd">
        <h4>' . __('Pages to be filtered') . '</h4>';

        foreach (Epc::blogAllowedPubPages() as $k => $v) {
            echo '
            <p><label for="filter_pubPages' . $v . '">' .
            form::checkbox(
                ['filter_pubPages[]', 'filter_pubPages' . $v],
                $v,
                in_array($v, $current->filter->pubPages)
            ) .
            __($k) . '</label></p>';
        }

        echo '
        </div><div class="two-boxes even">
        <h4>' . __('Filtering') . '</h4>

        <p><label for="filter_nocase">' .
        form::checkbox('filter_nocase', '1', $current->filter->nocase) .
        __('Case insensitive') . '</label></p>

        <p><label for="filter_plural">' .
        form::checkbox('filter_plural', '1', $current->filter->plural) .
        __('Also use the plural') . '</label></p>

        <p><label for="filter_limit">' .
        __('Limit the number of replacement to:') . '</label>' .
        form::number('filter_limit', ['min' => 0, 'max' => 99, 'default' => (int) $current->filter->limit]) . '
        </p>
        <p class="form-note">' . __('Leave it blank or set it to 0 for no limit') . '</p>

        </div><div class="two-boxes odd">
        <h4>' . __('Contents to be filtered') . '</h4>';

        foreach (Epc::blogAllowedTplValues() as $k => $v) {
            echo '
            <p><label for="filter_tplValues' . $v . '">' .
            form::checkbox(
                ['filter_tplValues[]', 'filter_tplValues' . $v],
                $v,
                in_array($v, $current->filter->tplValues)
            ) .
            __($k) . '</label></p>';
        }

        echo '
        </div><div class="two-boxes even">
        <h4>' . __('Style') . '</h4>';

        foreach ($current->filter->class as $k => $v) {
            echo '
            <p><label for="filter_style' . $k . '">' .
            sprintf(__('Class "%s":'), $v) . '</label>' .
            form::field(
                ['filter_style[]', 'filter_style' . $k],
                60,
                255,
                Html::escapeHTML($current->filter->style[$k])
            ) .
            '</p>';
        }

        echo '
        <p class="form-note">' . sprintf(__('The inserted HTML tag looks like: %s'), Html::escapeHTML(str_replace('%s', '...', $current->filter->replace))) . '</p>

        <p><label for="filter_notag">' . __('Ignore HTML tags:') . '</label>' .
        form::field('filter_notag', 60, 255, Html::escapeHTML($current->filter->notag)) . '
        </p>
        <p class="form-note">' . __('This is the list of HTML tags where content will be ignored.') . ' ' .
        ('' != $current->filter->htmltag ? '' : sprintf(__('Tag "%s" always be ignored.'), $current->filter->htmltag)) . '</p>
        </div>
        <div class="clear">
        <p>' .
        dcCore::app()->formNonce() .
        form::hidden(['action'], 'savefiltersetting') .
        form::hidden(['part'], $current->part) . '
        <input type="submit" name="save" value="' . __('Save') . '" />
        </p>
        </div>

        </form>
        </div>';

        # Filter records list
        if ($current->filter->has_list && isset($sorts) && isset($pager)) {
            $pager_url = dcCore::app()->adminurl->get('admin.plugin.' . My::id(), array_diff_key($sorts->values(true), ['page' => ''])) . '&page=%s#record';

            echo '
            <div class="multi-part" id="record" title="' . __('Records') . '">';

            $sorts->display(['admin.plugin.' . My::id(), '#record'], form::hidden('p', My::id()) . form::hidden('part', $current->part));

            $pager->display(
                $sorts,
                $pager_url,
                '<form action="' . dcCore::app()->adminurl->get('admin.plugin.' . My::id()) . '#record" method="post" id="form-records">' .
                '%s' .

                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .

                '<p class="col right">' .
                form::hidden('action', 'deleterecords') .
                '<input id="del-action" type="submit" name="save" value="' . __('Delete selected records') . '" /></p>' .
                dcCore::app()->adminurl->getHiddenFormFields('admin.plugin.' . My::id(), array_merge(['p' => My::id()], $sorts->values(true))) .
                form::hidden('redir', dcCore::app()->adminurl->get('admin.plugin.' . My::id(), $sorts->values(true))) .
                dcCore::app()->formNonce() .
                '</div>' .
                '</form>'
            );

            echo '</div>';

            # New record
            echo '
            <div class="multi-part" id="newrecord" title="' . __('New record') . '">
            <form action="' . dcCore::app()->adminurl->get('admin.plugin.' . My::id()) . '#record" method="post" id="form-create">' .

            '<p><label for="new_key">' . __('Key:') . '</label>' .
            form::field('new_key', 60, 255, ['extra_html' => 'required']) .
            '</p>' .

            '<p><label for="new_value">' . __('Value:') . '</label>' .
            form::field('new_value', 60, 255, ['extra_html' => 'required']) .
            '</p>

            <p class="clear">' .
            form::hidden(['action'], 'savenewrecord') .
            form::hidden(['part'], $current->part) .
            dcCore::app()->formNonce() . '
            <input id="new-action" type="submit" name="save" value="' . __('Save') . '" />
            </p>
            </form>
            </div>';
        }

        # --BEHAVIOR-- enhancePostContentAdminPage
        dcCore::app()->callBehavior('enhancePostContentAdminPage');

        dcPage::helpBlock('enhancePostContent');
        dcPage::closeModule();
    }
}
