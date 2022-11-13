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
class epcFilterTag extends epcFilter
{
    protected function init(): string
    {
        $this->setProperties([
            'priority' => 900,
            'name'     => __('Tag'),
            'help'     => __('Highlight tags of your blog.'),
            'htmltag'  => 'a',
            'class'    => ['a.epc-tag'],
            'replace'  => '<a class="epc-tag" href="%s" title="' . __('Tag') . '">%s</a>',
            'widget'   => '<a href="%s" title="' . __('Tag') . '">%s</a>',
        ]);

        $this->setSettings([
            'style'     => ['text-decoration: none; border-bottom: 3px double #CCCCCC;'],
            'notag'     => 'a,h1,h2,h3',
            'tplValues' => ['EntryContent'],
            'pubPages'  => ['post.html'],
        ]);

        return 'tag';
    }

    public function publicContent($tag, $args)
    {
        if (!dcCore::app()->plugins->moduleExists('tags')) {
            return null;
        }

        $metas = dcCore::app()->meta->getMetadata(['meta_type' => 'tag']);

        while ($metas->fetch()) {
            $args[0] = libEPC::replaceString(
                $metas->meta_id,
                sprintf($this->replace, dcCore::app()->blog->url . dcCore::app()->url->getBase('tag') . '/' . $metas->meta_id, '\\1'),
                $args[0],
                $this
            );
        }

        return null;
    }

    public function widgetList($content, $w, &$list)
    {
        if (!dcCore::app()->plugins->moduleExists('tags')) {
            return null;
        }

        $metas = dcCore::app()->meta->getMetadata(['meta_type' => 'tag']);

        while ($metas->fetch()) {
            $list[] = libEPC::matchString(
                $metas->meta_id,
                sprintf($this->widget, dcCore::app()->blog->url . dcCore::app()->url->getBase('tag') . '/' . $metas->meta_id, '\\1'),
                $content,
                $this
            );
        }

        return null;
    }
}

class epcFilterSearch extends epcFilter
{
    protected function init(): string
    {
        $this->setProperties([
            'priority' => 100,
            'name'     => __('Search'),
            'help'     => __('Highlight searched words.'),
            'htmltag'  => '',
            'class'    => ['span.epc-search'],
            'replace'  => '<span class="epc-search" title="' . __('Search') . '">%s</span>',
        ]);

        $this->setSettings([
            'nocase'    => true,
            'plural'    => true,
            'style'     => ['color: #FFCC66;'],
            'notag'     => 'h1,h2,h3',
            'tplValues' => ['EntryContent'],
            'pubPages'  => ['search.html'],
        ]);

        return 'search';
    }

    public function publicContent($tag, $args)
    {
        if (empty(dcCore::app()->public->search)) {
            return null;
        }

        $searchs = explode(' ', dcCore::app()->public->search);

        foreach ($searchs as $k => $v) {
            $args[0] = libEPC::replaceString(
                $v,
                sprintf($this->replace, '\\1'),
                $args[0],
                $this
            );
        }

        return null;
    }
}

class epcFilterAcronym extends epcFilter
{
    protected function init(): string
    {
        $this->setProperties([
            'priority' => 700,
            'name'     => __('Acronym'),
            'help'     => __('Explain some acronyms. First term of the list is the acornym and second term the explanation.'),
            'has_list' => true,
            'htmltag'  => 'acronym',
            'class'    => ['acronym.epc-acronym'],
            'replace'  => '<acronym class="epc-acronym" title="%s">%s</acronym>',
            'widget'   => '<acronym title="%s">%s</acronym>',
        ]);

        $this->setSettings([
            'style'     => ['font-weight: bold;'],
            'notag'     => 'a,acronym,abbr,dfn,h1,h2,h3',
            'tplValues' => ['EntryContent'],
            'pubPages'  => ['post.html'],
        ]);

        return 'acronym';
    }

    public function publicContent($tag, $args)
    {
        while ($this->records()->fetch()) {
            $args[0] = libEPC::replaceString(
                $this->records()->epc_key,
                sprintf($this->replace, __($this->records()->epc_value), '\\1'),
                $args[0],
                $this
            );
        }

        return null;
    }

    public function widgetList($content, $w, &$list)
    {
        while ($this->records()->fetch()) {
            $list[] = libEPC::matchString(
                $this->records()->epc_key,
                sprintf($this->widget, __($this->records()->epc_value), '\\1'),
                $content,
                $this
            );
        }

        return null;
    }
}

class epcFilterAbbreviation extends epcFilter
{
    protected function init(): string
    {
        $this->setProperties([
            'priority' => 400,
            'name'     => __('Abbreviation'),
            'help'     => __('Explain some abbreviation. First term of the list is the abbreviation and second term the explanation.'),
            'has_list' => true,
            'htmltag'  => 'a',
            'class'    => ['abbr.epc-abbr'],
            'replace'  => '<abbr class="epc-abbr" title="%s">%s</abbr>',
            'widget'   => '<abbr title="%s">%s</abbr>',
        ]);

        $this->setSettings([
            'style'     => ['font-weight: bold;'],
            'notag'     => 'a,acronym,abbr,dfn,h1,h2,h3',
            'tplValues' => ['EntryContent'],
            'pubPages'  => ['post.html'],
        ]);

        return 'abbreviation';
    }

    public function publicContent($tag, $args)
    {
        while ($this->records()->fetch()) {
            $args[0] = libEPC::replaceString(
                $this->records()->epc_key,
                sprintf($this->replace, __($this->records()->epc_value), '\\1'),
                $args[0],
                $this
            );
        }

        return null;
    }

    public function widgetList($content, $w, &$list)
    {
        while ($this->records()->fetch()) {
            $list[] = libEPC::matchString(
                $this->records()->epc_key,
                sprintf($this->widget, __($this->records()->epc_value), '\\1'),
                $content,
                $this
            );
        }

        return null;
    }
}

class epcFilterDefinition extends epcFilter
{
    protected function init(): string
    {
        $this->setProperties([
            'priority' => 800,
            'name'     => __('Definition'),
            'help'     => __('Explain some definition. First term of the list is the sample to define and second term the explanation.'),
            'has_list' => true,
            'htmltag'  => 'dfn',
            'class'    => ['dfn.epc-dfn'],
            'replace'  => '<dfn class="epc-dfn" title="%s">%s</dfn>',
            'widget'   => '<dfn class="epc-dfn" title="%s">%s</dfn>',
        ]);

        $this->setSettings([
            'style'     => ['font-weight: bold;'],
            'notag'     => 'a,acronym,abbr,dfn,h1,h2,h3',
            'tplValues' => ['EntryContent'],
            'pubPages'  => ['post.html'],
        ]);

        return 'definition';
    }

    public function publicContent($tag, $args)
    {
        while ($this->records()->fetch()) {
            $args[0] = libEPC::replaceString(
                $this->records()->epc_key,
                sprintf($this->replace, __($this->records()->epc_value), '\\1'),
                $args[0],
                $this
            );
        }

        return null;
    }

    public function widgetList($content, $w, &$list)
    {
        while ($this->records()->fetch()) {
            $list[] = libEPC::matchString(
                $this->records()->epc_key,
                sprintf($this->widget, __($this->records()->epc_value), '\\1'),
                $content,
                $this
            );
        }

        return null;
    }
}

class epcFilterCitation extends epcFilter
{
    protected function init(): string
    {
        $this->setProperties([
            'priority' => 600,
            'name'     => __('Citation'),
            'help'     => __('Highlight citation of people. First term of the list is the citation and second term the author.'),
            'has_list' => true,
            'htmltag'  => 'cite',
            'class'    => ['cite.epc-cite'],
            'replace'  => '<cite class="epc-cite" title="%s">%s</cite>',
            'widget'   => '<cite title="%s">%s</cite>',
        ]);

        $this->setSettings([
            'nocase'    => true,
            'style'     => ['font-style: italic;'],
            'notag'     => 'a,h1,h2,h3',
            'tplValues' => ['EntryContent'],
            'pubPages'  => ['post.html'],
        ]);

        return 'citation';
    }

    public function publicContent($tag, $args)
    {
        while ($this->records()->fetch()) {
            $args[0] = libEPC::replaceString(
                $this->records()->epc_key,
                sprintf($this->replace, __($this->records()->epc_value), '\\1'),
                $args[0],
                $this
            );
        }

        return null;
    }

    public function widgetList($content, $w, &$list)
    {
        while ($this->records()->fetch()) {
            $list[] = libEPC::matchString(
                $this->records()->epc_key,
                sprintf($this->widget, __($this->records()->epc_value), '\\1'),
                $content,
                $this
            );
        }

        return null;
    }
}

class epcFilterLink extends epcFilter
{
    protected function init(): string
    {
        $this->setProperties([
            'priority' => 500,
            'name'     => __('Link'),
            'help'     => __('Link some words. First term of the list is the term to link and second term the link.'),
            'has_list' => true,
            'htmltag'  => 'a',
            'class'    => ['a.epc-link'],
            'replace'  => '<a class="epc-link" title="%s" href="%s">%s</a>',
            'widget'   => '<a title="%s" href="%s">%s</a>',
        ]);

        $this->setSettings([
            'style'     => ['text-decoration: none; font-style: italic; color: #0000FF;'],
            'notag'     => 'a,h1,h2,h3',
            'tplValues' => ['EntryContent'],
            'pubPages'  => ['post.html'],
        ]);

        return 'link';
    }

    public function publicContent($tag, $args)
    {
        while ($this->records()->fetch()) {
            $args[0] = libEPC::replaceString(
                $this->records()->epc_key,
                sprintf($this->replace, '\\1', $this->records()->epc_value, '\\1'),
                $args[0],
                $this
            );
        }

        return null;
    }

    public function widgetList($content, $w, &$list)
    {
        while ($this->records()->fetch()) {
            $list[] = libEPC::matchString(
                $this->records()->epc_key,
                sprintf($this->widget, $this->records()->epc_value, $this->records()->epc_value, '\\1'),
                $content,
                $this
            );
        }

        return null;
    }
}

class epcFilterReplace extends epcFilter
{
    protected function init(): string
    {
        $this->setProperties([
            'priority' => 200,
            'name'     => __('Replace'),
            'help'     => __('Replace some text. First term of the list is the text to replace and second term the replacement.'),
            'has_list' => true,
            'htmltag'  => '',
            'class'    => ['span.epc-replace'],
            'replace'  => '<span class="epc-replace">%s</span>',
        ]);

        $this->setSettings([
            'nocase'    => true,
            'plural'    => true,
            'style'     => ['font-style: italic;'],
            'notag'     => 'h1,h2,h3',
            'tplValues' => ['EntryContent'],
            'pubPages'  => ['post.html'],
        ]);

        return 'replace';
    }

    public function publicContent($tag, $args)
    {
        while ($this->records()->fetch()) {
            $args[0] = libEPC::replaceString(
                $this->records()->epc_key,
                sprintf($this->replace, $this->records()->epc_value, '\\2'),
                $args[0],
                $this
            );
        }

        return null;
    }
}

class epcFilterUpdate extends epcFilter
{
    protected function init(): string
    {
        $this->setProperties([
            'priority' => 300,
            'name'     => __('Update'),
            'help'     => __('Update and show terms. First term of the list is the term to update and second term the new term.'),
            'has_list' => true,
            'htmltag'  => 'del,ins',
            'class'    => ['del.epc-update', 'ins.epc-update'],
            'replace'  => '<del class="epc-update">%s</del> <ins class="epc-update">%s</ins>',
        ]);

        $this->setSettings([
            'nocase'    => true,
            'plural'    => true,
            'style'     => ['text-decoration: line-through;', 'font-style: italic;'],
            'notag'     => 'h1,h2,h3',
            'tplValues' => ['EntryContent'],
            'pubPages'  => ['post.html'],
        ]);

        return 'update';
    }

    public function publicContent($tag, $args)
    {
        while ($this->records()->fetch()) {
            $args[0] = libEPC::replaceString(
                $this->records()->epc_key,
                sprintf($this->replace, '\\1', $this->records()->epc_value),
                $args[0],
                $this
            );
        }

        return null;
    }
}

class epcFilterTwitter extends epcFilter
{
    protected function init(): string
    {
        $this->setProperties([
            'priority' => 1000,
            'name'     => __('Twitter'),
            'help'     => __('Add link to twitter user page. Every word started with "@" will be considered as twitter user.'),
            'htmltag'  => 'a',
            'class'    => ['a.epc-twitter'],
            'replace'  => '<a class="epc-twitter" title="' . __("View this user's twitter page") . '" href="%s">%s</a>',
        ]);

        $this->setSettings([
            'style'     => ['text-decoration: none; font-weight: bold; font-style: italic; color: #0000FF;'],
            'notag'     => 'a,h1,h2,h3',
            'tplValues' => ['EntryContent'],
            'pubPages'  => ['post.html'],
        ]);

        return 'twitter';
    }

    public function publicContent($tag, $args)
    {
        $args[0] = libEPC::replaceString(
            '[A-Za-z0-9_]{2,}',
            sprintf($this->replace, 'http://twitter.com/\\1', '\\1'),
            $args[0],
            $this,
            '[^@]@',
            '\b'
        );

        return null;
    }
}
