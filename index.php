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

dcPage::check('contentadmin');

# -- Prepare queries and object --

$_filters = libEPC::getFilters();

$filters_id = $filters_combo = [];
foreach ($_filters as $id => $filter) {
    $filters_id[$id]              = $filter->name;
    $filters_combo[$filter->name] = $id;
}

$action = $_POST['action']  ?? '';
$part   = $_REQUEST['part'] ?? key($filters_id);

if (!isset($filters_id[$part])) {
    return null;
}

$header  = '';
$filter  = $_filters[$part];
$records = new epcRecords($core);

# -- Action --

if (!empty($action)) {
    # --BEHAVIOR-- enhancePostContentAdminSave
    $core->callBehavior('enhancePostContentAdminSave', $core);
}

try {
    # Update filter settings
    if ($action == 'savefiltersetting') {
        # Parse filters options
        $f = [
            'nocase'    => !empty($_POST['filter_nocase']),
            'plural'    => !empty($_POST['filter_plural']),
            'limit'     => abs((int) $_POST['filter_limit']),
            'style'     => (array) $_POST['filter_style'],
            'notag'     => (string) $_POST['filter_notag'],
            'tplValues' => (array) $_POST['filter_tplValues'],
            'pubPages'  => (array) $_POST['filter_pubPages']
        ];

        $core->blog->settings->addNamespace('enhancePostContent');
        $core->blog->settings->enhancePostContent->put('enhancePostContent_' . $filter->id(), serialize($f));

        $core->blog->triggerBlog();

        dcPage::addSuccessNotice(
            __('Filter successfully updated.')
        );

        $core->adminurl->redirect(
            'admin.plugin.enhancePostContent',
            ['part' => $part],
            '#settings'
        );
    }

    # Add new filter record
    if ($action == 'savenewrecord'
        && !empty($_POST['new_key'])
        && !empty($_POST['new_value'])
    ) {
        $cur             = $records->openCursor();
        $cur->epc_filter = $filter->id();
        $cur->epc_key    = html::escapeHTML($_POST['new_key']);
        $cur->epc_value  = html::escapeHTML($_POST['new_value']);

        if ($records->isRecord($cur->epc_filter, $cur->epc_key)) {
            dcPage::addErrorNotice(__('Key already exists for this filter'));
        } else {
            $records->addRecord($cur);

            $core->blog->triggerBlog();

            dcPage::addSuccessNotice(
                __('Filter successfully updated.')
            );
        }
        $core->adminurl->redirect(
            'admin.plugin.enhancePostContent',
            ['part' => $part],
            '#record'
        );
    }

    # Update filter records
    if ($action == 'deleterecords' && $filter->has_list
        && !empty($_POST['epc_id']) && is_array($_POST['epc_id'])
    ) {
        foreach ($_POST['epc_id'] as $id) {
            $records->delRecord($id);
        }

        $core->blog->triggerBlog();

        dcPage::addSuccessNotice(
            __('Filter successfully updated.')
        );

        if (!empty($_REQUEST['redir'])) {
            http::redirect($_REQUEST['redir']);
        } else {
            $core->adminurl->redirect(
                'admin.plugin.enhancePostContent',
                ['part' => $part],
                '#record'
            );
        }
    }
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

# -- Prepare page --

if ($filter->has_list) {
    $sorts = new adminGenericFilter($core, 'epc');
    $sorts->add(dcAdminFilters::getPageFilter());
    $sorts->add('part', $part);

    $params               = $sorts->params();
    $params['epc_filter'] = $filter->id();

    try {
        $list    = $records->getRecords($params);
        $counter = $records->getRecords($params, true);
        $pager   = new adminEpcList($core, $list, $counter->f(0));
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }

    $header = $sorts->js($core->adminurl->get('admin.plugin.enhancePostContent', ['part' => $part], '&') . '#record');
}

# -- Display page --

# Page headers
echo '
<html><head><title>' . __('Enhance post content') . '</title>' .
dcPage::jsPageTabs() .
dcPage::jsLoad(dcPage::getPF('enhancePostContent/js/index.js')) .
$header .

# --BEHAVIOR-- enhancePostContentAdminHeader
$core->callBehavior('enhancePostContentAdminHeader', $core) . '

</head><body>' .

# Page title
dcPage::breadcrumb([
    __('Plugins')              => '',
    __('Enhance post content') => '',
    $filter->name              => ''
]) .
dcPage::notices() .

# Filters select menu list
'<form method="get" action="' . $core->adminurl->get('admin.plugin.enhancePostContent') . '" id="filters_menu">' .
'<p class="anchor-nav"><label for="part" class="classic">' . __('Select filter:') . ' </label>' .
form::combo('part', $filters_combo, $part) . ' ' .
'<input type="submit" value="' . __('Ok') . '" />' .
form::hidden('p', 'enhancePostContent') . '</p>' .
'</form>';

# Filter title and description
echo '
<h3>' . $filter->name . '</h3>
<p>' . $filter->help . '</p>';

# Filter settings
echo '
<div class="multi-part" id="setting" title="' . __('Settings') . '">
<form method="post" action="' . $core->adminurl->get('admin.plugin.enhancePostContent') . '#setting">

<div class="two-boxes odd">
<h4>' . __('Pages to be filtered') . '</h4>';

foreach (libEPC::blogAllowedPubPages() as $k => $v) {
    echo '
    <p><label for="filter_pubPages' . $v . '">' .
    form::checkbox(
        ['filter_pubPages[]', 'filter_pubPages' . $v],
        $v,
        in_array($v, $filter->pubPages)
    ) .
    __($k) . '</label></p>';
}

echo '
</div><div class="two-boxes even">
<h4>' . __('Filtering') . '</h4>

<p><label for="filter_nocase">' .
form::checkbox('filter_nocase', '1', $filter->nocase) .
__('Case insensitive') . '</label></p>

<p><label for="filter_plural">' .
form::checkbox('filter_plural', '1', $filter->plural) .
__('Also use the plural') . '</label></p>

<p><label for="filter_limit">' .
__('Limit the number of replacement to:') . '</label>' .
form::number('filter_limit', ['min' => 0, 'max' => 99, 'default' => (int) $filter->limit]) . '
</p>
<p class="form-note">' . __('Leave it blank or set it to 0 for no limit') . '</p>

</div><div class="two-boxes odd">
<h4>' . __('Contents to be filtered') . '</h4>';

foreach (libEPC::blogAllowedTplValues() as $k => $v) {
    echo '
    <p><label for="filter_tplValues' . $v . '">' .
    form::checkbox(
        ['filter_tplValues[]', 'filter_tplValues' . $v],
        $v,
        in_array($v, $filter->tplValues)
    ) .
    __($k) . '</label></p>';
}

echo '
</div><div class="two-boxes even">
<h4>' . __('Style') . '</h4>';

foreach ($filter->class as $k => $v) {
    echo '
    <p><label for="filter_style' . $k . '">' .
    sprintf(__('Class "%s":'), $v) . '</label>' .
    form::field(
        ['filter_style[]', 'filter_style' . $k],
        60,
        255,
        html::escapeHTML($filter->style[$k])
    ) .
    '</p>';
}

echo '
<p class="form-note">' . sprintf(__('The inserted HTML tag looks like: %s'), html::escapeHTML(str_replace('%s', '...', $filter->replace))) . '</p>

<p><label for="filter_notag">' . __('Ignore HTML tags:') . '</label>' .
form::field('filter_notag', 60, 255, html::escapeHTML($filter->notag)) . '
</p>
<p class="form-note">' . __('This is the list of HTML tags where content will be ignored.') . ' ' .
('' != $filter->htmltag ? '' : sprintf(__('Tag "%s" always be ignored.'), $filter->htmltag)) . '</p>
</div>
<div class="clear">
<p>' .
$core->formNonce() .
form::hidden(['action'], 'savefiltersetting') .
form::hidden(['part'], $part) . '
<input type="submit" name="save" value="' . __('Save') . '" />
</p>
</div>

</form>
</div>';

# Filter records list
if ($filter->has_list) {
    $pager_url = $core->adminurl->get('admin.plugin.enhancePostContent', array_diff_key($sorts->values(true), ['page' => ''])) . '&page=%s#record';

    echo '
    <div class="multi-part" id="record" title="' . __('Records') . '">';

    $sorts->display(['admin.plugin.enhancePostContent', '#record'], form::hidden('p', 'enhancePostContent') . form::hidden('part', $part));

    $pager->display(
        $sorts,
        $pager_url,
        '<form action="' . $core->adminurl->get('admin.plugin.enhancePostContent') . '#record" method="post" id="form-records">' .
        '%s' .

        '<div class="two-cols">' .
        '<p class="col checkboxes-helpers"></p>' .

        '<p class="col right">' .
        form::hidden('action', 'deleterecords') .
        '<input id="del-action" type="submit" name="save" value="' . __('Delete selected records') . '" /></p>' .
        $core->adminurl->getHiddenFormFields('admin.plugin.enhancePostContent', array_merge(['p' => 'enhancePostContent'], $sorts->values(true))) .
        form::hidden('redir', $core->adminurl->get('admin.plugin.enhancePostContent', $sorts->values(true))) .
        $core->formNonce() .
        '</div>' .
        '</form>'
    );

    echo '</div>';

    # New record
    echo '
    <div class="multi-part" id="newrecord" title="' . __('New record') . '">
    <form action="' . $core->adminurl->get('admin.plugin.enhancePostContent') . '#record" method="post" id="form-create">' .

    '<p><label for="new_key">' . __('Key:') . '</label>' .
    form::field('new_key', 60, 255, ['extra_html' => 'required']) .
    '</p>' .

    '<p><label for="new_value">' . __('Value:') . '</label>' .
    form::field('new_value', 60, 255, ['extra_html' => 'required']) .
    '</p>

    <p class="clear">' .
    form::hidden(['action'], 'savenewrecord') .
    form::hidden(['part'], $part) .
    $core->formNonce() . '
    <input id="new-action" type="submit" name="save" value="' . __('Save') . '" />
    </p>
    </form>
    </div>';
}

# --BEHAVIOR-- enhancePostContentAdminPage
$core->callBehavior('enhancePostContentAdminPage', $core);

dcPage::helpBlock('enhancePostContent');

echo '</body></html>';
