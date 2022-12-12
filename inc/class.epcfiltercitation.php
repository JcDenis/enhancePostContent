<?php
if (!defined('DC_RC_PATH')) {
    return null;
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
            $args[0] = enhancePostContent::replaceString(
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
            $list[] = enhancePostContent::matchString(
                $this->records()->epc_key,
                sprintf($this->widget, __($this->records()->epc_value), '\\1'),
                $content,
                $this
            );
        }

        return null;
    }
}
