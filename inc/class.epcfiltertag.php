<?php
if (!defined('DC_RC_PATH')) {
    return null;
}

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
            $args[0] = enhancePostContent::replaceString(
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
            $list[] = enhancePostContent::matchString(
                $metas->meta_id,
                sprintf($this->widget, dcCore::app()->blog->url . dcCore::app()->url->getBase('tag') . '/' . $metas->meta_id, '\\1'),
                $content,
                $this
            );
        }

        return null;
    }
}
