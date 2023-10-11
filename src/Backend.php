<?php

declare(strict_types=1);

namespace Dotclear\Plugin\enhancePostContent;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\BlogSettings;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Input,
    Label,
    Link,
    Note,
    Para,
    Text
};

/**
 * @brief   enhancePostContent backend class.
 * @ingroup enhancePostContent
 *
 * @author      Jean-Christian Denis
 * @copyright   Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem();

        App::behavior()->addBehaviors([
            // backend user dashboard favorites icon
            'adminDashboardFavoritesV2' => function (Favorites $favs): void {
                $favs->register(My::id(), [
                    'title'       => My::name(),
                    'url'         => My::manageUrl(),
                    'small-icon'  => My::icons(),
                    'large-icon'  => My::icons(),
                    'permissions' => App::auth()->makePermissions([App::auth()::PERMISSION_CONTENT_ADMIN]),
                ]);
            },
            // backend user preference form
            'adminBlogPreferencesFormV2' => function (BlogSettings $blog_settings): void {
                $active           = (bool) $blog_settings->get(My::id())->get('active');
                $allowedtplvalues = Epc::blogAllowedTemplateValue();
                $allowedpubpages  = Epc::blogAllowedTemplatePage();

                echo
                (new Div())
                    ->class('fieldset')
                    ->items([
                        (new Text('h4', My::name()))
                            ->id('epc_params'),
                        (new Div())
                            ->class('two-cols')
                            ->items([
                                (new Div())
                                    ->class('col')
                                    ->items([
                                        // active
                                        (new Para())
                                            ->items([
                                                (new Checkbox('epc_active', $active))
                                                    ->value(1),
                                                (new Label(__('Enable plugin'), Label::OUTSIDE_LABEL_AFTER))
                                                    ->for('epc_active')
                                                    ->class('classic'),
                                            ]),
                                        (new Note())
                                            ->class('form-note')
                                            ->text(__('This enable public widgets and contents filter.')),
                                        (new Para())
                                            ->items([
                                                (new Link())
                                                    ->href(My::manageUrl())
                                                    ->text(__('Set content filters')),
                                            ]),
                                    ]),
                                (new Div())
                                    ->class('col')
                                    ->items([
                                        (new Text('h5', __('Extra'))),
                                        (new Para())
                                            ->text(__('This is a special feature to edit list of allowed template values and public pages where this plugin works.')),
                                        // allowedtplvalues
                                        (new Para())->items([
                                            (new Label(__('Allowed DC template values:'), Label::OUTSIDE_LABEL_BEFORE))->for('epc_allowedtplvalues'),
                                            (new Input('epc_allowedtplvalues'))->size(100)->maxlenght(0)->value(Epc::encodeMulti($allowedtplvalues)),
                                        ]),
                                        (new Note())
                                            ->class('form-note')
                                            ->text(__('Use "readable_name1:template_value1;readable_name2:template_value2;" like "entry content:EntryContent;entry excerpt:EntryExcerpt;".')),
                                        // allowedpubpages
                                        (new Para())->items([
                                            (new Label(__('Allowed public pages:'), Label::OUTSIDE_LABEL_BEFORE))->for('epc_allowedpubpages'),
                                            (new Input('epc_allowedpubpages'))->size(100)->maxlenght(0)->value(Epc::encodeMulti($allowedpubpages)),
                                        ]),
                                        (new Note())
                                            ->class('form-note')
                                            ->text(__('Use "readable_name1:template_page1;readable_name2:template_page2;" like "post page:post.html;home page:home.html;".')),
                                    ]),
                            ]),
                        (new Text('br'))
                            ->class('clear'),
                    ])
                    ->render();
            },
            // backend user preference save
            'adminBeforeBlogSettingsUpdate' => function (BlogSettings $blog_settings): void {
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
