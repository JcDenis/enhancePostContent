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

use dcCore;
use dcNsProcess;
use Dotclear\Helper\Html\Html;

class Prepend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = My::phpCompliant();

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        // register epc filters
        dcCore::app()->addBehavior('enhancePostContentFilters', function (EpcFilters $stack): void {
            foreach (My::DEFAULT_FILTERS as $class) {
                $stack->add(new $class());
            }
        });

        // register epc filters frontend css
        dcCore::app()->url->register(
            'epccss',
            'epc.css',
            '^epc\.css',
            function (string $args): void {
                $css = [];
                foreach (Epc::getFilters()->dump() as $filter) {
                    if ('' == $filter->class || '' == $filter->style) {
                        continue;
                    }

                    $res = '';
                    foreach ($filter->class as $k => $class) {
                        $styles = $filter->style;
                        $style  = Html::escapeHTML(trim($styles[$k]));
                        if ('' != $style) {
                            $res .= $class . ' {' . $style . '} ';
                        }
                    }

                    if (!empty($res)) {
                        $css[] = '/* CSS for enhancePostContent ' . $filter->id() . " */ \n" . $res . "\n";
                    }
                }

                header('Content-Type: text/css; charset=UTF-8');

                echo implode("\n", $css);

                exit;
            }
        );

        return true;
    }
}
