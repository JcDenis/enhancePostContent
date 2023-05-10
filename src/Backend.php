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
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Input,
    Label,
    Para
};

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN')
            && !is_null(dcCore::app()->auth) && !is_null(dcCore::app()->blog)
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

        // backend sidebar menu icon
        dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
            My::name(),
            dcCore::app()->adminurl?->get('admin.plugin.' . My::id()),
            dcPage::getPF(My::id() . '/icon.svg'),
            preg_match('/' . preg_quote((string) dcCore::app()->adminurl?->get('admin.plugin.' . My::id())) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
            dcCore::app()->auth?->check(dcCore::app()->auth->makePermissions([dcCore::app()->auth::PERMISSION_CONTENT_ADMIN]), dcCore::app()->blog?->id)
        );

        dcCore::app()->addBehaviors([
            // backend user dashboard favorites icon
            'adminDashboardFavoritesV2' => function (dcFavorites $favs): void {
                $favs->register(My::id(), [
                    'title'       => My::name(),
                    'url'         => dcCore::app()->adminurl?->get('admin.plugin.' . My::id()),
                    'small-icon'  => dcPage::getPF(My::id() . '/icon.svg'),
                    'large-icon'  => dcPage::getPF(My::id() . '/icon.svg'),
                    'permissions' => dcCore::app()->auth?->makePermissions([dcCore::app()->auth::PERMISSION_CONTENT_ADMIN]),
                ]);
            },
            // backend user preference form
            'adminBlogPreferencesFormV2' => function (dcSettings $blog_settings): void {
                $active           = (bool) $blog_settings->get(My::id())->get('active');
                $allowedtplvalues = Epc::blogAllowedTemplateValue();
                $allowedpubpages  = Epc::blogAllowedTemplatePage();

                echo
                '<div class="fieldset"><h4 id="epc_params">' . My::name() . '</h4>' .
                '<div class="two-cols">' .
                '<div class="col">' .
                // active
                (new Para())->items([
                    (new Checkbox('epc_active', $active))->value(1),
                    (new Label(__('Enable plugin'), Label::OUTSIDE_LABEL_AFTER))->for('epc_active')->class('classic'),
                ])->render() .
                '<p class="form-note">' .
                __('This enable public widgets and contents filter.') .
                '</p>' .
                '<p><a href="' . dcCore::app()->adminurl?->get('admin.plugin.' . My::id()) . '">' .
                __('Set content filters') . '</a></p>' .
                '</div>' .
                '<div class="col">' .
                '<h5>' . __('Extra') . '</h5>' .
                '<p>' . __('This is a special feature to edit list of allowed template values and public pages where this plugin works.') . '</p>' .
                // allowedtplvalues
                (new Para())->items([
                    (new Label(__('Allowed DC template values:'), Label::OUTSIDE_LABEL_BEFORE))->for('epc_allowedtplvalues'),
                    (new Input('epc_allowedtplvalues'))->size(100)->maxlenght(0)->value(Epc::encodeMulti($allowedtplvalues)),
                ])->render() .
                '<p class="form-note">' . __('Use "readable_name1:template_value1;readable_name2:template_value2;" like "entry content:EntryContent;entry excerpt:EntryExcerpt;".') . '</p>' .
                // allowedpubpages
                (new Para())->items([
                    (new Label(__('Allowed public pages:'), Label::OUTSIDE_LABEL_BEFORE))->for('epc_allowedpubpages'),
                    (new Input('epc_allowedpubpages'))->size(100)->maxlenght(0)->value(Epc::encodeMulti($allowedpubpages)),
                ])->render() .
                '<p class="form-note">' . __('Use "readable_name1:template_page1;readable_name2:template_page2;" like "post page:post.html;home page:home.html;".') . '</p>' .
                '</div>' .
                '</div>' .
                '<br class="clear" />' .
                '</div>';
            },
            // backend user preference save
            'adminBeforeBlogSettingsUpdate' => function (dcSettings $blog_settings): void {
                $active           = !empty($_POST['epc_active']);
                $allowedtplvalues = Epc::decodeMulti($_POST['epc_allowedtplvalues']);
                $allowedpubpages  = Epc::decodeMulti($_POST['epc_allowedpubpages']);

                $blog_settings->get(My::id())->put('active', $active);
                $blog_settings->get(My::id())->put('allowedtplvalues', json_encode($allowedtplvalues));
                $blog_settings->get(My::id())->put('allowedpubpages', json_encode($allowedpubpages));
            },
            // backend epc list filter
            'adminFiltersListsV2' => function (ArrayObject $sorts): void {
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
            // widgets registration
            'initWidgets' => [Widgets::class, 'initWidgets'],
        ]);

        return true;
    }
}
