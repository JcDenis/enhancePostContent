<?php

declare(strict_types=1);

namespace Dotclear\Plugin\enhancePostContent;

use ArrayObject;
use Dotclear\Database\MetaRecord;
use Dotclear\Plugin\widgets\WidgetsElement;
use Exception;

/**
 * @brief       enhancePostContent abstract filter class.
 * @ingroup     enhancePostContent
 *
 * All filter must extends this class.
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
abstract class EpcFilter
{
    /** @var    string  $id     The filter id */
    protected string $id = 'undefined';

    /** @var    MetaRecord    $records    The filter record if any */
    private ?MetaRecord $records = null;

    /** @var    int     $priority   The filter priority (property) */
    public readonly int $priority;

    /** @var    string  $name   The filter name (property) */
    public readonly string $name;

    /** @var    string  $description   The filter description (property) */
    public readonly string $description;

    /** @var    bool    $has_list   Filter has list of records (property) */
    public readonly bool $has_list;

    /** @var    array<int,string>   $ignore     The filter disabled html tags (property) */
    public readonly array $ignore;

    /** @var    array<int,string>  $class  The css class that apply to filter (property) */
    public readonly array $class;

    /** @var    string  $replace    The filter replacement bloc in content (property) */
    public readonly string $replace;

    /** @var    string  $widget     The filter replacement bloc in widget (property) */
    public readonly string $widget;

    /** @var    bool    $nocase     The filter caseless match (settings) */
    public readonly bool $nocase;

    /** @var    bool    $plural     The filter caseless match (settings) */
    public readonly bool $plural;

    /** @var    bool    $plural     The replacement limit per filter (settings) */
    public readonly int $limit;

    /** @var    array<int,string>   $style   The style applied to filter class (settings) */
    public readonly array $style;

    /** @var    array<int,string>   $notag   The filter disabled html tags (settings) */
    public readonly array $notag;

    /** @var    array<int,string>   $template    The extra template value to scan (settings) */
    public readonly array $template;

    /** @var    array<int,string>   $page   The extra frontend pages to scan (settings) */
    public readonly array $page;

    /**
     * Constructor sets filter properties and settings.
     */
    final public function __construct()
    {
        if ($this->id == 'undefined') {
            throw new Exception('Undefined Filter id');
        }

        // get blog settings
        $s = json_decode(My::settings()->getStr($this->id, false), true);
        if (empty($s) || !is_array($s)) {
            $s = [];
        }

        $properties = $this->initProperties();
        $settings   = $this->initSettings();

        // from filter defautl properties
        $this->priority    = isset($properties['priority']) && is_numeric($properties['priority']) ? abs((int) $properties['priority']) : 500;
        $this->name        = isset($properties['name']) && is_string($properties['name']) ? $properties['name'] : 'undefined';
        $this->description = isset($properties['description']) && is_string($properties['description']) ? $properties['description'] : 'undefined';
        $this->has_list    = isset($properties['has_list']) ? !!$properties['has_list'] : false;
        $this->ignore      = isset($properties['ignore']) && is_array($properties['ignore']) ? array_values(array_filter($properties['ignore'], is_string(...))) : [];
        $this->class       = isset($properties['class'])  && is_array($properties['class']) ? array_values(array_filter($properties['class'], is_string(...))) : [];
        $this->replace     = isset($properties['replace']) && is_string($properties['replace']) ? $properties['replace'] : '';
        $this->widget      = isset($properties['widget']) && is_string($properties['widget']) ? $properties['widget'] : '';

        // from filter defautl settings
        $nocase   = isset($settings['nocase']) ? !!$settings['nocase'] : false;
        $plural   = isset($settings['plural']) ? !!$settings['plural'] : false;
        $limit    = isset($settings['limit']) && is_numeric($settings['limit']) ? abs((int) $settings['limit']) : 0;
        $style    = isset($settings['style']) && is_array($settings['style']) ? $settings['style'] : [];
        $notag    = isset($settings['notag']) && is_array($settings['notag']) ? $settings['notag'] : [];
        $template = isset($settings['template']) && is_array($settings['template']) ? $settings['template'] : [];
        $page     = isset($settings['page']) && is_array($settings['page']) ? $settings['page'] : [];

        // from blog settings
        $this->nocase   = isset($s['nocase']) ? !!$s['nocase'] : $nocase;
        $this->plural   = isset($s['plural']) ? !!$s['plural'] : $plural;
        $this->limit    = isset($s['limit']) && is_numeric($s['limit']) ? abs((int) $s['limit']) : $limit;
        $this->style    = array_values(array_filter(isset($s['style']) && is_array($s['style']) ? $s['style'] : $style, is_string(...)));
        $this->notag    = array_values(array_filter(isset($s['notag']) && is_array($s['notag']) ? $s['notag'] : $notag, is_string(...)));
        $this->template = array_values(array_filter(isset($s['template']) && is_array($s['template']) ? $s['template'] : $template, is_string(...)));
        $this->page     = array_values(array_filter(isset($s['page']) && is_array($s['page']) ? $s['page'] : $page, is_string(...)));
    }

    /**
     * Return filter default properties.
     *
     * @return  array<string, mixed>    The properties
     */
    abstract protected function initProperties(): array;

    /**
     * Return filter default settings.
     *
     * @return  array<string, mixed>    The settings
     */
    abstract protected function initSettings(): array;

    /**
     * Get fitler ID.
     *
     * @return  string  The filter ID
     */
    final public function id(): string
    {
        return $this->id;
    }

    /**
     * Get fitler record.
     *
     * Fitler records are usefull to store and retrieve
     * list of keyword / replacement etc...
     *
     * @return  MetaRecord    The filter record instance
     */
    final public function records(): MetaRecord
    {
        if ($this->records === null && $this->has_list) {
            $this->records = EpcRecord::getRecords(['epc_filter' => $this->id()]);
        }

        return $this->records ?? MetaRecord::newFromArray([]);
    }

    /**
     * Filter frontend contents in situ.
     *
     * @param   string                      $tag    The tempale block tag
     * @param   array<int|string, mixed>    $args   The template block arguments
     */
    public function publicContent(string $tag, array $args): void
    {
    }

    /**
     * Filter frontend contents for widgets.
     *
     * Filter the contents and return matching results infos
     * into the list of current widget.
     *
     * @param   string                                  $content    The contents
     * @param   WidgetsElement                          $widget     The widget
     * @param   ArrayObject<int, array<string, mixed>>  $list       The list
     */
    public function widgetList(string $content, WidgetsElement $widget, ArrayObject $list): void
    {
    }
}
