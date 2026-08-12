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
declare(strict_types=1);

if (!isset($this) || !is_object($this) || !method_exists($this, 'registerModule') || !isset($this->id) || !is_string($this->id)) {
    return;
}

$this->registerModule(
    'Enhance post content',
    'Add features to words in post content',
    'Jean-Christian Denis and Contributors',
    '2026.08.12',
    [
        'type'        => 'plugin',
        'requires'    => [['core', '2.39']],
        'permissions' => 'My',
        'settings'    => ['blog' => '#params.' . $this->id . '_params'],
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2026-08-12T20:57:35+00:00',
    ]
);
