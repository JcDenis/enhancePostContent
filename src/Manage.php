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
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Form,
    Hidden,
    Input,
    label,
    Note,
    Number,
    Para,
    Select,
    Submit,
    Text
};
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;

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
        dcPage::notices();

        # Filters select menu list
        echo
        (new Form('filters_menu'))->method('get')->action(dcCore::app()->adminurl->get('admin.plugin.' . My::id()))->fields([
            (new Para())->class('anchor-nav')->items([
                (new Select('part'))->items($current->combo)->default($current->part),
                (new Submit(['do']))->value(__('Ok')),
                (new Hidden(['p'], My::id())),
            ]),
        ])->render();

        # Filter title and description
        echo 
        '<h3>' . $current->filter->name . '</h3>' .
        '<p>' . $current->filter->help . '</p>';

        # Filter settings
        $form_pages = [(new Text('h4', __('Pages to be filtered')))];
        foreach (Epc::blogAllowedPubPages() as $k => $v) {
            $form_pages[] = (new Para())->items([
                (new Checkbox(['filter_pubPages[]', 'filter_pubPages' . $v], in_array($v, $current->filter->pubPages)))->value(1),
                (new Label(__($k), Label::OUTSIDE_LABEL_AFTER))->for('filter_pubPages' . $v)->class('classic'),
            ]);
        }

        $form_values = [(new Text('h4', __('Contents to be filtered')))];
        foreach (Epc::blogAllowedTplValues() as $k => $v) {
            $form_values[] = (new Para())->items([
                (new Checkbox(['filter_tplValues[]', 'filter_tplValues' . $v], in_array($v, $current->filter->tplValues)))->value(1),
                (new Label(__($k), Label::OUTSIDE_LABEL_AFTER))->for('filter_tplValues' . $v)->class('classic'),
            ]);
        }

        $form_styles = [(new Text('h4', __('Style')))];
        foreach ($current->filter->class as $k => $v) {
            $form_styles[] = (new Para())->items([
                (new Label(sprintf(__('Class "%s":'), $v), Label::OUTSIDE_LABEL_BEFORE))->for('filter_style' . $k),
                (new Input(['filter_style[]', 'filter_style' . $k]))->size(60)->maxlenght(255)->value(Html::escapeHTML($current->filter->style[$k])),
            ]);
        }

        echo 
        (new Div('setting'))->class('multi-part')->title(__('Settings'))->items([
            (new Form('setting_form'))->method('post')->action(dcCore::app()->adminurl->get('admin.plugin.' . My::id()) . '#setting')->separator('')->fields([
                (new Div())->class('two-boxes even')->items($form_pages),
                (new Div())->class('two-boxes odd')->items([
                    (new Text('h4', __('Filtering'))),
                    (new Para())->items([
                        (new Checkbox('filter_nocase', $current->filter->nocase))->value(1),
                        (new Label(__('Case insensitive'), Label::OUTSIDE_LABEL_AFTER))->for('filter_nocase')->class('classic'),
                    ]),
                    (new Para())->items([
                        (new Checkbox('filter_plural', $current->filter->plural))->value(1),
                        (new Label(__('Also use the plural'), Label::OUTSIDE_LABEL_AFTER))->for('filter_plural')->class('classic'),
                    ]),
                    (new Para())->items([
                        (new Label(__('Limit the number of replacement to:'), Label::OUTSIDE_LABEL_BEFORE))->for('filter_limit'),
                        (new Number('filter_limit'))->min(0)->max(99)->value((int) $current->filter->limit),
                    ]),
                    (new Note())->class('form-note')->text(__('Leave it blank or set it to 0 for no limit')),
                ]),
                (new Div())->class('two-boxes even')->items($form_values),
                (new Div())->class('two-boxes odd')->items(array_merge($form_styles, [
                    (new Note())->class('form-note')->text(sprintf(__('The inserted HTML tag looks like: %s'), Html::escapeHTML(str_replace('%s', '...', $current->filter->replace)))),
                    (new Para())->items([
                        (new Label(__('Ignore HTML tags:'), Label::OUTSIDE_LABEL_BEFORE))->for('filter_notag'),
                        (new Input('filter_notag'))->size(60)->maxlenght(255)->value(Html::escapeHTML($current->filter->notag)),
                    ]),
                    (new Note())->class('form-note')->text(__('This is the list of HTML tags where content will be ignored.') . ' ' . ('' != $current->filter->htmltag ? '' : sprintf(__('Tag "%s" always be ignored.'), $current->filter->htmltag))),

                ])),
                (new Div())->class('clear')->items([
                    dcCore::app()->formNonce(false),
                    (new Hidden(['action'], 'savefiltersetting')),
                    (new Hidden(['part'], $current->part)),
                    (new Submit(['save']))->value(__('Save')),
                ]),
            ]),
        ])->render();

        # Filter records list
        if ($current->filter->has_list && isset($sorts) && isset($pager)) {
            $pager_url = dcCore::app()->adminurl->get('admin.plugin.' . My::id(), array_diff_key($sorts->values(true), ['page' => ''])) . '&page=%s#record';

            echo '
            <div class="multi-part" id="record" title="' . __('Records') . '">';

            $sorts->display(['admin.plugin.' . My::id(), '#record'], (new Hidden('p', My::id()))->render() . (new Hidden('part', $current->part))->render());

            $pager->display(
                $sorts,
                $pager_url,
                '<form action="' . dcCore::app()->adminurl->get('admin.plugin.' . My::id()) . '#record" method="post" id="form-records">' .
                '%s' .

                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .

                (new Para())->class('col right')->items(array_merge(
                    dcCore::app()->adminurl->hiddenFormFields('admin.plugin.' . My::id(), array_merge(['p' => My::id()], $sorts->values(true))),
                    [
                        dcCore::app()->formNonce(false),
                        (new Hidden('redir', dcCore::app()->adminurl->get('admin.plugin.' . My::id(), $sorts->values(true)))),
                        (new Hidden('action', 'deleterecords')),
                        (new Submit(['save', 'del-action']))->value(__('Delete selected records')),
                    ]
                ))->render() .
                '</div>' .
                '</form>'
            );

            echo '</div>';

            # New record
            echo 
            (new Div('newrecord'))->class('multi-part')->title(__('New record'))->items([
                (new Form('form-create'))->method('post')->action(dcCore::app()->adminurl->get('admin.plugin.' . My::id()) . '#record')->fields([
                    (new Para())->items([
                        (new Label(__('Key:'), Label::OUTSIDE_LABEL_BEFORE))->for('new_key'),
                        (new Input('new_key'))->size(60)->maxlenght(255)->required(true),
                    ]),
                    (new Para())->items([
                        (new Label(__('Value:'), Label::OUTSIDE_LABEL_BEFORE))->for('new_value'),
                        (new Input('new_value'))->size(60)->maxlenght(255)->required(true),
                    ]),
                    (new Para())->class('clear')->items([
                        dcCore::app()->formNonce(false),
                        (new Hidden(['action'], 'savenewrecord')),
                        (new Hidden(['part'], $current->part)),
                        (new Submit(['save', 'new-action']))->value(__('Save')),
                    ]),
                ]),
            ])->render();
        }

        # --BEHAVIOR-- enhancePostContentAdminPage
        dcCore::app()->callBehavior('enhancePostContentAdminPage');

        dcPage::helpBlock('enhancePostContent');
        dcPage::closeModule();
    }
}
