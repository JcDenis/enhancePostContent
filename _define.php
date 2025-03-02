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
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
$this->registerModule(
    'Enhance post content',
    'Add features to words in post content',
    'Jean-Christian Denis and Contributors',
    '2025.03.02',
    [
        'type'        => 'plugin',
        'requires'    => [['core', '2.28']],
        'permissions' => 'My',
        'settings'    => [
            'self' => '',
            'blog' => '#params.epc_params',
        ],
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-03-02T11:25:07+00:00',
    ]
);
