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
use dcCore;
use dcRecord;
use Dotclear\Plugin\widgets\WidgetsElement;

abstract class EpcFilter
{
    private $id      = 'undefined';
    private $records = null;

    private $properties = [
        'priority' => 500,
        'name'     => 'undefined',
        'help'     => 'undefined',
        'has_list' => false,
        'htmltag'  => '',
        'class'    => [],
        'replace'  => '',
        'widget'   => '',
    ];
    private $settings = [
        'nocase'    => false,
        'plural'    => false,
        'limit'     => 0,
        'style'     => [],
        'notag'     => '',
        'tplValues' => [],
        'pubPages'  => [],
    ];

    final public function __construct()
    {
        $this->id = $this->init();

        $this->blogSettings();
    }

    public static function create(ArrayObject $o): void
    {
        $c = get_called_class();
        $o->append(new $c());
    }

    final public function id()
    {
        return $this->id;
    }

    final public function __get(string $k): mixed
    {
        if (isset($this->properties[$k])) {
            return $this->properties[$k];
        }
        if (isset($this->settings[$k])) {
            return $this->settings[$k];
        }

        return null;
    }

    final public function __set(string $k, mixed $v): void
    {
        if (isset($this->settings[$k])) {
            $this->settings[$k] = $v;
        }
    }

    final public function property(string $k): mixed
    {
        return $this->properties[$k] ?? null;
    }

    final protected function setProperties(array|string $property, mixed $value = null): bool
    {
        $properties = is_array($property) ? $property : [$property => $value];
        foreach ($properties as $k => $v) {
            if (isset($this->properties[$k])) {
                $this->properties[$k] = $v;
            }
        }

        return true;
    }

    final public function setting(string $k): mixed
    {
        return $this->settings[$k] ?? null;
    }

    final protected function setSettings(array|string $setting, mixed $value = null): bool
    {
        $settings = is_array($setting) ? $setting : [$setting => $value];
        foreach ($settings as $k => $v) {
            if (isset($this->settings[$k])) {
                $this->settings[$k] = $v;
            }
        }

        return true;
    }

    private function blogSettings(): void
    {
        $opt = json_decode((string) dcCore::app()->blog->settings->get(My::id())->get($this->id));

        if (empty($opt)) {
            $opt = [];
        }
        if (isset($opt->nocase)) {
            $this->settings['nocase'] = (bool) $opt->nocase;
        }
        if (isset($opt->plural)) {
            $this->settings['plural'] = (bool) $opt->plural;
        }
        if (isset($opt->limit)) {
            $this->settings['limit'] = abs((int) $opt->limit);
        }
        if (isset($opt->style) && is_array($opt->style)) {
            $this->settings['style'] = (array) $opt->style;
        }
        if (isset($opt->notag)) {
            $this->settings['notag'] = (string) $opt->notag;
        }
        if (isset($opt->tplValues)) {
            $this->settings['tplValues'] = (array) $opt->tplValues;
        }
        if (isset($opt->pubPages)) {
            $this->settings['pubPages'] = (array) $opt->pubPages;
        }
    }

    final public function records(): ?dcRecord
    {
        if ($this->records === null && $this->has_list) {
            $this->records = EpcRecord::getRecords(['epc_filter' => $this->id()]);
        }

        return $this->records;
    }

    abstract protected function init(): string;

    public function publicContent(string $tag, array $args): void
    {
        return;
    }

    public function widgetList(string $content, WidgetsElement $w, array &$list): void
    {
        return;
    }
}
