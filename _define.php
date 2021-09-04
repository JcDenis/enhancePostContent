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

if (!defined('DC_RC_PATH')) {
    return null;
}

$this->registerModule(
    'Enhance post content',
    'Add features to words in post content',
    'Jean-Christian Denis and Contributors',
    '2021.09.05.1',
    [
        'requires' => [['core', '2.19']],
        'permissions' => 'contentadmin',
        'type' => 'plugin',
        'support' => 'https://github.com/JcDenis/enhancePostContent',
        'details' => 'https://plugins.dotaddict.org/dc2/details/enhancePostContent',
        'repository' => 'https://raw.githubusercontent.com/JcDenis/enhancePostContent/master/dcstore.xml',
        'settings' => [
            'blog' => '#params.epc_params'
        ]
    ]
);