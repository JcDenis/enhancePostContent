<?php
if (!defined('DC_RC_PATH')) {
    return null;
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
            $args[0] = enhancePostContent::replaceString(
                $this->records()->epc_key,
                sprintf($this->replace, $this->records()->epc_value, '\\2'),
                $args[0],
                $this
            );
        }

        return null;
    }
}
