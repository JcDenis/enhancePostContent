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
    public const FLAGGER = '�����%s�����';

    private static EpcFilters $filters;
    public static array $epcFilterLimit = [];

    #
    # Default definition
    #

    public static function defaultAllowedTplValues(): array
    {
        $rs = new ArrayObject([
            'entry excerpt'   => 'EntryExcerpt',
            'entry content'   => 'EntryContent',
            'comment content' => 'CommentContent',
        ]);

        # --BEHAVIOR-- enhancePostContentAllowedTplValues : ArrayObject
        dcCore::app()->callBehavior('enhancePostContentAllowedTplValues', $rs);

        return iterator_to_array($rs, true);
    }

    public static function blogAllowedTplValues(): array
    {
        $rs = json_decode((string) dcCore::app()->blog?->settings->get(My::id())->get('allowedtplvalues'), true);

        return is_array($rs) ? $rs : self::defaultAllowedTplValues();
    }

    public static function defaultAllowedWidgetValues(): array
    {
        $rs = new ArrayObject([
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
        dcCore::app()->callBehavior('enhancePostContentAllowedWidgetValues', $rs);

        return iterator_to_array($rs, true);
    }

    public static function defaultAllowedPubPages(): array
    {
        $rs = new ArrayObject([
            'home page'           => 'home.html',
            'post page'           => 'post.html',
            'category page'       => 'category.html',
            'search results page' => 'search.html',
            'atom feeds'          => 'atom.xml',
            'RSS feeds'           => 'rss2.xml',
        ]);

        # --BEHAVIOR-- enhancePostContentAllowedPubPages : ArrayObject
        dcCore::app()->callBehavior('enhancePostContentAllowedPubPages', $rs);

        return iterator_to_array($rs, true);
    }

    public static function blogAllowedPubPages(): array
    {
        $rs = json_decode((string) dcCore::app()->blog?->settings->get(My::id())->get('allowedpubpages'), true);

        return is_array($rs) ? $rs : self::defaultAllowedPubPages();
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

    public static function testContext(string $tag, array $args, EpcFilter $filter): bool
    {
        return in_array((string) dcCore::app()->ctx?->__get('current_tpl'), $filter->pubPages)
            && in_array($tag, $filter->tplValues)
            && $args[0] != '' //content
            && empty($args['encode_xml'])
            && empty($args['encode_html'])
            && empty($args['remove_html'])
            && empty($args['strip_tags'])
        ;
    }

    public static function replaceString(string $p, string $r, string $s, EpcFilter $filter, string $before = '\b', string $after = '\b'): string
    {
        # Limit
        if ($filter->limit > 0) {
            $limit = array_key_exists($filter->id() . '_' . $p, self::$epcFilterLimit) ? self::$epcFilterLimit[$filter->id() . '_' . $p] : $filter->limit;
            if ($limit < 1) {
                return $s;
            }
        } else {
            $limit = -1;
        }
        # Case sensitive
        $i = $filter->nocase ? 'i' : '';
        # Plural
        $x = $filter->plural ? $p . 's|' . $p : $p;

        # Mark words
        $ret = preg_replace('#(' . $before . ')(' . $x . ')(' . $after . ')#su' . $i, '$1' . sprintf(self::FLAGGER, '$2') . '$3', $s, -1, $count);
        if (is_string($ret)) {
            $s = $ret;
        }
        # Nothing to parse
        if (!$count) {
            return $s;
        }

        # Remove words that are into unwanted html tags
        $ignore_tags = array_merge(self::decodeTags($filter->htmltag), self::decodeTags($filter->notag));
        if (!empty($ignore_tags)) {
            $tags = implode('|', array_unique($ignore_tags));

            $ret = preg_replace_callback('#(<(' . $tags . ')[^>]*?>)(.*?)(</\\2>)#s', [self::class, 'removeTags'], $s);
            if (is_string($ret)) {
                $s = $ret;
            }
        }

        # Remove words inside html tag (class, title, alt, href, ...)
        $ret = preg_replace('#(' . sprintf(self::FLAGGER, '(' . $x . '(s|))') . ')(?=[^<]*>)#s' . $i, '$2$4', $s);
        if (is_string($ret)) {
            $s = $ret;
        }

        # Replace words by what you want (with limit)
        $ret = preg_replace('#' . sprintf(self::FLAGGER, '(' . $p . '(s|))') . '#s' . $i, $r, $s, $limit, $count);
        if (is_string($ret)) {
            $s = $ret;
        }

        # update limit
        self::$epcFilterLimit[$filter->id() . '_' . $p] = $limit - $count;

        # Clean rest
        $ret = preg_replace('#' . sprintf(self::FLAGGER, '(.*?)') . '#s', '$1', $s);
        if (is_string($ret)) {
            $s = $ret;
        }

        return $s;
    }

    public static function matchString(string $p, string $r, string $s, EpcFilter $filter, string $before = '\b', string $after = '\b'): array
    {
        # Case sensitive
        $i = $filter->nocase ? 'i' : '';
        # Plural
        $x = $filter->plural ? $p . 's|' . $p : $p;
        # Mark words
        $t = preg_match_all('#' . $before . '(' . $x . ')' . $after . '#su' . $i, $s, $matches);
        # Nothing to parse
        if (!$t) {
            return ['total' => 0, 'matches' => []];
        }

        # Build array
        $m    = [];
        $loop = 0;
        foreach ($matches[1] as $match) {
            $m[$loop]['key']   = $match;
            $m[$loop]['match'] = preg_replace('#(' . $p . '(s|))#s' . $i, $r, $match, -1, $count);
            $m[$loop]['num']   = $count;
            $loop++;
        }

        return ['total' => $t, 'matches' => $m];
    }

    public static function quote(string $s): string
    {
        return preg_quote($s, '#');
    }

    public static function removeTags(array $m): string
    {
        return $m[1] . preg_replace('#' . sprintf(self::FLAGGER, '(?!') . ')#s', '$1', $m[3]) . $m[4];
    }

    public static function decodeTags(string $t): array
    {
        return preg_match_all('#([A-Za-z0-9]+)#', (string) $t, $m) ? $m[1] : [];
    }

    public static function implode(array|string $a): string
    {
        if (is_string($a)) {
            return $a;
        }
        if (!is_array($a)) {
            return '';
        }

        $r = '';
        foreach ($a as $k => $v) {
            $r .= $k . ':' . $v . ';';
        }

        return $r;
    }

    public static function explode(array|string $s): array
    {
        if (is_array($s)) {
            return $s;
        }
        if (!is_string($s)) {
            return [];
        }

        $r = [];
        $s = explode(';', (string) $s);
        if (!is_array($s)) {
            return [];
        }

        foreach ($s as $cpl) {
            $cur = explode(':', $cpl);

            if (!is_array($cur) || !isset($cur[1])) {
                continue;
            }

            $key = Html::escapeHTML(trim($cur[0]));
            $val = Html::escapeHTML(trim($cur[1]));

            if (empty($key) || empty($val)) {
                continue;
            }

            $r[$key] = $val;
        }

        return $r;
    }

    #
    # Widgets
    #

    public static function widgetContentEntryExcerpt(?WidgetsElement $w = null): string
    {
        if (is_null(dcCore::app()->ctx) || !dcCore::app()->ctx->exists('posts')) {
            return '';
        }

        $res = '';
        while (dcCore::app()->ctx->__get('posts')?->fetch()) {
            $res .= dcCore::app()->ctx->__get('posts')->f('post_excerpt');
        }

        return $res;
    }

    public static function widgetContentEntryContent(): string
    {
        if (is_null(dcCore::app()->ctx) || !dcCore::app()->ctx->exists('posts')) {
            return '';
        }

        $res = '';
        while (dcCore::app()->ctx->__get('posts')?->fetch()) {
            $res .= dcCore::app()->ctx->__get('posts')->f('post_content');
        }

        return $res;
    }

    public static function widgetContentCommentContent(): string
    {
        if (is_null(dcCore::app()->ctx) || !dcCore::app()->ctx->exists('posts')) {
            return '';
        }

        $res      = '';
        $post_ids = [];
        while (dcCore::app()->ctx->__get('posts')?->fetch()) {
            $comments = dcCore::app()->blog?->getComments(['post_id' => dcCore::app()->ctx->__get('posts')->f('post_id')]);
            while ($comments?->fetch()) {
                $res .= $comments->getContent();
            }
        }

        return $res;
    }
}
