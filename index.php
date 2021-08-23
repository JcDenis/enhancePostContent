<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of enhancePostContent, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2013 Jean-Christian Denis and contributors
# contact@jcdenis.fr http://jcd.lv
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_CONTEXT_ADMIN')) {

	return null;
}

dcPage::check('contentadmin');

$core->blog->settings->addNamespace('enhancePostContent');
$s = $core->blog->settings->enhancePostContent;

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
	$core->callBehavior('enhancePostContentAdminSave',$core);
}

try
{
	# Update filter settings
	if ($action == 'savefiltersetting'
	 && isset($filters_id[$default_part])
	) {
		# Parse filters options
		$name = $filters_id[$default_part];
		$f = array(
			'nocase'		=> !empty($_POST['filter_nocase']),
			'plural'		=> !empty($_POST['filter_plural']),
			'limit'		=> abs((integer) $_POST['filter_limit']),
			'style'		=> (array) $_POST['filter_style'],
			'notag'		=> (string) $_POST['filter_notag'],
			'tplValues'	=> (array) $_POST['filter_tplValues'],
			'pubPages'	=> (array) $_POST['filter_pubPages']
		);

		$s->put('enhancePostContent_'.$name, serialize($f));

		$core->blog->triggerBlog();

		dcPage::addSuccessNotice(
			__('Filter successfully updated.')
		);
		http::redirect(
			$p_url.'part='.$default_part.'#setting'
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
		$cur->epc_key = html::escapeHTML($_POST['new_key']);
		$cur->epc_value = html::escapeHTML($_POST['new_value']);
		$records->addRecord($cur);

		$core->blog->triggerBlog();

		dcPage::addSuccessNotice(
			__('Filter successfully updated.')
		);
		http::redirect(
			$p_url.'&part='.$default_part.'#record'
		);
	}

	# Update filter records
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
			}
			elseif ($_POST['epc_key'][$k] != $_POST['epc_old_key'][$k] 
			 || $_POST['epc_value'][$k] != $_POST['epc_old_value'][$k]
			) {
				$cur = $records->openCursor();
				$cur->epc_filter = $filters_id[$default_part];
				$cur->epc_key = html::escapeHTML($_POST['epc_key'][$k]);
				$cur->epc_value = html::escapeHTML($_POST['epc_value'][$k]);
				$records->updRecord($id,$cur);
			}
		}

		$core->blog->triggerBlog();

		$redir = !empty($_REQUEST['redir']) ? 
			$_REQUEST['redir'] :
			$p_url.'&part='.$default_part.'#record';

		dcPage::addSuccessNotice(
			__('Filter successfully updated.')
		);
		http::redirect(
			$redir
		);
	}
}
catch(Exception $e) {
	$core->error->add($e->getMessage());
}

# -- Prepare page --

$breadcrumb = array(
	html::escapeHTML($core->blog->name) => '',
	__('Enhance post content') => $p_url
);
$top_menu = array();

foreach($filters_id as $id => $name) {

	$active = '';
	if ($default_part == $id) {
		$breadcrumb[__($filters_id[$default_part])] = '';
		$active = ' class="active"';
	}

	$top_menu[] = 
	'<a'.$active.' href="'.$p_url.'&amp;part='.$id.'">'.__($name).'</a>';
}

# -- Display page --

# Headers
echo '
<html><head><title>'.__('Enhance post content').'</title>'.
dcPage::jsLoad('js/_posts_list.js').
dcPage::jsToolbar().
dcPage::jsPageTabs().

# --BEHAVIOR-- enhancePostContentAdminHeader
$core->callBehavior('enhancePostContentAdminHeader',$core).'

</head><body>'.

# Title
dcPage::breadcrumb($breadcrumb).
dcPage::notices().

# Filters list
'<ul class="pseudo-tabs">'.
'<li>'.implode('</li><li>', $top_menu).'</li>'.
'</ul>';

# Filter content
if (isset($filters_id[$default_part])) {

	$name = $filters_id[$default_part];
	$filter = $_filters[$name];

	# Filter title and description
	echo '
	<h3>'.__($filters_id[$default_part]).'</h3>
	<p>'.$filter['help'].'</p>';

	# Filter settings
	echo '
	<div class="multi-part" id="setting" title="'.__('Settings').'">
	<form method="post" action="'.$p_url.'&amp;part='.$default_part.'&amp;tab=setting"><div>';

	echo 
	'<div class="two-boxes odd">
	<h4>'.__('Pages to be filtered').'</h4>';

	foreach(libEPC::blogAllowedPubPages() as $k => $v) {
		echo '
		<p><label for="filter_pubPages'.$v.'">'.
		form::checkbox(
			array('filter_pubPages[]', 'filter_pubPages'.$v),
			$v,
			in_array($v,$filter['pubPages'])
		).
		__($k).'</label></p>';
	}

	echo 
	'</div>';

	echo 
	'<div class="two-boxes even">
	<h4>'.__('Filtering').'</h4>

	<p><label for="filter_nocase">'.
	form::checkbox('filter_nocase', '1', $filter['nocase']).
	__('Case insensitive').'</label></p>

	<p><label for="filter_plural">'.
	form::checkbox('filter_plural', '1', $filter['plural']).
	__('Also use the plural').'</label></p>

	<p><label for="filter_limit">'.
	__('Limit the number of replacement to:').'</label>'.
	form::field('filter_limit', 4, 10, html::escapeHTML($filter['limit'])).'
	</p>
	<p class="form-note">'.__('Leave it blank or set it to 0 for no limit').'</p>

	</div>';

	echo 
	'<div class="two-boxes odd">
	<h4>'.__('Contents to be filtered').'</h4>';

	foreach(libEPC::blogAllowedTplValues() as $k => $v) {
		echo '
		<p><label for="filter_tplValues'.$v.'">'.
		form::checkbox(
			array('filter_tplValues[]', 'filter_tplValues'.$v),
			$v,
			in_array($v,$filter['tplValues'])
		).
		__($k).'</label></p>';
	}

	echo 
	'</div>';

	echo 
	'<div class="two-boxes even">
	<h4>'.__('Style').'</h4>';

	foreach($filter['class'] as $k => $v) {
		echo '
		<p><label for="filter_style'.$k.'">'.
		sprintf(__('Class "%s":'),$v).'</label>'.
		form::field(
			array('filter_style[]', 'filter_style'.$k),
			60,
			255,
			html::escapeHTML($filter['style'][$k])
		).
		'</p>';
	}

	echo '
	<p class="form-note">'.sprintf(__('The inserted HTML tag looks like: %s'), html::escapeHTML(str_replace('%s', '...', $filter['replace']))).'</p>

	<p><label for="filter_notag">'.__('Ignore HTML tags:').'</label>'.
	form::field('filter_notag', 60, 255, html::escapeHTML($filter['notag'])).'
	</p>
	<p class="form-note">'.__('This is the list of HTML tags where content will be ignored.').' '.
	(empty($filter['htmltag']) ? '' : sprintf(__('Tag "%s" always be ignored.'), $filter['htmltag'])).'</p>

	</div>';

	echo '</div>
	<div class="clear">
	<p>'.
	$core->formNonce().
	form::hidden(array('action'), 'savefiltersetting').'
	<input type="submit" name="save" value="'.__('Save').'" />
	</p>
	</div>

	</form>
	</div>';

	# Filter records list
	if ($filter['has_list']) {

		$sortby_combo = array(
			'epc_upddt',
			'epc_key',
			'epc_value',
			'epc_id'
		);

		$order_combo = array(
			'asc',
			'desc'
		);
	
		$sortby = !empty($_GET['sortby']) ? $_GET['sortby'] : (string) $s->enhancePostContent_list_sortby;
		$order = !empty($_GET['order']) ? $_GET['order'] : (string) $s->enhancePostContent_list_order;
		$page = !empty($_GET['page']) ? (integer) $_GET['page'] : 1;
		$nb = !empty($_GET['nb']) && (integer) $_GET['nb'] > 0 ? (integer) $_GET['nb'] : (integer) $s->enhancePostContent_list_nb;

		$params = array();
		$params['epc_filter'] = $name;
		$params['limit'] = array((($page-1)*$nb), $nb);

		if ($sortby !== '' && in_array($sortby, $sortby_combo)) {
			if ($order !== '' && in_array($order, $order_combo)) {
				$params['order'] = $sortby.' '.$order;
			}
		}

		try {
			$list = $records->getRecords($params);
			$counter = $records->getRecords($params, true);
		}
		catch (Exception $e) {
			$core->error->add($e->getMessage());
		}

		$pager_url = $p_url.
			'&amp;nb='.$nb.
			'&amp;sortby=%s'.
			'&amp;order='.($order == 'desc' ? 'desc' : 'asc').
			'&amp;page=%s'.
			'&amp;part='.$default_part.
			'#record';

		$pager = new pager($page, $counter->f(0), $nb, 10);
		$pager->html_prev = __('&#171;prev.');
		$pager->html_next = __('next&#187;');
		$pager->base_url = sprintf($pager_url, $sortby,'%s');
		$pager->var_page = 'page';

		echo '
		<div class="multi-part" id="record" title="'.__('Records').'">';

		if ($core->error->flag() || $list->isEmpty()) {
			echo '<p>'.__('No record').'</p>';
		}
		else {
			echo '
			<form action="'.$pager_url.'" method="post">
			<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>
			<table>
			<thead><tr>
			<th><a href="'.sprintf($pager_url, 'epc_key', $page).'">'
			.__('Key').'</a></th>
			<th><a href="'.sprintf($pager_url, 'epc_value', $page).'">'.
			__('Value').'</a></th>
			<th><a href="'.sprintf($pager_url, 'epc_upddt', $page).'">'.
			__('Date').'</a></th>
			</tr></thead>
			<tbody>';

			while($list->fetch()) {
				echo '
				<tr class="line">
				<td class="nowrap">'.
				form::hidden(array('epc_id[]'), $list->epc_id).
				form::hidden(array('epc_old_key[]'), html::escapeHTML($list->epc_key)).
				form::hidden(array('epc_old_value[]'), html::escapeHTML($list->epc_value)).
				form::field(array('epc_key[]'), 30, 225, html::escapeHTML($list->epc_key), '').'</td>
				<td class="maximal">'.
				form::field(array('epc_value[]'), 90, 225, html::escapeHTML($list->epc_value), 'maximal').'</td>
				<td class="nowrap">'.
				dt::dt2str(__('%Y-%m-%d %H:%M'), $list->epc_upddt,$core->auth->getInfo('user_tz')).'</td>
				</tr>';
			}

			echo '
			</tbody>
			</table>
			<p class="form-note">'.__('In order to remove a record, leave empty its key or value.').'</p>
			<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>

			<div class="clear">
			<p>'.
			$core->formNonce().
			form::hidden(array('redir'), sprintf($pager_url, $sortby, $page)).
			form::hidden(array('action'), 'saveupdaterecords').'
			<input type="submit" name="save" value="'.__('Save').'" />
			</p>
			</div>

			</form>';
		}

		echo '</div>';

		# New record
		echo '
		<div class="multi-part" id="newrecord" title="'.__('New record').'">
		<form action="'.$p_url.'&amp;part='.$default_part.'&amp;tab=setting" method="post">'.

		'<p><label for="new_key">'.__('Key:').'</label>'.
		form::field('new_key', 60, 255).
		'</p>'.

		'<p><label for="new_value">'.__('Value:').'</label>'.
		form::field('new_value', 60, 255).
		'</p>

		<p class="clear">'.
		form::hidden(array('action'), 'savenewrecord').
		$core->formNonce().'
		<input type="submit" name="save" value="'.__('Save').'" />
		</p>
		</form>
		</div>';
	}
}

# --BEHAVIOR-- enhancePostContentAdminPage
$core->callBehavior('enhancePostContentAdminPage',$core);

dcPage::helpBlock('enhancePostContent');

# Footers
echo 
'<hr class="clear"/><p class="right modules">
<a class="module-config" '.
'href="plugins.php?module=enhancePostContent&amp;conf=1&amp;redir='.
urlencode('plugin.php?p=enhancePostContent').'">'.__('Configuration').'</a> - 
enhancePostContent - '.$core->plugins->moduleInfo('enhancePostContent', 'version').'&nbsp;
<img alt="'.__('enhancePostContent').'" src="index.php?pf=enhancePostContent/icon.png" />
</p>
</body></html>';