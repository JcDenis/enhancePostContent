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

$_filters = libEPC::blogFilters();
$filters_id = array();
foreach($_filters as $name => $filter) {
    $filters_id[$filter['id']] = $name;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$default_part = isset($_REQUEST['part']) ? $_REQUEST['part'] : key($filters_id);

$records = new epcRecords($core);

# -- Action --

if (!empty($action)) {
    # --BEHAVIOR-- enhancePostContentAdminSave
    $core->callBehavior('enhancePostContentAdminSave', $core);
}

try {
    # Update filter settings
    if ($action == 'savefiltersetting'
        && isset($filters_id[$default_part])
    ) {
        # Parse filters options
        $name = $filters_id[$default_part];
        $f = [
            'nocase'    => !empty($_POST['filter_nocase']),
            'plural'    => !empty($_POST['filter_plural']),
            'limit'     => abs((integer) $_POST['filter_limit']),
            'style'     => (array) $_POST['filter_style'],
            'notag'     => (string) $_POST['filter_notag'],
            'tplValues' => (array) $_POST['filter_tplValues'],
            'pubPages'  => (array) $_POST['filter_pubPages']
        ];

        $core->blog->settings->addNamespace('enhancePostContent');
        $core->blog->settings->enhancePostContent->put('enhancePostContent_' . $name, serialize($f));

        $core->blog->triggerBlog();

        dcPage::addSuccessNotice(
            __('Filter successfully updated.')
        );

        $core->adminurl->redirect(
            'admin.plugin.enhancePostContent', 
            ['part' => $default_part],
            '#settings'
        );
    }

    # Add new filter record
    if ($action == 'savenewrecord'
        && isset($filters_id[$default_part])
        && !empty($_POST['new_key'])
        && !empty($_POST['new_value'])
    ) {
        $cur = $records->openCursor();
        $cur->epc_filter = $filters_id[$default_part];
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
            ['part' => $default_part],
            '#record'
        );
    }

    # Update filter records
    $error = false;
    if ($action == 'saveupdaterecords'
        && isset($filters_id[$default_part])
        && $_filters[$filters_id[$default_part]]['has_list']
    ) {
        foreach($_POST['epc_id'] as $k => $id) {
            $k = abs((integer) $k);
            $id = abs((integer) $id);

            if (empty($_POST['epc_key'][$k])
                || empty($_POST['epc_value'][$k])
            ) {
                $records->delRecord($id);
            } elseif ($_POST['epc_key'][$k] != $_POST['epc_old_key'][$k] 
                || $_POST['epc_value'][$k] != $_POST['epc_old_value'][$k]
            ) {
                $cur = $records->openCursor();
                $cur->epc_filter = $filters_id[$default_part];
                $cur->epc_key    = html::escapeHTML($_POST['epc_key'][$k]);
                $cur->epc_value  = html::escapeHTML($_POST['epc_value'][$k]);

                if ($records->isRecord($cur->epc_filter, $cur->epc_key, $id)) {
                    dcPage::addErrorNotice(__('Key already exists for this filter'));
                    $error = true;
                } else {
                    $records->updRecord($id, $cur);
                }
            }
        }

        $core->blog->triggerBlog();

        $redir = !empty($_REQUEST['redir']) ? 
            $_REQUEST['redir'] :
            $core->adminurl->get('admin.plugin.enhancePostContent', ['part' => $default_part]) . '#record';
        if (!$error) {
            dcPage::addSuccessNotice(
                __('Filter successfully updated.')
            );
        }
        http::redirect(
            $redir
        );
    }
} catch(Exception $e) {
    $core->error->add($e->getMessage());
}

# -- Prepare page --

$breadcrumb = [html::escapeHTML($core->blog->name) => '', __('Enhance post content') => '', __('Filters') => ''];

$filters_combo = [];
foreach($filters_id as $id => $name) {
    if ($default_part == $id) {
        $breadcrumb[__($filters_id[$default_part])] = '';
    }
    $filters_combo[__($name)] = $id;
}

# -- Display page --

# Headers
echo '
<html><head><title>' . __('Enhance post content') . '</title>' .
//dcPage::jsLoad('js/_posts_list.js') .
dcPage::jsToolbar() .
dcPage::jsPageTabs() .
dcPage::jsLoad(dcPage::getPF('enhancePostContent/js/index.js')) .

# --BEHAVIOR-- enhancePostContentAdminHeader
$core->callBehavior('enhancePostContentAdminHeader', $core) . '

</head><body>' .

# Title
dcPage::breadcrumb($breadcrumb) .
dcPage::notices() .

# Filters list
'<form method="post" action="' . $p_url . '&tab=settings">' .
'<p class="anchor-nav"><label for="epc_tab" class="classic">' . __('Select filter:') . ' </label>' .
 form::combo('part', $filters_combo, $default_part) . ' ' .
$core->formNonce() .
'<input type="submit" value="' . __('Ok') . '" /></p>' .
'</form>';

# Filter content
if (isset($filters_id[$default_part])) {
    $name = $filters_id[$default_part];
    $filter = $_filters[$name];

    # Filter title and description
    echo '
    <h3>' . __($filters_id[$default_part]) . '</h3>
    <p>' . $filter['help'] . '</p>';

    # Filter settings
    echo '
    <div class="multi-part" id="setting" title="' . __('Settings') . '">
    <form method="post" action="' . $p_url . '&amp;part=' . $default_part . '&amp;tab=setting"><div>';

    echo 
    '<div class="two-boxes odd">
    <h4>' . __('Pages to be filtered') . '</h4>';

    foreach(libEPC::blogAllowedPubPages() as $k => $v) {
        echo '
        <p><label for="filter_pubPages' . $v . '">' .
        form::checkbox(
            ['filter_pubPages[]', 'filter_pubPages' . $v],
            $v,
            in_array($v, $filter['pubPages'])
        ) .
        __($k) . '</label></p>';
    }

    echo 
    '</div>';

    echo 
    '<div class="two-boxes even">
    <h4>' . __('Filtering') . '</h4>

    <p><label for="filter_nocase">' .
    form::checkbox('filter_nocase', '1', $filter['nocase']) .
    __('Case insensitive') . '</label></p>

    <p><label for="filter_plural">' .
    form::checkbox('filter_plural', '1', $filter['plural']) .
    __('Also use the plural') . '</label></p>

    <p><label for="filter_limit">' .
    __('Limit the number of replacement to:') . '</label>' .
    form::field('filter_limit', 4, 10, html::escapeHTML($filter['limit'])) . '
    </p>
    <p class="form-note">' . __('Leave it blank or set it to 0 for no limit') . '</p>

    </div>';

    echo 
    '<div class="two-boxes odd">
    <h4>' . __('Contents to be filtered') . '</h4>';

    foreach(libEPC::blogAllowedTplValues() as $k => $v) {
        echo '
        <p><label for="filter_tplValues' . $v . '">' .
        form::checkbox(
            ['filter_tplValues[]', 'filter_tplValues' . $v],
            $v,
            in_array($v, $filter['tplValues'])
        ) .
        __($k) . '</label></p>';
    }

    echo 
    '</div>';

    echo 
    '<div class="two-boxes even">
    <h4>' . __('Style') . '</h4>';

    foreach($filter['class'] as $k => $v) {
        echo '
        <p><label for="filter_style' . $k . '">' .
        sprintf(__('Class "%s":'), $v) . '</label>' .
        form::field(
            ['filter_style[]', 'filter_style'.$k],
            60,
            255,
            html::escapeHTML($filter['style'][$k])
        ) .
        '</p>';
    }

    echo '
    <p class="form-note">' . sprintf(__('The inserted HTML tag looks like: %s'), html::escapeHTML(str_replace('%s', '...', $filter['replace']))) . '</p>

    <p><label for="filter_notag">' . __('Ignore HTML tags:') . '</label>' .
    form::field('filter_notag', 60, 255, html::escapeHTML($filter['notag'])) . '
    </p>
    <p class="form-note">' . __('This is the list of HTML tags where content will be ignored.') . ' ' .
    (empty($filter['htmltag']) ? '' : sprintf(__('Tag "%s" always be ignored.'), $filter['htmltag'])) . '</p>

    </div>';

    echo '</div>
    <div class="clear">
    <p>' .
    $core->formNonce() .
    form::hidden(['action'], 'savefiltersetting') . '
    <input type="submit" name="save" value="' . __('Save') . '" />
    </p>
    </div>

    </form>
    </div>';

    # Filter records list
    if ($filter['has_list']) {
        $sorts = new adminGenericFilter($core, 'epc');
        $sorts->add(dcAdminFilters::getPageFilter());

        $params = $sorts->params();
        $params['epc_filter'] = $name;

        try {
            $list = $records->getRecords($params);
            $counter = $records->getRecords($params, true);

            $pager_url = $p_url .
                '&amp;nb=' . $sorts->nb .
                '&amp;sortby=%s' .
                '&amp;order=%s' . 
                '&amp;page=%s' .
                '&amp;part=' . $default_part .
                '#record';

            $pager = new dcPager($sorts->page, $counter->f(0), $sorts->nb, 10);
            $pager->base_url = sprintf($pager_url, $sorts->sortby, $sorts->order, '%s');
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }

        echo '
        <div class="multi-part" id="record" title="' . __('Records') . '">';

        if ($core->error->flag() || $list->isEmpty()) {
            echo '<p>' . __('No record') . '</p>';
        } else {
            echo '
            <form action="' . sprintf($pager_url, $sorts->sortby, $sorts->order, $sorts->page) . '" method="post">' .

            $pager->getLinks() . '

            <div class="table-outer">
            <table><caption class="hidden">' . __('Records') . '</caption>
            <thead><tr>';

            $lines = [
                __('Key')   => 'epc_key',
                __('Value') => 'epc_value',
                __('Date')  => 'epc_date'
            ];
            foreach($lines as $k => $v) {
                echo '<th><a href="' . sprintf($pager_url, $v, $sorts->sortby == $v ? $sorts->order == 'asc' ? 'desc' : 'asc' : $sorts->order, $sorts->page) . '">' .$k . '</a></th>';
            }

            echo '
            </tr></thead>
            <tbody>';

            while($list->fetch()) {
                echo '
                <tr class="line">
                <td class="nowrap">' .
                form::hidden(['epc_id[]'], $list->epc_id) .
                form::hidden(['epc_old_key[]'], html::escapeHTML($list->epc_key)) .
                form::hidden(['epc_old_value[]'], html::escapeHTML($list->epc_value)) .
                form::field(['epc_key[]'], 30, 225, html::escapeHTML($list->epc_key), '') . '</td>
                <td class="maximal">' .
                form::field(['epc_value[]'], 90, 225, html::escapeHTML($list->epc_value), '') . '</td>
                <td class="nowrap count">' .
                dt::dt2str(__('%Y-%m-%d %H:%M'), $list->epc_upddt,$core->auth->getInfo('user_tz')) . '</td>
                </tr>';
            }

            echo '
            </tbody>
            </table></div>
            <p class="form-note">' . __('In order to remove a record, leave empty its key or value.') . '</p>' .

            $pager->getLinks() . '

            <div class="clear">
            <p>' .
            $core->formNonce() .
            form::hidden(['redir'], sprintf($pager_url, $sorts->sortby, $sorts->order, $sorts->page)) .
            form::hidden(['action'], 'saveupdaterecords') . '
            <input type="submit" name="save" value="' . __('Save') . '" />
            </p>
            </div>

            </form>';
        }

        echo '</div>';

        # New record
        echo '
        <div class="multi-part" id="newrecord" title="' . __('New record') . '">
        <form action="' . 
            $core->adminurl->get('admin.plugin.enhancePostContent', ['part' => $default_part]) . 
            '#record" method="post">' .

        '<p><label for="new_key">' . __('Key:') . '</label>' .
        form::field('new_key', 60, 255) .
        '</p>' .

        '<p><label for="new_value">' . __('Value:') . '</label>' .
        form::field('new_value', 60, 255) .
        '</p>

        <p class="clear">' .
        form::hidden(['action'], 'savenewrecord') .
        $core->formNonce() . '
        <input type="submit" name="save" value="' . __('Save') . '" />
        </p>
        </form>
        </div>';
    }
}

# --BEHAVIOR-- enhancePostContentAdminPage
$core->callBehavior('enhancePostContentAdminPage', $core);

dcPage::helpBlock('enhancePostContent');

echo '</body></html>';