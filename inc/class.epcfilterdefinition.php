<?php
if (!defined('DC_RC_PATH')) {
    return null;
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
