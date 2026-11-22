<?php

declare(strict_types=1);

namespace Dotclear\Plugin\enhancePostContent\ClaudetteMcp;

use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\ClaudetteMcp\McpToolException;
use Dotclear\Plugin\enhancePostContent\EpcRecord;
use Exception;

/**
 * @brief       enhancePostContent tool for the plugin ClaudetteMcp: add or update a term.
 * @ingroup     enhancePostContent
 *
 * Values are escaped as in the admin form. No deletion.
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class SetEnhancedTerm extends TermTool
{
    public function name(): string
    {
        return 'set_enhanced_term';
    }

    public function title(): string
    {
        return 'Add or update a term enhanced in contents';
    }

    public function description(): string
    {
        return 'Add a term to a filter of the plugin enhancePostContent (ex: an acronym and its meaning, a word and its link), ' .
            'or update one (with its ID from list_enhanced_terms). It changes how all the contents of the blog are displayed, at once. ' .
            'Terms can not be deleted here.';
    }

    public function properties(): array
    {
        return [
            'id' => [
                'type'        => 'integer',
                'description' => 'The ID of the term to update (see list_enhanced_terms), omit to add a new term.',
            ],
            'filter' => [
                'type'        => 'string',
                'enum'        => array_keys($this->filters()),
                'description' => 'The filter of a new term (see list_enhanced_terms for what its values mean).',
            ],
            'term' => [
                'type'        => 'string',
                'description' => 'The first value: the term found in contents.',
            ],
            'value' => [
                'type'        => 'string',
                'description' => 'The second value: explanation, link, replacement... depending on the filter.',
            ],
        ];
    }

    public function readOnly(): bool
    {
        return false;
    }

    public function call(array $args): array
    {
        $filters = $this->filters();
        $id      = $this->int($args, 'id');
        $current = null;

        if ($id !== 0) {
            $rs = EpcRecord::getRecords(['epc_id' => $id, 'epc_filter' => array_keys($filters)]);
            if ($rs->isEmpty()) {
                throw new McpToolException('Unknown term, see list_enhanced_terms');
            }
            $current = $this->describeTerm($rs);
        }

        $filter = is_string($f = $current['filter'] ?? null) ? $f : $this->str($args, 'filter');
        $term   = $this->has($args, 'term') ? $this->str($args, 'term') : (is_string($t = $current['term'] ?? null) ? $t : '');
        $value  = $this->has($args, 'value') ? $this->str($args, 'value') : (is_string($v = $current['value'] ?? null) ? $v : '');

        if (!isset($filters[$filter])) {
            throw new McpToolException('Unknown filter, use one of: ' . implode(', ', array_keys($filters)));
        }
        if ($term === '' || $value === '') {
            throw new McpToolException('A term needs its two values');
        }

        // Escaped as in the admin form
        $key = Html::escapeHTML($term);
        if (EpcRecord::isRecord($filter, $key, $id !== 0 ? $id : null)) {
            throw new McpToolException('This term already exists in this filter, see list_enhanced_terms');
        }

        $cur = EpcRecord::openCursor();
        $cur->setStrField('epc_filter', $filter);
        $cur->setStrField('epc_key', $key);
        $cur->setStrField('epc_value', Html::escapeHTML($value));

        try {
            if ($id === 0) {
                $id = EpcRecord::addRecord($cur);
            } else {
                EpcRecord::updRecord($id, $cur);
            }
        } catch (Exception $e) {
            throw new McpToolException($e->getMessage());
        }

        return $this->describeTerm(EpcRecord::getRecords(['epc_id' => $id]));
    }
}
