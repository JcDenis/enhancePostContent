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

dcCore::app()->blog->settings->addNamespace(basename(__DIR__));

require __DIR__ . '/_widgets.php';

# Admin menu
dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
    __('Enhance post content'),
    dcCore::app()->adminurl->get('admin.plugin.' . basename(__DIR__)),
    urldecode(dcPage::getPF(basename(__DIR__) . '/icon.svg')),
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.' . basename(__DIR__))) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([dcAuth::PERMISSION_CONTENT_ADMIN]), dcCore::app()->blog->id)
);

# Dashboard favorites
dcCore::app()->addBehavior('adminDashboardFavoritesV2', function (dcFavorites $favs) {
    $favs->register(basename(__DIR__), [
        'title'       => __('Enhance post content'),
        'url'         => dcCore::app()->adminurl->get('admin.plugin.' . basename(__DIR__)),
        'small-icon'  => urldecode(dcPage::getPF(basename(__DIR__) . '/icon.svg')),
        'large-icon'  => urldecode(dcPage::getPF(basename(__DIR__) . '/icon.svg')),
        'permissions' => dcCore::app()->auth->makePermissions([dcAuth::PERMISSION_CONTENT_ADMIN]),
    ]);
});

# Preference form
dcCore::app()->addBehavior('adminBlogPreferencesFormV2', function (dcSettings $blog_settings) {
    $active           = (bool) $blog_settings->get(basename(__DIR__))->get('active');
    $allowedtplvalues = enhancePostContent::blogAllowedTplValues();
    $allowedpubpages  = enhancePostContent::blogAllowedPubPages();

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
    '<p><a href="' . dcCore::app()->adminurl->get('admin.plugin.' . basename(__DIR__)) . '">' .
    __('Set content filters') . '</a></p>' .
    '</div>' .
    '<div class="col">' .
    '<h5>' . __('Extra') . '</h5>' .
    '<p>' . __('This is a special feature to edit list of allowed template values and public pages where this plugin works.') . '</p>' .
    '<p><label for="epc_allowedtplvalues">' . __('Allowed DC template values:') . '</label>' .
    form::field('epc_allowedtplvalues', 100, 0, enhancePostContent::implode($allowedtplvalues)) . '</p>' .
    '<p class="form-note">' . __('Use "readable_name1:template_value1;readable_name2:template_value2;" like "entry content:EntryContent;entry excerpt:EntryExcerpt;".') . '</p>' .
    '<p><label for="epc_allowedpubpages">' . __('Allowed public pages:') . '</label>' .
    form::field('epc_allowedpubpages', 100, 0, enhancePostContent::implode($allowedpubpages)) . '</p>' .
    '<p class="form-note">' . __('Use "readable_name1:template_page1;readable_name2:template_page2;" like "post page:post.html;home page:home.html;".') . '</p>' .
    '</div>' .
    '</div>' .
    '<br class="clear" />' .
    '</div>';
});

# Save preference
dcCore::app()->addBehavior('adminBeforeBlogSettingsUpdate', function (dcSettings $blog_settings) {
    $active           = !empty($_POST['epc_active']);
    $allowedtplvalues = enhancePostContent::explode($_POST['epc_allowedtplvalues']);
    $allowedpubpages  = enhancePostContent::explode($_POST['epc_allowedpubpages']);

    $blog_settings->get(basename(__DIR__))->put('active', $active);
    $blog_settings->get(basename(__DIR__))->put('allowedtplvalues', json_encode($allowedtplvalues));
    $blog_settings->get(basename(__DIR__))->put('allowedpubpages', json_encode($allowedpubpages));
});

# List filter
dcCore::app()->addBehavior('adminFiltersListsV2', function (ArrayObject $sorts) {
    $sorts['epc'] = [
        __('Enhance post content'),
        [
            __('Date')  => 'epc_upddt',
            __('Key')   => 'epc_key',
            __('Value') => 'epc_value',
            __('ID')    => 'epc_id',
        ],
        'epc_upddt',
        'desc',
        [__('records per page'), 20],
    ];
});
