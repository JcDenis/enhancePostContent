<?php

declare(strict_types=1);

namespace Dotclear\Plugin\enhancePostContent\ClaudetteMcp;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\ClaudetteMcp\McpTool;
use Dotclear\Plugin\enhancePostContent\Epc;
use Dotclear\Plugin\enhancePostContent\My;

/**
 * @brief       enhancePostContent tools helper for the plugin ClaudetteMcp.
 * @ingroup     enhancePostContent
 *
 * ClaudetteMcp connects an AI assistant to the blog: these tools let the assistant manage the terms
 * of the filters with a list (acronyms, abbreviations, definitions, links...).
 * These classes are only loaded by ClaudetteMcp, which is not required by enhancePostContent.
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
abstract class TermTool extends McpTool
{
    /**
     * Family of tools for the keys of ClaudetteMcp (string, not constant: older ClaudetteMcp versions have none).
     */
    public function family(): string
    {
        return 'extras';
    }

    /**
     * Like in backend: the plugin must be active on the blog, its page is for content administrators.
     */
    public function available(): bool
    {
        return My::settings()->getBool('active', false) && $this->can(App::auth()::PERMISSION_CONTENT_ADMIN);
    }

    /**
     * Get filters with a list of terms, by ID.
     *
     * @return  array<string, string>   Filters descriptions
     */
    protected function filters(): array
    {
        $filters = [];
        foreach (Epc::getFilters()->dump() as $filter) {
            if ($filter->has_list) {
                $filters[$filter->id()] = $filter->name . ': ' . $filter->description;
            }
        }

        return $filters;
    }

    /**
     * Describe a term.
     *
     * @return  array<string, mixed>
     */
    protected function describeTerm(MetaRecord $rs): array
    {
        return [
            'id'     => $rs->intField('epc_id'),
            'filter' => $rs->strField('epc_filter'),
            'term'   => Html::decodeEntities($rs->strField('epc_key')),
            'value'  => Html::decodeEntities($rs->strField('epc_value')),
        ];
    }
}
