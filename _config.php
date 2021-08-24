<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of enhancePostContent, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2021 Jean-Christian Denis and contributors
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_CONTEXT_MODULE')) {

	return null;
}

$redir = empty($_REQUEST['redir']) ? 
	$list->getURL().'#plugins' : $_REQUEST['redir'];

# -- Form combos --
$sortby_combo = array(
	__('Date')	=> 'epc_upddt',
	__('Key')		=> 'epc_key',
	__('Value')	=> 'epc_value',
	__('ID')		=> 'epc_id'
);

$order_combo = array(
	__('Ascending')	=> 'asc',
	__('Descending')	=> 'desc'
);

# -- Get settings --
$core->blog->settings->addNamespace('enhancePostContent');
$s = $core->blog->settings->enhancePostContent;
$active			= (boolean) $s->enhancePostContent_active;
$list_sortby		= (string) $s->enhancePostContent_list_sortby;
$list_order		= (string) $s->enhancePostContent_list_order;
$list_nb			= (integer) $s->enhancePostContent_list_nb;
$_filters			= libEPC::blogFilters();
$allowedtplvalues	= libEPC::blogAllowedTplValues();
$allowedpubpages	= libEPC::blogAllowedPubPages();

# -- Set settings --
if (!empty($_POST['save'])) {

	try {
		$active = !empty($_POST['active']);
		$list_sortby = in_array($_POST['list_sortby'], $sortby_combo) ? 
			$_POST['list_sortby'] : 'epc_id';
		$list_order = in_array($_POST['list_order'], $order_combo) ? 
			$_POST['list_order'] : 'desc';
		$list_nb = isset($_POST['list_nb']) && $_POST['list_nb'] > 0 ? 
			$_POST['list_nb'] : 20;

		$s->put('enhancePostContent_active',		$active);
		$s->put('enhancePostContent_list_sortby',	$list_sortby);
		$s->put('enhancePostContent_list_order',	$list_order);
		$s->put('enhancePostContent_list_nb',		$list_nb);

		$allowedtplvalues	= libEPC::explode($_POST['allowedtplvalues']);
		$allowedpubpages	= libEPC::explode($_POST['allowedpubpages']);

		$s->put(
			'enhancePostContent_allowedtplvalues',
			serialize($allowedtplvalues)
		);
		$s->put(
			'enhancePostContent_allowedpubpages',
			serialize($allowedpubpages)
		);

		$core->blog->triggerBlog();

		dcPage::addSuccessNotice(
			__('Configuration has been successfully updated.')
		);
		http::redirect(
			$list->getURL('module=enhancePostContent&conf=1&redir='.
			$list->getRedir())
		);
	}
	catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

# -- Display form --
echo '

<div class="fieldset">
<h4>'.__('General').'</h4>
<p>'.__('This enable public widgets and contents filter.').'</p>

<p><label class="classic" for="active">'.
form::checkbox('active', 1, $active).
__('Enable plugin').'</label></p>

</div>

<div class="fieldset">
<h4>'.__('Record list').'</h4>
<p>'.__('This is the default order of records lists.').'</p>

<p><label for="list_sortby">'.__('Order by:').'</label>'.
form::combo('list_sortby', $sortby_combo, $list_sortby).'</p>

<p><label for="list_order">'.__('Sort:').'</label>'.
form::combo('list_order', $order_combo, $list_order).'</p>

<p><label for="list_nb">'.__('Records per page:').'</label>'.
form::field('list_nb', 3, 3, $list_nb).'</p>

</div>

<div class="fieldset">
<h4>'.__('Extra').'</h4>
<p>'.__('This is a special feature to edit list of allowed template values and public pages where this plugin works.').'</p>

<p><label for="allowedtplvalues">'.__('Allowed DC template values:').'</label>'.
form::field('allowedtplvalues', 100,0, libEPC::implode($allowedtplvalues)).'</p>
<p class="form-note">'.__('Use "readable_name1:template_value1;readable_name2:template_value2;" like "entry content:EntryContent;entry excerpt:EntryExcerpt;".').'</p>

<p><label for="allowedpubpages">'.__('Allowed public pages:').'</label>'.
form::field('allowedpubpages', 100, 0, libEPC::implode($allowedpubpages)).'</p>
<p class="form-note">'.__('Use "readable_name1:template_page1;readable_name2:template_page2;" like "post page:post.html;home page:home.html;".').'</p>

</div>';
