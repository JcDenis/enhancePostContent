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

$core->blog->settings->addNamespace('enhancePostContent');

require dirname(__FILE__) . '/_widgets.php';

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
    ['epcAdminBehaviors', 'adminDashboardFavorites']
);
$core->addBehavior(
    'adminBlogPreferencesForm',
    ['epcAdminBehaviors', 'adminBlogPreferencesForm']
);
$core->addBehavior(
    'adminBeforeBlogSettingsUpdate',
    ['epcAdminBehaviors', 'adminBeforeBlogSettingsUpdate']
);

class epcAdminBehaviors
{
    public static function adminDashboardFavorites($core, $favs)
    {
        $favs->register('enhancePostContent', [
            'title' => __('Enhance post content'),
            'url' => 'plugin.php?p=enhancePostContent',
            'small-icon' => 'index.php?pf=enhancePostContent/icon.png',
            'large-icon' => 'index.php?pf=enhancePostContent/icon-big.png',
            'permissions' => $core->auth->check('contentadmin', $core->blog->id),
            'active_cb' => [
                'epcAdminBehaviors', 
                'adminDashboardFavoritesActive'
            ]
        ]);
    }

    public static function adminDashboardFavoritesActive($request, $params)
    {
        return $request == 'plugin.php' 
            && isset($params['p']) 
            && $params['p'] == 'enhancePostContent';
    }

    public static function sortbyCombo()
    {
        return [
            __('Date') => 'epc_upddt',
            __('Key') => 'epc_key',
            __('Value') => 'epc_value',
            __('ID') => 'epc_id'
        ];
    }

    public static function orderCombo()
    {
        return [
            __('Ascending') => 'asc',
            __('Descending') => 'desc'
        ];
    }

    public static function adminBlogPreferencesForm(dcCore $core, dcSettings $blog_settings)
    {
        $active         = (boolean) $blog_settings->enhancePostContent->enhancePostContent_active;
        $list_sortby        = (string) $blog_settings->enhancePostContent->enhancePostContent_list_sortby;
        $list_order     = (string) $blog_settings->enhancePostContent->enhancePostContent_list_order;
        $list_nb            = (integer) $blog_settings->enhancePostContent->enhancePostContent_list_nb;
        $_filters           = libEPC::blogFilters();
        $allowedtplvalues   = libEPC::blogAllowedTplValues();
        $allowedpubpages    = libEPC::blogAllowedPubPages();

        echo
        '<div class="fieldset"><h4 id="fac_params">' . __('Enhance post content') .'</h4>' .
        '<div class="two-cols">' .
        '<div class="col">' .
        '<p><label class="classic">' .
        form::checkbox('epc_active', '1', $active) . 
        __('Enable plugin') . '</label></p>' .
        '<p class="form-note">' .
        __('This enable public widgets and contents filter.') .
        '</p>' .
        '<h5>' . __('Record list') . '</h4>' .
        '<p class="form-note">' . __('This is the default order of records lists.') . '</p>' .
        '<p><label for="epc_list_sortby">' . __('Order by:') . '</label>' .
        form::combo('epc_list_sortby', self::sortbyCombo(), $list_sortby) . '</p>' .
        '<p><label for="epc_list_order">' . __('Sort:') . '</label>' .
        form::combo('epc_list_order', self::orderCombo(), $list_order) . '</p>' .
        '<p><label for="list_nb">' . __('Records per page:') . '</label>' .
        form::field('epc_list_nb', 3, 3, $list_nb) . '</p>' .
        '</div>' .
        '<div class="col">' .
        '<h5>' . __('Extra') . '</h5>' .
        '<p>' . __('This is a special feature to edit list of allowed template values and public pages where this plugin works.') . '</p>' .
        '<p><label for="epc_allowedtplvalues">' . __('Allowed DC template values:') . '</label>' .
        form::field('epc_allowedtplvalues', 100, 0, libEPC::implode($allowedtplvalues)) . '</p>' .
        '<p class="form-note">' . __('Use "readable_name1:template_value1;readable_name2:template_value2;" like "entry content:EntryContent;entry excerpt:EntryExcerpt;".') . '</p>' .
        '<p><label for="epc_allowedpubpages">' . __('Allowed public pages:') . '</label>' .
        form::field('epc_allowedpubpages', 100, 0, libEPC::implode($allowedpubpages)) . '</p>' .
        '<p class="form-note">' . __('Use "readable_name1:template_page1;readable_name2:template_page2;" like "post page:post.html;home page:home.html;".') . '</p>' .
        '</div>' .
        '</div>' .
        '<br class="clear" />' . 
        '</div>';
    }

    public static function adminBeforeBlogSettingsUpdate(dcSettings $blog_settings)
    {
        $active = !empty($_POST['epc_active']);
        $list_sortby = in_array($_POST['epc_list_sortby'], self::sortbyCombo()) ? $_POST['epc_list_sortby'] : 'epc_id';
        $list_order = in_array($_POST['epc_list_order'], self::orderCombo()) ? $_POST['epc_list_order'] : 'desc';
        $list_nb = isset($_POST['epc_list_nb']) && $_POST['epc_list_nb'] > 0 ? $_POST['epc_list_nb'] : 20;
        $allowedtplvalues = libEPC::explode($_POST['epc_allowedtplvalues']);
        $allowedpubpages = libEPC::explode($_POST['epc_allowedpubpages']);

        $blog_settings->enhancePostContent->put('enhancePostContent_active', $active);
        $blog_settings->enhancePostContent->put('enhancePostContent_list_sortby', $list_sortby);
        $blog_settings->enhancePostContent->put('enhancePostContent_list_order', $list_order);
        $blog_settings->enhancePostContent->put('enhancePostContent_list_nb', $list_nb);
        $blog_settings->enhancePostContent->put('enhancePostContent_allowedtplvalues', serialize($allowedtplvalues));
        $blog_settings->enhancePostContent->put('enhancePostContent_allowedpubpages', serialize($allowedpubpages));
    }
}