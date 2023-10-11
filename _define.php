<?php
/**
 * @file
 * @brief       The plugin enhancePostContent definition
 * @ingroup     enhancePostContent
 *
 * @defgroup    enhancePostContent Plugin enhancePostContent.
 *
 * Add features to words in post content.
 *
 * @author      Jean-Christian Denis
 * @copyright   Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
$this->registerModule(
    'Enhance post content',
    'Add features to words in post content',
    'Jean-Christian Denis and Contributors',
    '2023.10.11',
    [
        'type'        => 'plugin',
        'requires'    => [['core', '2.28']],
        'permissions' => 'My',
        'settings'    => [
            'self' => '',
            'blog' => '#params.epc_params',
        ],
        'support'    => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/issues',
        'details'    => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/src/branch/master/README.md',
        'repository' => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/raw/branch/master/dcstore.xml',
    ]
);
