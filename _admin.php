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

require dirname(__FILE__).'/_widgets.php';

# Admin menu
$_menu['Blog']->addItem(
	__('Enhance post content'),
	'plugin.php?p=enhancePostContent',
	'index.php?pf=enhancePostContent/icon.png',
	preg_match(
		'/plugin.php\?p=enhancePostContent(&.*)?$/',
		$_SERVER['REQUEST_URI']
	),
	$core->auth->check('contentadmin', $core->blog->id)
);

$core->addBehavior(
	'adminDashboardFavorites',
	array('epcAdminBehaviors', 'adminDashboardFavorites')
);

class epcAdminBehaviors
{
	public static function adminDashboardFavorites($core, $favs)
	{
		$favs->register('enhancePostContent', array(
			'title'		=> __('Enhance post content'),
			'url'		=> 'plugin.php?p=enhancePostContent',
			'small-icon'	=> 'index.php?pf=enhancePostContent/icon.png',
			'large-icon'	=> 'index.php?pf=enhancePostContent/icon-big.png',
			'permissions'	=> $core->auth->check('contentadmin', $core->blog->id),
			'active_cb'	=> array(
				'epcAdminBehaviors', 
				'adminDashboardFavoritesActive'
			)
		));
	}

	public static function adminDashboardFavoritesActive($request, $params)
	{
		return $request == 'plugin.php' 
			&& isset($params['p']) 
			&& $params['p'] == 'enhancePostContent';
	}
}
