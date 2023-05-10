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
    Label,
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
            && !is_null(dcCore::app()->auth) && !is_null(dcCore::app()->blog)
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

        // nullsafe check
        if (is_null(dcCore::app()->blog) || is_null(dcCore::app()->adminurl)) {
            return false;
        }

        // get filter and post values
        $action = $_POST['action'] ?? '';
        $filter = Epc::getFilters()->get($_REQUEST['part'] ?? '');
        if (is_null($filter)) {
            return true;
        }

        // check errors
        if (dcCore::app()->error->flag()) {
            return true;
        }

        // open save to other plugins
        if (!empty($action)) {
            # --BEHAVIOR-- enhancePostContentAdminSave
            dcCore::app()->callBehavior('enhancePostContentAdminSave');
        }

        try {
            // Update filter settings
            if ($action == 'savefiltersetting') {
                # Parse filters options
                $f = [
                    'nocase'   => !empty($_POST['filter_nocase']),
                    'plural'   => !empty($_POST['filter_plural']),
                    'limit'    => abs((int) $_POST['filter_limit']),
                    'style'    => (array) $_POST['filter_style'],
                    'notag'    => Epc::decodeSingle($_POST['filter_notag']),
                    'template' => (array) $_POST['filter_template'],
                    'page'     => (array) $_POST['filter_page'],
                ];

                dcCore::app()->blog->settings->get(My::id())->put($filter->id(), json_encode($f));

                dcCore::app()->blog->triggerBlog();

                dcPage::addSuccessNotice(
                    __('Filter successfully updated.')
                );

                dcCore::app()->adminurl->redirect(
                    'admin.plugin.' . My::id(),
                    ['part' => $filter->id()],
                    '#settings'
                );
            }

            // Add new filter record
            if ($action == 'savenewrecord'
                && !empty($_POST['new_key'])
                && !empty($_POST['new_value'])
            ) {
                $cur = EpcRecord::openCursor();
                $cur->setField('epc_filter', $filter->id());
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
                    ['part' => $filter->id()],
                    '#record'
                );
            }

            // Update filter records
            if ($action == 'deleterecords'
                && $filter->has_list
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
                        ['part' => $filter->id()],
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

        // nullsafe check
        if (is_null(dcCore::app()->blog) || is_null(dcCore::app()->adminurl)) {
            return;
        }

        // get filters
        $filters = Epc::getFilters();
        $filter  = $filters->get($_REQUEST['part'] ?? 'link');
        if (is_null($filter)) {
            return;
        }

        // sort filters by name on backend
        Epc::getFilters()->sort(true);

        // Prepare tabs and lists
        $header = '';
        if ($filter->has_list) {
            $sorts = new adminGenericFilterV2('epc');
            $sorts->add(dcAdminFilters::getPageFilter());
            $sorts->add('part', $filter->id());

            $params               = $sorts->params();
            $params['epc_filter'] = $filter->id();

            try {
                $list    = EpcRecord::getRecords($params);
                $counter = EpcRecord::getRecords($params, true);
                $pager   = new BackendList($list, (int) $counter->f(0));
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }

            $header = $sorts->js(dcCore::app()->adminurl->get('admin.plugin.' . My::id(), ['part' => $filter->id()], '&') . '#record');
        }

        // display
        dcPage::openModule(
            My::name(),
            dcPage::jsPageTabs() .
            dcPage::jsModuleLoad(My::id() . '/js/backend.js') .
            $header .

            # --BEHAVIOR-- enhancePostContentAdminHeader
            dcCore::app()->callBehavior('enhancePostContentAdminHeader')
        );

        echo
        dcPage::breadcrumb([
            __('Plugins') => '',
            My::name()    => '',
            $filter->name => '',
        ]) .
        dcPage::notices();

        // filters select menu
        echo
        (new Form('filters_menu'))->method('get')->action(dcCore::app()->adminurl->get('admin.plugin.' . My::id()))->fields([
            (new Para())->class('anchor-nav')->items([
                (new Select('part'))->items($filters->nid())->default($filter->id()),
                (new Submit(['do']))->value(__('Ok')),
                (new Hidden(['p'], My::id())),
            ]),
        ])->render();

        // selected filter
        echo
        '<h3>' . $filter->name . '</h3>' .
        '<p>' . $filter->description . '</p>';

        // Filter settings
        $form_pages = [(new Text('h4', __('Pages to be filtered')))];
        foreach (Epc::blogAllowedTemplatePage() as $k => $v) {
            $form_pages[] = (new Para())->items([
                (new Checkbox(['filter_page[]', 'filter_page' . $v], in_array($v, $filter->page)))->value($v),
                (new Label(__($k), Label::OUTSIDE_LABEL_AFTER))->for('filter_page' . $v)->class('classic'),
            ]);
        }

        $form_values = [(new Text('h4', __('Contents to be filtered')))];
        foreach (Epc::blogAllowedTemplateValue() as $k => $v) {
            $form_values[] = (new Para())->items([
                (new Checkbox(['filter_template[]', 'filter_template' . $v], in_array($v, $filter->template)))->value($v),
                (new Label(__($k), Label::OUTSIDE_LABEL_AFTER))->for('filter_template' . $v)->class('classic'),
            ]);
        }

        $form_styles = [(new Text('h4', __('Style')))];
        foreach ($filter->class as $k => $v) {
            $form_styles[] = (new Para())->items([
                (new Label(sprintf(__('Class "%s":'), $v), Label::OUTSIDE_LABEL_BEFORE))->for('filter_style' . $k),
                (new Input(['filter_style[]', 'filter_style' . $k]))->size(60)->maxlenght(255)->value(Html::escapeHTML($filter->style[$k])),
            ]);
        }

        echo
        (new Div('setting'))->class('multi-part')->title(__('Settings'))->items([
            (new Form('setting_form'))->method('post')->action(dcCore::app()->adminurl->get('admin.plugin.' . My::id()) . '#setting')->separator('')->fields([
                (new Div())->class('two-boxes even')->items($form_pages),
                (new Div())->class('two-boxes odd')->items([
                    (new Text('h4', __('Filtering'))),
                    (new Para())->items([
                        (new Checkbox('filter_nocase', $filter->nocase))->value(1),
                        (new Label(__('Case insensitive'), Label::OUTSIDE_LABEL_AFTER))->for('filter_nocase')->class('classic'),
                    ]),
                    (new Para())->items([
                        (new Checkbox('filter_plural', $filter->plural))->value(1),
                        (new Label(__('Also use the plural'), Label::OUTSIDE_LABEL_AFTER))->for('filter_plural')->class('classic'),
                    ]),
                    (new Para())->items([
                        (new Label(__('Limit the number of replacement to:'), Label::OUTSIDE_LABEL_BEFORE))->for('filter_limit'),
                        (new Number('filter_limit'))->min(0)->max(99)->value((int) $filter->limit),
                    ]),
                    (new Note())->class('form-note')->text(__('Leave it blank or set it to 0 for no limit')),
                ]),
                (new Div())->class('two-boxes even')->items($form_values),
                (new Div())->class('two-boxes odd')->items(array_merge($form_styles, [
                    (new Note())->class('form-note')->text(sprintf(__('The inserted HTML tag looks like: %s'), Html::escapeHTML(str_replace('%s', '...', $filter->replace)))),
                    (new Para())->items([
                        (new Label(__('Ignore HTML tags:'), Label::OUTSIDE_LABEL_BEFORE))->for('filter_notag'),
                        (new Input('filter_notag'))->size(60)->maxlenght(255)->value(Epc::encodeSingle($filter->notag)),
                    ]),
                    (new Note())->class('form-note')->text(__('This is the list of HTML tags where content will be ignored.') . '<br />' . (empty($filter->ignore) ? '' : sprintf(__('Tags "%s" will allways be ignored.'), Epc::encodeSingle($filter->ignore)))),

                ])),
                (new Div())->class('clear')->items([
                    dcCore::app()->formNonce(false),
                    (new Hidden(['action'], 'savefiltersetting')),
                    (new Hidden(['part'], $filter->id())),
                    (new Submit(['save']))->value(__('Save')),
                ]),
            ]),
        ])->render();

        // Filter records list (if any)
        if ($filter->has_list && isset($pager)) {
            $pager_url = dcCore::app()->adminurl->get('admin.plugin.' . My::id(), array_diff_key($sorts->values(true), ['page' => ''])) . '&page=%s#record';

            echo '
            <div class="multi-part" id="record" title="' . __('Records') . '">';

            $sorts->display(['admin.plugin.' . My::id(), '#record'], (new Hidden('p', My::id()))->render() . (new Hidden('part', $filter->id()))->render());

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

            // New record
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
                        (new Hidden(['part'], $filter->id())),
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
