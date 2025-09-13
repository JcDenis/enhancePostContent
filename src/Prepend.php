<?php

declare(strict_types=1);

namespace Dotclear\Plugin\enhancePostContent;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Helper\Html\Html;

/**
 * @brief       enhancePostContent prepend class.
 * @ingroup     enhancePostContent
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Prepend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // register epc filters
        App::behavior()->addBehavior('enhancePostContentFilters', function (EpcFilters $stack): void {
            foreach (Epc::DEFAULT_FILTERS as $class) {
                $stack->add(new $class());
            }
        });

        // register epc filters frontend css
        App::url()->register(
            'epccss',
            'epc.css',
            '^epc\.css',
            function (?string $args): void {
                $css = [];
                foreach (Epc::getFilters()->dump() as $filter) {
                    if ([] === $filter->class || [] === $filter->style) {
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
