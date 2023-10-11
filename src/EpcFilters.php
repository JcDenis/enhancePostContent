<?php

declare(strict_types=1);

namespace Dotclear\Plugin\enhancePostContent;

/**
 * @brief   enhancePostContent filters stack.
 * @ingroup enhancePostContent
 *
 * Use Epc::getFilters() to get loaded stack
 *
 * @author      Jean-Christian Denis
 * @copyright   Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class EpcFilters
{
    /** @var 	array<int,EpcFilter> 	$satck 	The filters stack */
    private array $stack = [];

    /**
     * Add a filter to the stack.
     *
     * @return 	EpcFilters 	The filters instance
     */
    public function add(EpcFilter $filter): EpcFilters
    {
        $this->stack[$filter->id()] = $filter;

        return $this;
    }

    /**
     * Get all filters.
     *
     * @return 	array<string,EpcFilter> 	The filters stack
     */
    public function dump(): array
    {
        return $this->stack;
    }

    /**
     * Get a filter.
     *
     * @param 	string 	$id 	The filter ID
     *
     * @return null|EpcFilter 	The filter
     */
    public function get(string $id): ?EpcFilter
    {
        return $this->stack[$id] ?? null;
    }

    /**
     * Get filters name / id pair.
     *
     * @return 	array 	The nid pairs
     */
    public function nid(bool $exclude_widget = false): array
    {
        $nid = [];
        foreach ($this->stack as $filter) {
            if (!$exclude_widget || $filter->widget != '') {
                $nid[$filter->name] = $filter->id();
            }
        }

        return $nid;
    }

    /**
     * Sort filters stack by filter name or priority.
     *
     * @return 	EpcFilters 	The filters instance
     */
    public function sort(bool $by_name = false): EpcFilters
    {
        if ($by_name) {
            uasort($this->stack, fn ($a, $b) => $a->name <=> $b->name);
        } else {
            uasort($this->stack, fn ($a, $b) => $a->priority <=> $b->priority);
        }

        return $this;
    }
}
