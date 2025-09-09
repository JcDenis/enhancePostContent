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
    '2025.09.09',
    [
        'type'        => 'plugin',
        'requires'    => [['core', '2.36']],
        'permissions' => 'My',
        'settings'    => ['blog' => '#params.' . $this->id . '_params'],
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-06-25T22:37:29+00:00',
    ]
);
