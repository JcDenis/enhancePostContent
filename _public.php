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

require __DIR__ . '/_widgets.php';

dcCore::app()->blog->settings->addNamespace('enhancePostContent');

if (dcCore::app()->blog->settings->enhancePostContent->enhancePostContent_active) {
    dcCore::app()->addBehavior(
        'publicHeadContentV2',
        ['publicEnhancePostContent', 'publicHeadContent']
    );
    dcCore::app()->addBehavior(
        'publicBeforeContentFilterV2',
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
     */
    public static function publicHeadContent()
    {
        echo dcUtils::cssLoad(dcCore::app()->blog->url . dcCore::app()->url->getURLFor('epccss'));
    }

    public static function css($args)
    {
        $css     = [];
        $filters = libEPC::getFilters();

        foreach ($filters as $id => $filter) {
            if ('' == $filter->class || '' == $filter->style) {
                continue;
            }

            $res = '';
            foreach ($filter->class as $k => $class) {
                $styles = $filter->style;
                $style  = html::escapeHTML(trim($styles[$k]));
                if ('' != $style) {
                    $res .= $class . ' {' . $style . '} ';
                }
            }

            if (!empty($res)) {
                $css[] = '/* CSS for enhancePostContent ' . $id . " */ \n" . $res . "\n";
            }
        }

        header('Content-Type: text/css; charset=UTF-8');

        echo implode("\n", $css);

        exit;
    }

    /**
     * Filter template blocks content
     *
     * @param  string $tag  Tempalte block name
     * @param  array  $args Tempalte Block arguments
     */
    public static function publicContentFilter($tag, $args)
    {
        $filters = libEPC::getFilters();

        foreach ($filters as $id => $filter) {
            if (!libEPC::testContext($tag, $args, $filter)) {
                continue;
            }
            $filter->publicContent($tag, $args);
        }
    }
}
