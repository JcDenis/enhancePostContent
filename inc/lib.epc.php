<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of enhancePostContent, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2013 Jean-Christian Denis and contributors
# contact@jcdenis.fr http://jcd.lv
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

# l10n
__('Tag');__('Search');__('Acronym');__('Abbreviation');__('Definition');
__('Citation');__('Link');__('Replace');__('Update');__('Twitter');
__('entry excerpt');__('entry content');__('comment content');
__('home page');__('post page');__('category page');__('search results page');
__('atom feeds');__('RSS feeds');

class libEPC
{
	#
	# Default definition
	#
	
	public static function defaultAllowedTplValues()
	{
		return array(
			'entry excerpt' => 'EntryExcerpt',
			'entry content' => 'EntryContent',
			'comment content' => 'CommentContent',
		);
	}
	
	public static function blogAllowedTplValues()
	{
		global $core;
		$core->blog->settings->addNamespace('enhancePostContent');
		$allowedtplvalues = self::defaultAllowedTplValues();
		$rs = @unserialize($core->blog->settings->enhancePostContent->enhancePostContent_allowedtplvalues);
		return is_array($rs) ? $rs : $allowedtplvalues;
	}
	
	public static function defaultAllowedWidgetValues()
	{
		global $core;
		
		$rs = array(
			'entry excerpt' => array(
				'id' => 'entryexcerpt',
				'callback' => array('libEPC','widgetContentEntryExcerpt')
			),
			'entry content' => array(
				'id' => 'entrycontent',
				'callback' => array('libEPC','widgetContentEntryContent')
			),
			'comment content' => array(
				'id' => 'commentcontent',
				'callback' => array('libEPC','widgetContentCommentContent')
			)
		);
		
		$core->callBehavior('enhancePostContentAllowedWidgetValues',$rs);
		
		return $rs;
	}
	
	public static function defaultAllowedPubPages()
	{
		return array(
			'home page' => 'home.html',
			'post page' => 'post.html',
			'category page' => 'category.html',
			'search results page' => 'search.html',
			'atom feeds' => 'atom.xml',
			'RSS feeds' => 'rss2.xml'
		);
	}
	
	public static function blogAllowedPubPages()
	{
		global $core;
		$core->blog->settings->addNamespace('enhancePostContent');
		$allowedpubpages = self::defaultAllowedPubPages();
		$rs = @unserialize($core->blog->settings->enhancePostContent->enhancePostContent_allowedpubpages);
		return is_array($rs) ? $rs : $allowedpubpages;
	}
	
	public static function defaultFilters()
	{
		global $core;
		
		$filters = array(
			'Tag' => array(
				'id' => 'tag',
				'publicContentFilter' => array('libEPC','publicContentFilterTag'),
				'widgetListFilter' => array('libEPC','widgetListTag'),
				
				'help' => __('Highlight tags of your blog.'),
				'has_list' => false,
				'htmltag' => 'a',
				'class' => array('a.epc-tag'),
				'replace' => '<a class="epc-tag" href="%s" title="'.__('Tag').'">%s</a>',
				'widget' =>  '<a href="%s" title="'.__('Tag').'">%s</a>',
				
				'nocase' => false,
				'plural' => false,
				'limit' => 0,
				'style' => array('text-decoration: none; border-bottom: 3px double #CCCCCC;'),
				'notag' => 'a,h1,h2,h3',
				'tplValues' => array('EntryContent'),
				'pubPages' => array('post.html')
			),
			'Search' => array(
				'id' => 'search',
				'publicContentFilter' => array('libEPC','publicContentFilterSearch'),
				
				'help' => __('Highlight searched words.'),
				'has_list' => false,
				'htmltag' => '',
				'class' => array('span.epc-search'),
				'replace' => '<span class="epc-search" title="'.__('Search').'">%s</span>',
				
				'nocase' => true,
				'plural' => true,
				'limit' => 0,
				'style' => array('color: #FFCC66;'),
				'notag' => 'h1,h2,h3',
				'tplValues' => array('EntryContent'),
				'pubPages' => array('search.html')
			),
			'Acronym' => array(
				'id' => 'acronym',
				'publicContentFilter' => array('libEPC','publicContentFilterAcronym'),
				'widgetListFilter' => array('libEPC','widgetListAcronym'),
				
				'help' => __('Explain some acronyms. First term of the list is the acornym and second term the explanation.'),
				'has_list' => true,
				'htmltag' => 'acronym',
				'class' => array('acronym.epc-acronym'),
				'replace' => '<acronym class="epc-acronym" title="%s">%s</acronym>',
				'widget' => '<acronym title="%s">%s</acronym>',
				
				'nocase' => false,
				'plural' => false,
				'limit' => 0,
				'style' => array('font-weight: bold;'),
				'notag' => 'a,acronym,abbr,dfn,h1,h2,h3',
				'tplValues' => array('EntryContent'),
				'pubPages' => array('post.html'),
			),
			'Abbreviation' => array(
				'id' => 'abbreviation',
				'publicContentFilter' => array('libEPC','publicContentFilterAbbreviation'),
				'widgetListFilter' => array('libEPC','widgetListAbbreviation'),
				
				'help' => __('Explain some abbreviation. First term of the list is the abbreviation and second term the explanation.'),
				'has_list' => true,
				'htmltag' => 'a',
				'class' => array('abbr.epc-abbr'),
				'replace' => '<abbr class="epc-abbr" title="%s">%s</abbr>',
				'widget' =>  '<abbr title="%s">%s</abbr>',
				
				'nocase' => false,
				'plural' => false,
				'limit' => 0,
				'style' => array('font-weight: bold;'),
				'notag' => 'a,acronym,abbr,dfn,h1,h2,h3',
				'tplValues' => array('EntryContent'),
				'pubPages' => array('post.html'),
			),
			'Definition' => array(
				'id' => 'definition',
				'publicContentFilter' => array('libEPC','publicContentFilterDefinition'),
				'widgetListFilter' => array('libEPC','widgetListDefinition'),
				
				'help' => __('Explain some definition. First term of the list is the sample to define and second term the explanation.'),
				'has_list' => true,
				'htmltag' => 'dfn',
				'class' => array('dfn.epc-dfn'),
				'replace' => '<dfn class="epc-dfn" title="%s">%s</dfn>',
				'widget' =>  '<dfn class="epc-dfn" title="%s">%s</dfn>',
				
				'nocase' => false,
				'plural' => false,
				'limit' => 0,
				'style' => array('font-weight: bold;'),
				'notag' => 'a,acronym,abbr,dfn,h1,h2,h3',
				'tplValues' => array('EntryContent'),
				'pubPages' => array('post.html'),
			),
			'Citation' => array(
				'id' => 'citation',
				'publicContentFilter' => array('libEPC','publicContentFilterCitation'),
				'widgetListFilter' => array('libEPC','widgetListCitation'),
				
				'help' => __('Highlight citation of people. First term of the list is the citation and second term the author.'),
				'has_list' => true,
				'htmltag' => 'cite',
				'class' => array('cite.epc-cite'),
				'replace' => '<cite class="epc-cite" title="%s">%s</cite>',
				'widget' => '<cite title="%s">%s</cite>',
				
				'nocase' => true,
				'plural' => false,
				'limit' => 0,
				'style' => array('font-style: italic;'),
				'notag' => 'a,h1,h2,h3',
				'tplValues' => array('EntryContent'),
				'pubPages' => array('post.html'),
			),
			'Link' => array(
				'id' => 'link',
				'publicContentFilter' => array('libEPC','publicContentFilterLink'),
				'widgetListFilter' => array('libEPC','widgetListLink'),
				
				'help' => __('Link some words. First term of the list is the term to link and second term the link.'),
				'has_list' => true,
				'htmltag' => 'a',
				'class' => array('a.epc-link'),
				'replace' => '<a class="epc-link" title="%s" href="%s">%s</a>',
				'widget' => '<a title="%s" href="%s">%s</a>',
				
				'nocase' => false,
				'plural' => false,
				'limit' => 0,
				'style' => array('text-decoration: none; font-style: italic; color: #0000FF;'),
				'notag' => 'a,h1,h2,h3',
				'tplValues' => array('EntryContent'),
				'pubPages' => array('post.html'),
			),
			'Replace' => array(
				'id' => 'replace',
				'publicContentFilter' => array('libEPC','publicContentFilterReplace'),
				
				'help' => __('Replace some text. First term of the list is the text to replace and second term the replacement.'),
				'has_list' => true,
				'htmltag' => '',
				'class' => array('span.epc-replace'),
				'replace' => '<span class="epc-replace">%s</span>',
				
				'nocase' => true,
				'plural' => true,
				'limit' => 0,
				'style' => array('font-style: italic;'),
				'notag' => 'h1,h2,h3',
				'tplValues' => array('EntryContent'),
				'pubPages' => array('post.html'),
			),
			'Update' => array(
				'id' => 'update',
				'publicContentFilter' => array('libEPC','publicContentFilterUpdate'),
				
				'help' => __('Update and show terms. First term of the list is the term to update and second term the new term.'),
				'has_list' => true,
				'htmltag' => 'del,ins',
				'class' => array('del.epc-update','ins.epc-update'),
				'replace' => '<del class="epc-update">%s</del> <ins class="epc-update">%s</ins>',
				
				'nocase' => true,
				'plural' => true,
				'limit' => 0,
				'style' => array('text-decoration: line-through;','font-style: italic;'),
				'notag' => 'h1,h2,h3',
				'tplValues' => array('EntryContent'),
				'pubPages' => array('post.html'),
			),
			'Twitter' => array(
				'id' => 'twitter',
				'publicContentFilter' => array('libEPC','publicContentFilterTwitter'),
				
				'help' => __('Add link to twitter user page. Every word started with "@" will be considered as twitter user.'),
				'has_list' => false,
				'htmltag' => 'a',
				'class' => array('a.epc-twitter'),
				'replace' => '<a class="epc-twitter" title="'.__("View this user's twitter page").'" href="%s">%s</a>',
				
				'nocase' => false,
				'plural' => false,
				'limit' => 0,
				'style' => array('text-decoration: none; font-weight: bold; font-style: italic; color: #0000FF;'),
				'notag' => 'a,h1,h2,h3',
				'tplValues' => array('EntryContent'),
				'pubPages' => array('post.html')
			)
		);
		
		$core->callBehavior('enhancePostContentDefaultFilters',$filters);
		
		return $filters;
	}
	
	public static function blogFilters($one=null)
	{
		global $core;
		$core->blog->settings->addNamespace('enhancePostContent');
		$filters = self::defaultFilters();
		
		foreach($filters as $name => $filter)
		{
			# Parse filters options
			$ns = 'enhancePostContent_'.$name;
			$opt[$name] = @unserialize($core->blog->settings->enhancePostContent->$ns);
			
			if (!is_array($opt[$name]))
			{
				$opt[$name] = array();
			}
			if (isset($opt[$name]['nocase']))
			{
				$filters[$name]['nocase'] = (boolean) $opt[$name]['nocase'];
			}
			if (isset($opt[$name]['plural']))
			{
				$filters[$name]['plural'] = (boolean) $opt[$name]['plural'];
			}
			if (isset($opt[$name]['limit']))
			{
				$filters[$name]['limit'] = abs((integer) $opt[$name]['limit']);
			}
			if (isset($opt[$name]['style']) && is_array($opt[$name]['style']))
			{
				$filters[$name]['style'] = (array) $opt[$name]['style'];
			}
			if (isset($opt[$name]['notag']))
			{
				$filters[$name]['notag'] = (string) $opt[$name]['notag'];
			}
			if (isset($opt[$name]['tplValues']))
			{
				$filters[$name]['tplValues'] = (array) $opt[$name]['tplValues'];
			}
			if (isset($opt[$name]['pubPages']))
			{
				$filters[$name]['pubPages'] = (array) $opt[$name]['pubPages'];
			}
		}
		
		$core->callBehavior('enhancePostContentBlogFilters',$filters);
		
		return $filters;
	}

	public static function testContext($tag,$args,$opt)
	{
		return 
			isset($opt['pubPages']) 
		 && is_array($opt['pubPages']) 
		 && in_array($GLOBALS['_ctx']->current_tpl,$opt['pubPages'])
		 &&	isset($opt['tplValues']) 
		 && is_array($opt['tplValues']) 
		 && in_array($tag,$opt['tplValues']) 
		 && $args[0] != '' //content
		 && !$args[2] // remove html
		;
	}
	
	public static function replaceString($p,$r,$s,$filter,$before='\b',$after='\b')
	{
		# Limit
		if ($filter['limit'] > 0)
		{
			$l = isset($GLOBALS['epcFilterLimit'][$filter['id']][$p]) ? $GLOBALS['epcFilterLimit'][$filter['id']][$p] : $filter['limit'];
			if ($l < 1) return $s;
		} else {
			$l = -1;
		}
		# Case sensitive
		$i = $filter['nocase'] ? 'i' : '';
		# Plural
		$x = $filter['plural'] ? $p.'s|'.$p : $p;
		# Mark words
		$s = preg_replace('#('.$before.')('.$x.')('.$after.')#s'.$i,'$1ççççç$2ççççç$3',$s,-1,$count);
		# Nothing to parse
		if (!$count) return $s;
		# Remove words that are into unwanted html tags
		$tags = '';
		$ignore_tags = array_merge(self::decodeTags($filter['htmltag']),self::decodeTags($filter['notag']));
		if (is_array($ignore_tags) && !empty($ignore_tags))
		{
			$tags = implode('|',$ignore_tags);
		}
		if (!empty($tags))
		{
			$s = preg_replace_callback('#(<('.$tags.')[^>]*?>)(.*?)(</\\2>)#s',array('libEPC','removeTags'),$s);
		}
		# Remove words inside html tag (class, title, alt, href, ...)
		$s = preg_replace('#(ççççç('.$p.'(s|))ççççç)(?=[^<]+>)#s'.$i,'$2$4',$s);
		# Replace words by what you want (with limit)
		$s = preg_replace('#ççççç('.$p.'(s|))ççççç#s'.$i,$r,$s,$l,$count);
		# update limit
		$GLOBALS['epcFilterLimit'][$filter['id']][$p] = $l - $count;
		# Clean rest
		return $s = preg_replace('#ççççç(.*?)ççççç#s','$1',$s);
	}
	
	public static function matchString($p,$r,$s,$filter,$before='\b',$after='\b')
	{
		# Case sensitive
		$i = $filter['nocase'] ? 'i' : '';
		# Plural
		$x = $filter['plural'] ? $p.'s|'.$p : $p;
		# Mark words
		$t = preg_match_all('#'.$before.'('.$x.')'.$after.'#s'.$i,$s,$matches);
		# Nothing to parse
		if (!$t) return array('total'=>0,'matches'=>array());
		
		# Build array
		$m = array();
		$loop=0;
		foreach($matches[1] as $match)
		{
			$m[$loop]['key'] = $match;
			$m[$loop]['match'] = preg_replace('#('.$p.'(s|))#s'.$i,$r,$match,-1,$count);
			$m[$loop]['num'] = $count;
			$loop++;
		}
		return array('total'=>$t,'matches'=>$m);
	}
	
	public static function quote($s)
	{
		return preg_quote($s,'#');
	}
	
	public static function removeTags($m)
	{
		return $m[1].preg_replace('#ççççç(?!ççççç)#s','$1',$m[3]).$m[4];
	}
	
	public static function decodeTags($t)
	{
		return preg_match_all('#([A-Za-z0-9]+)#',(string) $t, $m) ? $m[1] : array();
	}
	
	public static function implode($a)
	{
		if (is_string($a)) return $a;
		if (!is_array($a)) return array();
		
		$r = '';
		foreach($a as $k => $v)
		{
			$r .= $k.':'.$v.';';
		}
		return $r;
	}
	
	public static function explode($s)
	{
		if (is_array($s)) return $s;
		if (!is_string($s)) return '';
		
		$r = array();
		$s = explode(';',(string) $s);
		if (!is_array($s)) return array();
		
		foreach($s as $cpl)
		{
			$cur = explode(':',$cpl);
			
			if (!is_array($cur) || !isset($cur[1])) continue;
			
			$key = html::escapeHTML(trim($cur[0]));
			$val = html::escapeHTML(trim($cur[1]));
			
			if (empty($key) || empty($val)) continue;
			
			$r[$key] = $val;
		}
		return $r;
	}
	
	#
	# Widgets
	#
	
	public static function widgetContentEntryExcerpt($core,$w)
	{
		global $_ctx;
		if (!$_ctx->exists('posts')) return;
		
		$res = '';
		while ($_ctx->posts->fetch())
		{
			$res .= $_ctx->posts->post_excerpt;
		}
		return $res;
	}
	
	public static function widgetContentEntryContent()
	{
		global $_ctx;
		if (!$_ctx->exists('posts')) return;
		
		$res = '';
		while ($_ctx->posts->fetch())
		{
			$res .= $_ctx->posts->post_content;
		}
		return $res;
	}
	
	public static function widgetContentCommentContent()
	{
		global $core, $_ctx;
		if (!$_ctx->exists('posts')) return;
		
		$res = '';
		$post_ids = array();
		while ($_ctx->posts->fetch())
		{
			$comments = $core->blog->getComments(array('post_id'=>$_ctx->posts->post_id));
			while ($comments->fetch())
			{
				$res .= $comments->getContent();
			}
		}
		return $res;
	}
	
	#
	# Filters
	#
	
	public static function publicContentFilterTag($core,$filter,$tag,$args)
	{
		if (!$core->plugins->moduleExists('tags')) return;
		
		$metas = $core->meta->getMetadata(array('meta_type'=>'tag'));
		
		while($metas->fetch())
		{
			$k = $metas->meta_id;
			$args[0] = self::replaceString(
				$k,
				sprintf($filter['replace'],$core->blog->url.$core->url->getBase('tag').'/'.$k,'\\1'),
				$args[0],
				$filter
			);
		}
		return;
	}
	
	public static function widgetListTag($core,$filter,$content,$w,&$list)
	{
		if (!$core->plugins->moduleExists('tags')) return;

		$metas = $core->meta->getMetadata(array('meta_type'=>'tag'));
		
		while($metas->fetch())
		{
			$k = $metas->meta_id;
			$list[] = self::matchString(
				$k,
				sprintf($filter['widget'],$core->blog->url.$core->url->getBase('tag').'/'.$k,'\\1'),
				$content,
				$filter
			);
		}
		return;
	}
	
	public static function publicContentFilterSearch($core,$filter,$tag,$args)
	{
		if (!isset($GLOBALS['_search'])) return;
		
		$searchs = explode(' ',$GLOBALS['_search']);
		
		foreach($searchs as $k => $v)
		{
			$args[0] = self::replaceString(
				$v,
				sprintf($filter['replace'],'\\1'),
				$args[0],
				$filter
			);
		}
		return;
	}
	
	public static function publicContentFilterAcronym($core,$filter,$tag,$args)
	{
		while($filter['list']->fetch())
		{
			$k = $filter['list']->epc_key;
			$v = $filter['list']->epc_value;
			
			$args[0] = self::replaceString(
				$k,
				sprintf($filter['replace'],__($v),'\\1'),
				$args[0],
				$filter
			);
		}
		return;
	}
	
	public static function widgetListAcronym($core,$filter,$content,$w,&$list)
	{
		while($filter['list']->fetch())
		{
			$k = $filter['list']->epc_key;
			$v = $filter['list']->epc_value;
			
			$list[] = self::matchString(
				$k,
				sprintf($filter['widget'],__($v),'\\1'),
				$content,
				$filter
			);
		}
		return;
	}
	
	public static function publicContentFilterAbbreviation($core,$filter,$tag,$args)
	{
		while($filter['list']->fetch())
		{
			$k = $filter['list']->epc_key;
			$v = $filter['list']->epc_value;
			
			$args[0] = self::replaceString(
				$k,
				sprintf($filter['replace'],__($v),'\\1'),
				$args[0],
				$filter
			);
		}
		return;
	}
	
	public static function widgetListAbbreviation($core,$filter,$content,$w,&$list)
	{
		while($filter['list']->fetch())
		{
			$k = $filter['list']->epc_key;
			$v = $filter['list']->epc_value;
			
			$list[] = self::matchString(
				$k,
				sprintf($filter['widget'],__($v),'\\1'),
				$content,
				$filter
			);
		}
		return;
	}
	
	public static function publicContentFilterDefinition($core,$filter,$tag,$args)
	{
		while($filter['list']->fetch())
		{
			$k = $filter['list']->epc_key;
			$v = $filter['list']->epc_value;
			
			$args[0] = self::replaceString(
				$k,
				sprintf($filter['replace'],__($v),'\\1'),
				$args[0],
				$filter
			);
		}
		return;
	}
	
	public static function widgetListDefinition($core,$filter,$content,$w,&$list)
	{
		while($filter['list']->fetch())
		{
			$k = $filter['list']->epc_key;
			$v = $filter['list']->epc_value;
			
			$list[] = self::matchString(
				$k,
				sprintf($filter['widget'],__($v),'\\1'),
				$content,
				$filter
			);
		}
		return;
	}
	
	public static function publicContentFilterCitation($core,$filter,$tag,$args)
	{
		while($filter['list']->fetch())
		{
			$k = $filter['list']->epc_key;
			$v = $filter['list']->epc_value;
			
			$args[0] = self::replaceString(
				$k,
				sprintf($filter['replace'],__($v),'\\1'),
				$args[0],
				$filter
			);
		}
		return;
	}
	
	public static function widgetListCitation($core,$filter,$content,$w,&$list)
	{
		while($filter['list']->fetch())
		{
			$k = $filter['list']->epc_key;
			$v = $filter['list']->epc_value;
			
			$list[] = self::matchString(
				$k,
				sprintf($filter['widget'],__($v),'\\1'),
				$content,
				$filter
			);
		}
		return;
	}
	
	public static function publicContentFilterLink($core,$filter,$tag,$args)
	{
		while($filter['list']->fetch())
		{
			$k = $filter['list']->epc_key;
			$v = $filter['list']->epc_value;
			
			$args[0] = self::replaceString(
				$k,
				sprintf($filter['replace'],'\\1',$v,'\\1'),
				$args[0],
				$filter
			);
		}
		return;
	}
	
	public static function widgetListLink($core,$filter,$content,$w,&$list)
	{
		while($filter['list']->fetch())
		{
			$k = $filter['list']->epc_key;
			$v = $filter['list']->epc_value;
			
			$list[] = self::matchString(
				$k,
				sprintf($filter['widget'],$v,$v,'\\1'),
				$content,
				$filter
			);
		}
		return;
	}
	
	public static function publicContentFilterReplace($core,$filter,$tag,$args)
	{
		while($filter['list']->fetch())
		{
			$k = $filter['list']->epc_key;
			$v = $filter['list']->epc_value;
			
			$args[0] = self::replaceString(
				$k,
				sprintf($filter['replace'],$v,'\\2'),
				$args[0],
				$filter
			);
		}
		return;
	}
	
	public static function publicContentFilterUpdate($core,$filter,$tag,$args)
	{
		while($filter['list']->fetch())
		{
			$k = $filter['list']->epc_key;
			$v = $filter['list']->epc_value;
			
			$args[0] = self::replaceString(
				$k,
				sprintf($filter['replace'],'\\1',$v),
				$args[0],
				$filter
			);
		}
		return;
	}
	
	public static function publicContentFilterTwitter($core,$filter,$tag,$args)
	{
		$args[0] = self::replaceString(
			'[A-Za-z0-9_]{2,}',
			sprintf($filter['replace'],'http://twitter.com/\\1','\\1'),
			$args[0],
			$filter,
			'[^@]@','\b'
		);
		return;
	}
}
?>