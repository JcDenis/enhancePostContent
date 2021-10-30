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
        echo dcUtils::cssLoad($core->blog->url . $core->url->getURLFor('epccss'));
    }

    public static function css($args)
    {
        $css = [];
        $filters = libEPC::blogFilters();

        foreach($filters as $name => $filter) {
            if (empty($filter['class']) || empty($filter['style'])) {
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
                $css[] = 
                "/* CSS for enhancePostContent " . $name . " */ \n" . $res . "\n";
            }
        }

        header('Content-Type: text/css; charset=UTF-8');

        echo implode("\n", $css);

        exit;
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