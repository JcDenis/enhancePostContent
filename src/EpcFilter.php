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
use Exception;

abstract class EpcFilter
{
    protected string $id = 'undefined';

    private ?dcRecord $records = null;

    // properties
    public readonly int $priority;
    public readonly string $name;
    public readonly string $help;
    public readonly bool $has_list;
    public readonly string $htmltag;
    public readonly array $class;
    public readonly string $replace;
    public readonly string $widget;

    // settings
    public readonly bool $nocase;
    public readonly bool $plural;
    public readonly int $limit;
    public readonly array $style;
    public readonly string $notag;
    public readonly array $tplValues;
    public readonly array $pubPages;

    final public function __construct()
    {
        if ($this->id == 'undefined') {
            throw new Exception('Undefined Filter id');
        }

        // get blog settings
        $s = json_decode((string) dcCore::app()->blog->settings->get(My::id())->get($this->id), true);
        if (empty($s)) {
            $s = [];
        }

        $properties = $this->initProperties();
        $settings   = $this->initSettings();

        // from filter defautl properties
        $this->priority = isset($properties['priority']) ? abs((int) $properties['priority']) : 500;
        $this->name     = isset($properties['name']) ? (string) $properties['name'] : 'undefined';
        $this->help     = isset($properties['help']) ? (string) $properties['help'] : 'undefined';
        $this->has_list = isset($properties['has_list']) ? (bool) $properties['has_list'] : false;
        $this->htmltag  = isset($properties['htmltag']) ? (string) $properties['htmltag'] : '';
        $this->class    = isset($properties['class']) && is_array($properties['class']) ? $properties['class'] : [];
        $this->replace  = isset($properties['replace']) ? (string) $properties['replace'] : '';
        $this->widget   = isset($properties['widget']) ? (string) $properties['widget'] : '';

        // from filter defautl settings
        $nocase    = isset($settings['nocase']) ? (bool) $settings['nocase'] : false;
        $plural    = isset($settings['plural']) ? (bool) $settings['plural'] : false;
        $limit     = isset($settings['limit']) ? abs((int) $settings['limit']) : 0;
        $style     = isset($settings['style']) && is_array($settings['style']) ? $settings['style'] : [];
        $notag     = isset($settings['notag']) ? (string) $settings['notag'] : '';
        $tplValues = isset($settings['tplValues']) && is_array($settings['tplValues']) ? $settings['tplValues'] : [];
        $pubPages  = isset($settings['pubPages'])  && is_array($settings['pubPages']) ? $settings['pubPages'] : [];

        // from blog settings
        $this->nocase    = isset($s['nocase']) ? (bool) $s['nocase'] : $nocase;
        $this->plural    = isset($s['plural']) ? (bool) $s['plural'] : $plural;
        $this->limit     = isset($s['limit']) ? abs((int) $s['limit']) : $limit;
        $this->style     = isset($s['style']) && is_array($s['style']) ? $s['style'] : $style;
        $this->notag     = isset($s['notag']) ? (string) $s['notag'] : $notag;
        $this->tplValues = isset($s['tplValues']) && is_array($s['tplValues']) ? $s['tplValues'] : $tplValues;
        $this->pubPages  = isset($s['pubPages'])  && is_array($s['pubPages']) ? $s['pubPages'] : $pubPages;
    }

    protected function initProperties(): array
    {
        return [];
    }

    protected function initSettings(): array
    {
        return [];
    }

    public static function create(ArrayObject $o): void
    {
        $c = static::class;
        $o->append(new $c());
    }

    final public function id(): string
    {
        return $this->id;
    }

    final public function records(): ?dcRecord
    {
        if ($this->records === null && $this->has_list) {
            $this->records = EpcRecord::getRecords(['epc_filter' => $this->id()]);
        }

        return $this->records;
    }

    public function publicContent(string $tag, array $args): void
    {
    }

    public function widgetList(string $content, WidgetsElement $w, ArrayObject $list): void
    {
    }
}
