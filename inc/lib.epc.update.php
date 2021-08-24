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

# This file only update old filters lists from settings to database

$f = $core->con->select("SELECT * FROM ".$core->prefix."setting WHERE setting_ns='enhancePostContent' AND blog_id IS NOT NULL ");

while ($f->fetch())
{
	if (preg_match('#enhancePostContent_(.*?)List#',$f->setting_id,$m))
	{
		$curlist = @unserialize($f->setting_value);
		if (is_array($curlist))
		{
			foreach($curlist as $k => $v)
			{
				$cur = $core->con->openCursor($core->prefix.'epc');
				$core->con->writeLock($core->prefix.'epc');

				$cur->epc_id = $core->con->select('SELECT MAX(epc_id) FROM '.$core->prefix.'epc'.' ')->f(0) + 1;
				$cur->blog_id = $f->blog_id;
				$cur->epc_filter = $m[1];
				$cur->epc_key = $k;
				$cur->epc_value = $v;

				$cur->insert();
				$core->con->unlock();
			}
		}
		$core->con->execute("DELETE FROM ".$core->prefix."setting WHERE setting_id='".$f->setting_id."' AND setting_ns='enhancePostContent' AND blog_id='".$f->blog_id."' ");
	}
}
?>