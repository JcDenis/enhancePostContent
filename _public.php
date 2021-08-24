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

if (!defined('DC_RC_PATH')) {
    return null;
}

require dirname(__FILE__) . '/_widgets.php';

$core->blog->settings->addNamespace('enhancePostContent');

if ($core->blog->settings->enhancePostContent->enhancePostContent_active) {

    $core->addBehavior(
        'publicHeadContent',
        ['publicEnhancePostContent', 'publicHeadContent']
    );
    $core->addBehavior(
        'publicBeforeContentFilter',
        ['publicEnhancePostContent', 'publicContentFilter']
    );
}

/**
 * @ingroup DC_PLUGIN_ENHANCEPOSTCONTENT
 * @brief Filter posts content - public methods.
 * @since 2.6
 */
class publicEnhancePostContent
{
    /**
     * Add filters CSS to page header
     * 
     * @param  dcCore $core dcCore instance
     */
    public static function publicHeadContent(dcCore $core)
    {
        $filters = libEPC::blogFilters();

        foreach($filters as $name => $filter) {

            if (empty($filter['class']) 
             || empty($filter['style'])) {
                continue;
            }

            $res = '';
            foreach($filter['class'] as $k => $class) {
                $style = html::escapeHTML(trim($filter['style'][$k]));
                if ('' != $style) {
                    $res .= $class . " {" . $style . "} ";
                }
            }

            if (!empty($res)) {
                echo 
                "\n<!-- CSS for enhancePostContent " . $name . " --> \n" .
                "<style type=\"text/css\"> " . $res . "</style> \n";
            }
        }
    }

    /**
     * Filter template blocks content
     * 
     * @param  dcCore $core dcCore instance
     * @param  string $tag  Tempalte block name
     * @param  array  $args Tempalte Block arguments
     */
    public static function publicContentFilter(dcCore $core, $tag, $args)
    {
        $filters = libEPC::blogFilters();
        $records = new epcRecords($core);

        foreach($filters as $name => $filter) {

            if (!isset($filter['publicContentFilter'])
             || !is_callable($filter['publicContentFilter']) 
             || !libEPC::testContext($tag,$args,$filter)) {
                continue;
            }

            if ($filter['has_list']) {
                $filter['list'] = $records->getRecords(array(
                    'epc_filter' => $name)
                );
                if ($filter['list']->isEmpty()) {
                    continue;
                }
            }

            call_user_func_array(
                $filter['publicContentFilter'],
                [$core, $filter, $tag, $args]
            );
        }
    }
}