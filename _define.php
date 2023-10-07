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

use Dotclear\App;

$this->registerModule(
    'Enhance post content',
    'Add features to words in post content',
    'Jean-Christian Denis and Contributors',
    '2023.08.14',
    [
        'requires' => [
            ['php', '8.1'],
            ['core', '2.28'],
        ],
        'permissions' => App::auth()->makePermissions([
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]),
        'settings' => [
            'blog' => '#params.epc_params',
        ],
        'type'       => 'plugin',
        'support'    => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/issues',
        'details'    => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/src/branch/master/README.md',
        'repository' => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/raw/branch/master/dcstore.xml',
    ]
);
