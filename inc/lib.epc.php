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
# l10n
__('entry excerpt');__('entry content');__('comment content');
__('home page');__('post page');__('category page');__('search results page');
__('atom feeds');__('RSS feeds');

class libEPC
{
    protected static $default_filters = null;
    public static $epcFilterLimit     = [];

    #
    # Default definition
    #

    public static function defaultAllowedTplValues()
    {
        global $core;

        $rs = new arrayObject([
            'entry excerpt'   => 'EntryExcerpt',
            'entry content'   => 'EntryContent',
            'comment content' => 'CommentContent',
        ]);

        $core->callBehavior('enhancePostContentAllowedTplValues', $rs);

        return iterator_to_array($rs, true);
    }

    public static function blogAllowedTplValues()
    {
        global $core;

        $core->blog->settings->addNamespace('enhancePostContent');
        $rs = @unserialize($core->blog->settings->enhancePostContent->enhancePostContent_allowedtplvalues);

        return is_array($rs) ? $rs : self::defaultAllowedTplValues();
    }

    public static function defaultAllowedWidgetValues()
    {
        global $core;

        $rs = new arrayObject([
            'entry excerpt' => [
                'id' => 'entryexcerpt',
                'cb' => ['libEPC','widgetContentEntryExcerpt']
            ],
            'entry content' => [
                'id' => 'entrycontent',
                'cb' => ['libEPC','widgetContentEntryContent']
            ],
            'comment content' => [
                'id' => 'commentcontent',
                'cb' => ['libEPC','widgetContentCommentContent']
            ]
        ]);

        $core->callBehavior('enhancePostContentAllowedWidgetValues', $rs);

        return iterator_to_array($rs, true);
    }

    public static function defaultAllowedPubPages()
    {
        global $core;

        $rs = new arrayObject([
            'home page'           => 'home.html',
            'post page'           => 'post.html',
            'category page'       => 'category.html',
            'search results page' => 'search.html',
            'atom feeds'          => 'atom.xml',
            'RSS feeds'           => 'rss2.xml'
        ]);

        $core->callBehavior('enhancePostContentAllowedPubPages', $rs);

        return iterator_to_array($rs, true);
    }

    public static function blogAllowedPubPages()
    {
        global $core;

        $core->blog->settings->addNamespace('enhancePostContent');
        $rs = @unserialize($core->blog->settings->enhancePostContent->enhancePostContent_allowedpubpages);

        return is_array($rs) ? $rs : self::defaultAllowedPubPages();
    }

    public static function getFilters()
    {
        global $core;

        if (self::$default_filters === null) {
            $final   = $sort   = [];
            $filters = new arrayObject();

            try {
                $core->callBehavior('enhancePostContentFilters', $filters, $core);

                foreach ($filters as $filter) {
                    if ($filter instanceof epcFilter && !isset($final[$filter->id()])) {
                        $sort[$filter->id()]  = $filter->priority;
                        $final[$filter->id()] = $filter;
                    }
                }
            } catch (Exception $e) {
                $core->error->add($e->getMessage());
            }
            array_multisort($sort, $final);
            self::$default_filters = $final;
        }

        return self::$default_filters;
    }

    public static function testContext($tag, $args, $filter)
    {
        return is_array($filter->pubPages)
            && in_array($GLOBALS['_ctx']->current_tpl, $filter->pubPages)
            && is_array($filter->tplValues)
            && in_array($tag, $filter->tplValues)
            && $args[0] != '' //content
            && empty($args['encode_xml'])
            && empty($args['encode_html'])
            && empty($args['remove_html'])
            && empty($args['strip_tags'])
        ;
    }

    public static function replaceString($p, $r, $s, $filter, $before = '\b', $after = '\b')
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
        $s = preg_replace('#(' . $before . ')(' . $x . ')(' . $after . ')#su' . $i, '$1ççççç$2ççççç$3', $s, -1, $count);
        # Nothing to parse
        if (!$count) {
            return $s;
        }
        # Remove words that are into unwanted html tags
        $tags        = '';
        $ignore_tags = array_merge(self::decodeTags($filter->htmltag), self::decodeTags($filter->notag));
        if (is_array($ignore_tags) && !empty($ignore_tags)) {
            $tags = implode('|', $ignore_tags);
        }
        if (!empty($tags)) {
            $s = preg_replace_callback('#(<(' . $tags . ')[^>]*?>)(.*?)(</\\2>)#s', ['libEPC', 'removeTags'], $s);
        }
        # Remove words inside html tag (class, title, alt, href, ...)
        $s = preg_replace('#(ççççç(' . $p . '(s|))ççççç)(?=[^<]+>)#s' . $i, '$2$4', $s);
        # Replace words by what you want (with limit)
        $s = preg_replace('#ççççç(' . $p . '(s|))ççççç#s' . $i, $r, $s, $limit, $count);
        # update limit
        self::$epcFilterLimit[$filter->id() . '_' . $p] = $limit - $count;
        # Clean rest
        return $s = preg_replace('#ççççç(.*?)ççççç#s', '$1', $s);
    }

    public static function matchString($p, $r, $s, $filter, $before = '\b', $after = '\b')
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

    public static function quote($s)
    {
        return preg_quote($s, '#');
    }

    public static function removeTags($m)
    {
        return $m[1] . preg_replace('#ççççç(?!ççççç)#s', '$1', $m[3]) . $m[4];
    }

    public static function decodeTags($t)
    {
        return preg_match_all('#([A-Za-z0-9]+)#', (string) $t, $m) ? $m[1] : [];
    }

    public static function implode($a)
    {
        if (is_string($a)) {
            return $a;
        }
        if (!is_array($a)) {
            return [];
        }

        $r = '';
        foreach ($a as $k => $v) {
            $r .= $k . ':' . $v . ';';
        }

        return $r;
    }

    public static function explode($s)
    {
        if (is_array($s)) {
            return $s;
        }
        if (!is_string($s)) {
            return '';
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

            $key = html::escapeHTML(trim($cur[0]));
            $val = html::escapeHTML(trim($cur[1]));

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

    public static function widgetContentEntryExcerpt($core, $w)
    {
        global $_ctx;

        if (!$_ctx->exists('posts')) {
            return null;
        }

        $res = '';
        while ($_ctx->posts->fetch()) {
            $res .= $_ctx->posts->post_excerpt;
        }

        return $res;
    }

    public static function widgetContentEntryContent()
    {
        global $_ctx;

        if (!$_ctx->exists('posts')) {
            return null;
        }

        $res = '';
        while ($_ctx->posts->fetch()) {
            $res .= $_ctx->posts->post_content;
        }

        return $res;
    }

    public static function widgetContentCommentContent()
    {
        global $core, $_ctx;

        if (!$_ctx->exists('posts')) {
            return null;
        }

        $res      = '';
        $post_ids = [];
        while ($_ctx->posts->fetch()) {
            $comments = $core->blog->getComments(['post_id' => $_ctx->posts->post_id]);
            while ($comments->fetch()) {
                $res .= $comments->getContent();
            }
        }

        return $res;
    }
}
