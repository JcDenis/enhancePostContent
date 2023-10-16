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
        $s = json_decode((string) My::settings()->get($this->id), true);
        if (empty($s)) {
            $s = [];
        }

        $properties = $this->initProperties();
        $settings   = $this->initSettings();

        // from filter defautl properties
        $this->priority    = isset($properties['priority']) ? abs((int) $properties['priority']) : 500;
        $this->name        = isset($properties['name']) ? (string) $properties['name'] : 'undefined';
        $this->description = isset($properties['description']) ? (string) $properties['description'] : 'undefined';
        $this->has_list    = isset($properties['has_list']) ? (bool) $properties['has_list'] : false;
        $this->ignore      = isset($properties['ignore']) && is_array($properties['ignore']) ? $properties['ignore'] : [];
        $this->class       = isset($properties['class'])  && is_array($properties['class']) ? $properties['class'] : [];
        $this->replace     = isset($properties['replace']) ? (string) $properties['replace'] : '';
        $this->widget      = isset($properties['widget']) ? (string) $properties['widget'] : '';

        // from filter defautl settings
        $nocase   = isset($settings['nocase']) ? (bool) $settings['nocase'] : false;
        $plural   = isset($settings['plural']) ? (bool) $settings['plural'] : false;
        $limit    = isset($settings['limit']) ? abs((int) $settings['limit']) : 0;
        $style    = isset($settings['style'])    && is_array($settings['style']) ? $settings['style'] : [];
        $notag    = isset($settings['notag'])    && is_array($settings['notag']) ? $settings['notag'] : [];
        $template = isset($settings['template']) && is_array($settings['template']) ? $settings['template'] : [];
        $page     = isset($settings['page'])     && is_array($settings['page']) ? $settings['page'] : [];

        // from blog settings
        $this->nocase   = isset($s['nocase']) ? (bool) $s['nocase'] : $nocase;
        $this->plural   = isset($s['plural']) ? (bool) $s['plural'] : $plural;
        $this->limit    = isset($s['limit']) ? abs((int) $s['limit']) : $limit;
        $this->style    = isset($s['style'])    && is_array($s['style']) ? $s['style'] : $style;
        $this->notag    = isset($s['notag'])    && is_array($s['notag']) ? $s['notag'] : $notag;
        $this->template = isset($s['template']) && is_array($s['template']) ? $s['template'] : $template;
        $this->page     = isset($s['page'])     && is_array($s['page']) ? $s['page'] : $page;
    }

    /**
     * Return filter default properties.
     *
     * @return  array   The properties
     */
    abstract protected function initProperties(): array;

    /**
     * Return filter default settings.
     *
     * @return  array   The settings
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
     * @param   string  $tag    The tempale block tag
     * @param   array   $args   The template block arguments
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
     * @param   string          $content    The contents
     * @param   WidgetsElement  $widget     The widget
     * @param   ArrayObject     $list       The list
     */
    public function widgetList(string $content, WidgetsElement $widget, ArrayObject $list): void
    {
    }
}
