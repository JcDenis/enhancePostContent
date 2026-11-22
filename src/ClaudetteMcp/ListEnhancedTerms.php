<?php

declare(strict_types=1);

namespace Dotclear\Plugin\enhancePostContent\ClaudetteMcp;

use Dotclear\Plugin\ClaudetteMcp\McpToolException;
use Dotclear\Plugin\enhancePostContent\EpcRecord;

/**
 * @brief       enhancePostContent tool for the plugin ClaudetteMcp: list terms.
 * @ingroup     enhancePostContent
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ListEnhancedTerms extends TermTool
{
    public function name(): string
    {
        return 'list_enhanced_terms';
    }

    public function title(): string
    {
        return 'List terms enhanced in contents';
    }

    public function description(): string
    {
        return 'List the terms the plugin enhancePostContent enhances in the contents of the blog (acronyms, abbreviations, definitions, links, replacements...), ' .
            'by filter, with the filters and what their two values mean.';
    }

    public function properties(): array
    {
        return [
            'filter' => [
                'type'        => 'string',
                'enum'        => array_keys($this->filters()),
                'description' => 'Limit to a filter.',
            ],
            'limit' => [
                'type'        => 'integer',
                'description' => 'Terms per page, 1 to 200, default: 100.',
            ],
            'page' => [
                'type'        => 'integer',
                'description' => 'Page number, default: 1.',
            ],
        ];
    }

    public function call(array $args): array
    {
        $filters = $this->filters();
        $limit   = $this->int($args, 'limit', 100, 1, 200);
        $page    = $this->int($args, 'page', 1, 1);
        $params  = ['epc_filter' => array_keys($filters), 'order' => 'E.epc_filter ASC, E.epc_key ASC'];

        $filter = $this->str($args, 'filter');
        if ($filter !== '') {
            if (!isset($filters[$filter])) {
                throw new McpToolException('Unknown filter, use one of: ' . implode(', ', array_keys($filters)));
            }
            $params['epc_filter'] = $filter;
        }

        $total = EpcRecord::getRecords($params, true)->f(0);

        $params['limit'] = [($page - 1) * $limit, $limit];

        $rs    = EpcRecord::getRecords($params);
        $terms = [];
        while ($rs->fetch()) {
            $terms[] = $this->describeTerm($rs);
        }

        return [
            'filters' => $filters,
            'total'   => is_numeric($total) ? (int) $total : 0,
            'page'    => $page,
            'limit'   => $limit,
            'terms'   => $terms,
        ];
    }
}
