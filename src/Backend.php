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
declare(strict_types=1);

namespace Dotclear\Plugin\enhancePostContent;

use ArrayObject;
use dcAdmin;
use dcCore;
use dcPage;
use dcFavorites;
use dcNsProcess;
use dcSettings;

use form;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN')
            && My::phpCompliant()
            && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id);

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        # Admin menu
        dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
            My::name(),
            dcCore::app()->adminurl->get('admin.plugin.' . My::id()),
            dcPage::getPF(My::id() . '/icon.svg'),
            preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.' . My::id())) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
            dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([dcCore::app()->auth::PERMISSION_CONTENT_ADMIN]), dcCore::app()->blog->id)
        );

        dcCore::app()->addBehaviors([
            # Dashboard favorites
            'adminDashboardFavoritesV2'     => function (dcFavorites $favs): void {
                $favs->register(My::id(), [
                    'title'       => My::name(),
                    'url'         => dcCore::app()->adminurl->get('admin.plugin.' . My::id()),
                    'small-icon'  => dcPage::getPF(My::id() . '/icon.svg'),
                    'large-icon'  => dcPage::getPF(My::id() . '/icon.svg'),
                    'permissions' => dcCore::app()->auth->makePermissions([dcCore::app()->auth::PERMISSION_CONTENT_ADMIN]),
                ]);
            },
            # Preference form
            'adminBlogPreferencesFormV2'    => function (dcSettings $blog_settings):void {
                $active           = (bool) $blog_settings->get(My::id())->get('active');
                $allowedtplvalues = Epc::blogAllowedTplValues();
                $allowedpubpages  = Epc::blogAllowedPubPages();

                echo
                '<div class="fieldset"><h4 id="epc_params">' . My::name() . '</h4>' .
                '<div class="two-cols">' .
                '<div class="col">' .
                '<p><label class="classic">' .
                form::checkbox('epc_active', '1', $active) .
                __('Enable plugin') . '</label></p>' .
                '<p class="form-note">' .
                __('This enable public widgets and contents filter.') .
                '</p>' .
                '<p><a href="' . dcCore::app()->adminurl->get('admin.plugin.' . My::id()) . '">' .
                __('Set content filters') . '</a></p>' .
                '</div>' .
                '<div class="col">' .
                '<h5>' . __('Extra') . '</h5>' .
                '<p>' . __('This is a special feature to edit list of allowed template values and public pages where this plugin works.') . '</p>' .
                '<p><label for="epc_allowedtplvalues">' . __('Allowed DC template values:') . '</label>' .
                form::field('epc_allowedtplvalues', 100, 0, Epc::implode($allowedtplvalues)) . '</p>' .
                '<p class="form-note">' . __('Use "readable_name1:template_value1;readable_name2:template_value2;" like "entry content:EntryContent;entry excerpt:EntryExcerpt;".') . '</p>' .
                '<p><label for="epc_allowedpubpages">' . __('Allowed public pages:') . '</label>' .
                form::field('epc_allowedpubpages', 100, 0, Epc::implode($allowedpubpages)) . '</p>' .
                '<p class="form-note">' . __('Use "readable_name1:template_page1;readable_name2:template_page2;" like "post page:post.html;home page:home.html;".') . '</p>' .
                '</div>' .
                '</div>' .
                '<br class="clear" />' .
                '</div>';
            },
            # Save preference
            'adminBeforeBlogSettingsUpdate' => function (dcSettings $blog_settings): void {
                $active           = !empty($_POST['epc_active']);
                $allowedtplvalues = Epc::explode($_POST['epc_allowedtplvalues']);
                $allowedpubpages  = Epc::explode($_POST['epc_allowedpubpages']);

                $blog_settings->get(My::id())->put('active', $active);
                $blog_settings->get(My::id())->put('allowedtplvalues', json_encode($allowedtplvalues));
                $blog_settings->get(My::id())->put('allowedpubpages', json_encode($allowedpubpages));
            },
            # List filter
            'adminFiltersListsV2'           => function (ArrayObject $sorts): void {
                $sorts['epc'] = [
                    My::name(),
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
            },
            # Widgets
            'initWidgets'                   => [Widgets::class, 'initWidgets'],
        ]);

        return true;
    }
}
