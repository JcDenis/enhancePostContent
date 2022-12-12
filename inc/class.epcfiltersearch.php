<?php
if (!defined('DC_RC_PATH')) {
    return null;
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
            $args[0] = enhancePostContent::replaceString(
                $v,
                sprintf($this->replace, '\\1'),
                $args[0],
                $this
            );
        }

        return null;
    }
}
