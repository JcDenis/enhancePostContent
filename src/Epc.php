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
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\widgets\WidgetsElement;
use Exception;

# l10n
__('entry excerpt');
__('entry content');
__('comment content');
__('home page');
__('post page');
__('category page');
__('search results page');
__('atom feeds');
__('RSS feeds');

class Epc
{
    /** @var    string  The temporary pattern to tag words to replace */
    public const FLAGGER = 'ççççç%sççççç';

    /** @var    EpcFilters  $filters    THe filters stack */
    private static EpcFilters $filters;

    /** @var    array<string,int>   $limits     The replacment limit per filtre */
    private static array $limits = [];

    /**
     * Get list of default allowed templates name->tag.
     *
     * @return  array<string,string>    The templates name->tag pairs
     */
    public static function defaultAllowedTemplateValue(): array
    {
        $list = new ArrayObject([
            'entry excerpt'   => 'EntryExcerpt',
            'entry content'   => 'EntryContent',
            'comment content' => 'CommentContent',
        ]);

        # --BEHAVIOR-- enhancePostContentAllowedTplValues : ArrayObject
        dcCore::app()->callBehavior('enhancePostContentAllowedTplValues', $list);

        return iterator_to_array($list, true);
    }

    /**
     * Get list of allowed templates name->tag set on current blog.
     *
     * @return  array<string,string>    The templates name->tag pairs
     */
    public static function blogAllowedTemplateValue(): array
    {
        $list = json_decode((string) dcCore::app()->blog?->settings->get(My::id())->get('allowedtplvalues'), true);

        return is_array($list) ? $list : self::defaultAllowedTemplateValue();
    }

    /**
     * Get list of allowed templates name->[tag,callback] to list on epc widgets.
     *
     * @return  array    The templates name->[id,cb] values
     */
    public static function widgetAllowedTemplateValue(): array
    {
        $list = new ArrayObject([
            'entry excerpt' => [
                'id' => 'entryexcerpt',
                'cb' => [self::class, 'widgetContentEntryExcerpt'],
            ],
            'entry content' => [
                'id' => 'entrycontent',
                'cb' => [self::class, 'widgetContentEntryContent'],
            ],
            'comment content' => [
                'id' => 'commentcontent',
                'cb' => [self::class, 'widgetContentCommentContent'],
            ],
        ]);

        # --BEHAVIOR-- enhancePostContentAllowedWidgetValues : ArrayObject
        dcCore::app()->callBehavior('enhancePostContentAllowedWidgetValues', $list);

        return iterator_to_array($list, true);
    }

    /**
     * Get list of default allowed templates name->page to list on epc widgets.
     *
     * @return  array<string,string>    The templates name->page pairs
     */
    public static function defaultAllowedTemplatePage(): array
    {
        $list = new ArrayObject([
            'home page'           => 'home.html',
            'post page'           => 'post.html',
            'category page'       => 'category.html',
            'search results page' => 'search.html',
            'atom feeds'          => 'atom.xml',
            'RSS feeds'           => 'rss2.xml',
        ]);

        # --BEHAVIOR-- enhancePostContentAllowedPubPages : ArrayObject
        dcCore::app()->callBehavior('enhancePostContentAllowedPubPages', $list);

        return iterator_to_array($list, true);
    }

    /**
     * Get list of allowed templates name->page set on blog to list on epc widgets.
     *
     * @return  array<string,string>    The templates name->page pairs
     */
    public static function blogAllowedTemplatePage(): array
    {
        $list = json_decode((string) dcCore::app()->blog?->settings->get(My::id())->get('allowedpubpages'), true);

        return is_array($list) ? $list : self::defaultAllowedTemplatePage();
    }

    /**
     * Get filters.
     *
     * On first call, we load once filters from behavior.
     *
     * @return  EpcFilters  The fitlers instacne
     */
    public static function getFilters(): EpcFilters
    {
        if (empty(self::$filters)) {
            $filters = new EpcFilters();

            try {
                # --BEHAVIOR-- enhancePostContentFilters : EpcFilters
                dcCore::app()->callBehavior('enhancePostContentFilters', $filters);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }

            self::$filters = $filters->sort();
        }

        return self::$filters;
    }

    /**
     * Apply filter to content.
     *
     * @param   string      $search         The search
     * @param   string      $replacement    The replacement
     * @param   string      $content        The content
     * @param   EpcFilter   $filter         The filter
     * @param   string      $before         The start limit pattern
     * @param   string      $after          The end limit pattern
     */
    public static function replaceString(
        string $search,
        string $replacement,
        string $content,
        EpcFilter $filter,
        string $before = '\b',
        string $after = '\b'
    ): string {
        // Limit
        if ($filter->limit > 0) {
            // memorize limit between two template values
            $limit = array_key_exists($filter->id() . '_' . $search, self::$limits) ? self::$limits[$filter->id() . '_' . $search] : $filter->limit;
            if ($limit < 1) {
                return $content;
            }
        } else {
            $limit = -1;
        }

        // Case sensitive
        $caseless = $filter->nocase ? 'i' : '';

        # Plural
        $plural = $filter->plural ? 's?' : '';

        // Mark words
        $ret = preg_replace('#(' . $before . ')(' . $search . $plural . ')(' . $after . ')#su' . $caseless, '$1' . sprintf(self::FLAGGER, '$2') . '$3', $content, -1, $count);
        if (is_string($ret)) {
            $content = $ret;
        }

        // Nothing to parse
        if (!$count) {
            return $content;
        }

        // Remove words that are into unwanted html tags
        $ignore = array_merge(self::decodeSingle($filter->ignore), self::decodeSingle($filter->notag));
        if (!empty($ignore)) {
            $ret = preg_replace_callback('#(<(' . implode('|', array_unique($ignore)) . ')[^>]*?>)(.*?)(</\\2>)#s', function (array $m): string {
                return $m[1] . preg_replace('#' . sprintf(self::FLAGGER, '(?!') . ')#s', '$1', $m[3]) . $m[4];
            }, $content);
            if (is_string($ret)) {
                $content = $ret;
            }
        }

        // Remove words inside html tag (class, title, alt, href, ...)
        $ret = preg_replace('#(' . sprintf(self::FLAGGER, '(' . $search . '(' . $plural . '))') . ')(?=[^<]*>)#s' . $caseless, '$2$4', $content);
        if (is_string($ret)) {
            $content = $ret;
        }

        // Replace words by what you want (with limit)
        $ret = preg_replace('#' . sprintf(self::FLAGGER, '(' . $search . '(' . $plural . '))') . '#s' . $caseless, $replacement, $content, $limit, $count);
        if (is_string($ret)) {
            $content = $ret;
        }

        // update limit
        self::$limits[$filter->id() . '_' . $search] = $limit - $count;

        // Clean rest
        $ret = preg_replace('#' . sprintf(self::FLAGGER, '(.*?)') . '#s', '$1', $content);
        if (is_string($ret)) {
            $content = $ret;
        }

        return $content;
    }

    /**
     * Find filter on content.
     *
     * @param   string      $search         The search
     * @param   string      $replacement    The replacement
     * @param   string      $content        The content
     * @param   EpcFilter   $filter         The filter
     * @param   string      $before         The start limit pattern
     * @param   string      $after          The end limit pattern
     */
    public static function matchString(
        string $search,
        string $replacement,
        string $content,
        EpcFilter $filter,
        string $before = '\b',
        string $after = '\b'
    ): array {
        return [
            'total'       => (int) preg_match_all('#' . $before . '(' . $search . ($filter->plural ? 's?' : '') . ')' . $after . '#su' . ($filter->nocase ? 'i' : ''), $content),
            'search'      => $search,
            'replacement' => preg_replace('#(' . $search . ')#', $replacement, $search),
        ];
    }

    /**
     * Quote regular expression according to epc parser.
     *
     * @param   string  $string     The string
     *
     * @return  string  The quoted string
     */
    public static function quote(string $string): string
    {
        return preg_quote($string, '#');
    }

    /**
     * Implode simple array into string a,b,c.
     *
     * @param   array|string    $values     The values
     *
     * @return  string  The value
     */
    public static function encodeSingle(array|string $values): string
    {
        return implode(',', self::decodeSingle($values));
    }

    /**
     * Explode string into simple array [a,b,c].
     *
     * @param   array|string    $value  The value
     *
     * @return  array   The values
     */
    public static function decodeSingle(array|string $value): array
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        return preg_match_all('#([A-Za-z0-9]+)#', (string) $value, $matches) ? $matches[1] : [];
    }

    /**
     * Implode complexe array into string a:aa:b:bb;c:cc.
     *
     * @param   array|string    $values     The values
     *
     * @return  string  The value
     */
    public static function encodeMulti(array|string $values): string
    {
        if (is_string($values)) {
            return $values;
        }

        $string = '';
        foreach ($values as $key => $value) {
            $string .= $key . ':' . $value . ';';
        }

        return $string;
    }

    /**
     * Explode string into complexe array [a=>aa,b=>aa,c=>cc].
     *
     * @param   array|string    $value  The value
     *
     * @return  array   The values
     */
    public static function decodeMulti(array|string $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $values = [];
        $exp    = explode(';', (string) $value);
        if (!is_array($exp)) {
            return [];
        }

        foreach ($exp as $cpl) {
            $cur = explode(':', $cpl);

            if (!is_array($cur) || !isset($cur[1])) {
                continue;
            }

            $key = Html::escapeHTML(trim($cur[0]));
            $val = Html::escapeHTML(trim($cur[1]));

            if (empty($key) || empty($val)) {
                continue;
            }

            $values[$key] = $val;
        }

        return $values;
    }

    /**
     * Send entries excerpts to widget.
     *
     * @param   WidgetsElement|null     $widget     The widgets
     *
     * @return  string  The entries exceprts
     */
    public static function widgetContentEntryExcerpt(?WidgetsElement $widget = null): string
    {
        if (is_null(dcCore::app()->ctx) || !dcCore::app()->ctx->exists('posts')) {
            return '';
        }

        $content = '';
        while (dcCore::app()->ctx->__get('posts')?->fetch()) {
            $content .= dcCore::app()->ctx->__get('posts')->f('post_excerpt');
        }

        return $content;
    }

    /**
     * Send entries contents to widget.
     *
     * @param   WidgetsElement|null     $widget     The widgets
     *
     * @return  string  The entries contents
     */
    public static function widgetContentEntryContent(?WidgetsElement $widget = null): string
    {
        if (is_null(dcCore::app()->ctx) || !dcCore::app()->ctx->exists('posts')) {
            return '';
        }

        $content = '';
        while (dcCore::app()->ctx->__get('posts')?->fetch()) {
            $content .= dcCore::app()->ctx->__get('posts')->f('post_content');
        }

        return $content;
    }

    /**
     * Send entries comments to widget.
     *
     * @param   WidgetsElement|null     $widget     The widgets
     *
     * @return  string  The entries comments
     */
    public static function widgetContentCommentContent(?WidgetsElement $widget = null): string
    {
        if (is_null(dcCore::app()->ctx) || !dcCore::app()->ctx->exists('posts')) {
            return '';
        }

        $content = '';
        while (dcCore::app()->ctx->__get('posts')?->fetch()) {
            $comments = dcCore::app()->blog?->getComments(['post_id' => dcCore::app()->ctx->__get('posts')->f('post_id')]);
            while ($comments?->fetch()) {
                $content .= $comments->__call('getContent', null);
            }
        }

        return $content;
    }
}
