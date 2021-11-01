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
$_menu['Plugins']->addItem(
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
$core->addBehavior(
    'adminFiltersLists',
    ['epcAdminBehaviors', 'adminFiltersLists']
);

class epcAdminBehaviors
{
    public static function adminDashboardFavorites($core, $favs)
    {
        $favs->register('enhancePostContent', [
            'title'       => __('Enhance post content'),
            'url'         => 'plugin.php?p=enhancePostContent',
            'small-icon'  => 'index.php?pf=enhancePostContent/icon.png',
            'large-icon'  => 'index.php?pf=enhancePostContent/icon-big.png',
            'permissions' => $core->auth->check('contentadmin', $core->blog->id),
            'active_cb'   => [
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
            __('Date')  => 'epc_upddt',
            __('Key')   => 'epc_key',
            __('Value') => 'epc_value',
            __('ID')    => 'epc_id'
        ];
    }

    public static function adminBlogPreferencesForm(dcCore $core, dcSettings $blog_settings)
    {
        $active           = (boolean) $blog_settings->enhancePostContent->enhancePostContent_active;
        $allowedtplvalues = libEPC::blogAllowedTplValues();
        $allowedpubpages  = libEPC::blogAllowedPubPages();

        echo
        '<div class="fieldset"><h4 id="epc_params">' . __('Enhance post content') . '</h4>' .
        '<div class="two-cols">' .
        '<div class="col">' .
        '<p><label class="classic">' .
        form::checkbox('epc_active', '1', $active) .
        __('Enable plugin') . '</label></p>' .
        '<p class="form-note">' .
        __('This enable public widgets and contents filter.') .
        '</p>' .
        '<p><a href="' . $core->adminurl->get('admin.plugin.enhancePostContent') . '">' .
        __('Set content filters') . '</a></p>' .
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
        $active           = !empty($_POST['epc_active']);
        $allowedtplvalues = libEPC::explode($_POST['epc_allowedtplvalues']);
        $allowedpubpages  = libEPC::explode($_POST['epc_allowedpubpages']);

        $blog_settings->enhancePostContent->put('enhancePostContent_active', $active);
        $blog_settings->enhancePostContent->put('enhancePostContent_allowedtplvalues', serialize($allowedtplvalues));
        $blog_settings->enhancePostContent->put('enhancePostContent_allowedpubpages', serialize($allowedpubpages));
    }

    public static function adminFiltersLists(dcCore $core, $sorts)
    {
        $sorts['epc'] = [
            __('Enhance post content'),
            self::sortbyCombo(),
            'epc_upddt',
            'desc',
            [__('records per page'), 20]
        ];
    }
}
