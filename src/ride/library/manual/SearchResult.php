<?php

namespace ride\library\manual;

/**
 * Data container for a search result
 */
class SearchResult {

    /**
     * Nested array with the ratio as key and the pages as value
     * @var array
     */
    private $result;

    /**
     * Constructs a new search result
     * @return null
     */
    public function __construct() {
        $this->result = array();
    }

    /**
     * Adds a page to the search result
     * @param Page $page A page which matches the search query
     * @param integer $ratio Ratio of the search query that matched (0-100)
     * @return null
     */
    public function addPage(Page $page, $ratio) {
        if (!isset($this->result[$ratio])) {
            $this->result[$ratio] = array();
        }

        $this->result[$ratio][$page->getName()] = $page;
    }

    /**
     * Gets the result sorted by the ratio
     * @return array Array with the name of the page as key and a Page instance
     * as value
     */
    public function getResult() {
        ksort($this->result);

        $result = array();
        foreach ($this->result as $ratio => $pages) {
            foreach ($pages as $name => $page) {
                $result[$name] = $page;
            }
        }

        return $result;
    }

}