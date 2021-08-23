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

$dc_min = '2.6';
$mod_id = 'enhancePostContent';
$new_version = $core->plugins->moduleInfo($mod_id, 'version');
$old_version = $core->getVersion($mod_id);

if (version_compare($old_version, $new_version, '>=')) {

	return null;
}

try
{
	# Check Dotclear version
	if (!method_exists('dcUtils', 'versionsCompare') 
	 || dcUtils::versionsCompare(DC_VERSION, $dc_min, '<', false)) {
		throw new Exception(sprintf(
			'%s requires Dotclear %s', $mod_id, $dc_min
		));
	}
	
	# Database
	$s = new dbStruct($core->con, $core->prefix);
	$s->epc
		->epc_id ('bigint', 0, false)
		->blog_id ('varchar', 32, false)
		->epc_type('varchar', 32, false, "'epc'")
		->epc_filter('varchar', 64, false)
		->epc_key('varchar', 255, false)
		->epc_value('text', 0, false)
		->epc_upddt('timestamp', 0, false, 'now()')
		
		->primary('pk_epc', 'epc_id')
		->index('idx_epc_blog_id', 'btree', 'blog_id')
		->index('idx_epc_type', 'btree', 'epc_type')
		->index('idx_epc_filter', 'btree', 'epc_filter')
		->index('idx_epc_key', 'btree', 'epc_key');
	
	$si = new dbStruct($core->con, $core->prefix);
	$changes = $si->synchronize($s);
	$s = null;
	
	# Settings
	$core->blog->settings->addNamespace($mod_id);
	$s = $core->blog->settings->enhancePostContent;
	
	$s->put('enhancePostContent_active', false,'boolean', 'Enable enhancePostContent', false, true);
	$s->put('enhancePostContent_list_sortby', 'epc_key', 'string', 'Admin records list field order', false, true);
	$s->put('enhancePostContent_list_order', 'desc', 'string', 'Admin records list order', false, true);
	$s->put('enhancePostContent_list_nb', 20, 'integer', 'Admin records list nb per page', false, true);
	$s->put('enhancePostContent_allowedtplvalues', serialize(libEPC::defaultAllowedTplValues()), 'string', 'List of allowed template values', false, true);
	$s->put('enhancePostContent_allowedpubpages', serialize(libEPC::defaultAllowedPubPages()), 'string', 'List of allowed template pages', false, true);
	
	# Filters settings
	$filters = libEPC::defaultFilters();
	foreach($filters as $name => $filter) {
		# Only editable options
		$opt = array(
			'nocase'		=> $filter['nocase'],
			'plural'		=> $filter['plural'],
			'style'		=> $filter['style'],
			'notag'		=> $filter['notag'],
			'tplValues'	=> $filter['tplValues'],
			'pubPages'	=> $filter['pubPages']
		);
		$s->put('enhancePostContent_'.$name, serialize($opt), 'string', 'Settings for '.$name, false, true);
		# only tables
		if (isset($filter['list'])) {
			$s->put('enhancePostContent_'.$name.'List', serialize($filter['list']), 'string', 'List for '.$name, false, true);
		}
	}

	# Move old filters lists from settings to database
	if ($old_version && version_compare('0.6.6', $old_version, '>=')) {
		include_once dirname(__FILE__).'/inc/lib.epc.update.php';
	}

	# Version
	$core->setVersion($mod_id, $new_version);

	return true;
}
catch (Exception $e) {
	$core->error->add($e->getMessage());
}

return false;
