<?php

namespace ride\library\manual;

/**
 * Text processor to link page title occurences with an actual anchor
 */
class PageLinker {

    /**
     * The URL to the manual page detail
     * @var string
     */
    private $url;

    /**
     * The available pages
     * @var array
     */
    private $pages;

    /**
     * Constructs a new page linker
     * @param string $url The URL to the manual page detail with a %page%
     * placeholder for the page's slug
     * @param array $pages The available pages
     * @return null
     */
    public function __construct($url, array $pages) {
        $this->url = $url;
        $this->pages = $pages;
    }

    /**
     * Converts the occurences of page titles with the link thereof
     * @param string $content The content to process
     * @return string The processed content
     */
    public function process($content) {
        foreach ($this->pages as $name => $null) {
            $title = urldecode($name);

            $position = 0;

            do {
                $position = stripos($content, $title, $position);
                if ($position === false) {
                    continue;
                }

                $title = substr($content, $position, strlen($title));

                if (!$this->isLinkable(substr($content, 0, $position))) {
                    $position += 1;
                    continue;
                }

                $tmpContent = substr($content, 0, $position);
                $tmpContent .= '<a href="' . str_replace('%page%', $name, $this->url) . '">' . $title . '</a>';

                $tmpPosition = $position + strlen($title);
                $position = strlen($tmpContent);

                $tmpContent .= substr($content, $tmpPosition);

                $content = $tmpContent;
            } while ($position !== false);
        }

        return $content;
    }

    private function isLinkable($html) {
        $positionOpen = strrpos($html, '<');
        $positionClose = strrpos($html, '>');

        if ($positionOpen === false && $positionClose === false || $positionOpen === false && $positionClose !== false) {
            // no tag preceeding or no tag opened
            return true;
        }

        if ($positionOpen !== false && $positionClose === false || $positionOpen > $positionClose) {
            // inside a tag
            return false;
        }

        if ($html{$positionClose - 1} == '/') {
            // tag without closing tag
            return $this->isLinkable(substr($html, 0, $positionOpen));
        }

        if ($html{$positionOpen + 1} == '/') {
            // closing tag
            $positionTag = $positionOpen + 2;
            $tagName = trim(substr($html, $positionTag, $positionClose - 1));
            $positionOpen = $this->getOpenPosition($html, $tagName);

            return $this->isLinkable(substr($html, 0, $positionOpen));
        }

        // open tag
        $positionSpace = strpos($html, ' ', $positionOpen);
        $positionTagEnd = min($positionSpace, $positionClose);

        $tagName = trim(substr($html, $positionOpen + 1, $positionTagEnd - $positionOpen - 1));
        if ($tagName == 'a') {
            // inside a anchor
            return false;
        }

        // inside a not anchor
        return $this->isLinkable(substr($html, 0, $positionOpen));
    }

    private function getOpenPosition($html, $tagName) {
        $positionOpen = strrpos($html, '<');
        $positionClose = strrpos($html, '>');

        if ($positionOpen === false && $positionClose === false || $positionOpen === false && $positionClose !== false) {
            // no tag preceeding or no tag opened
            return false;
        }

        if ($positionOpen !== false && $positionClose === false || $positionOpen > $positionClose) {
            // inside a tag
            return false;
        }

        if ($html{$positionClose - 1} == '/') {
            // tag without closing tag
            return $this->getOpenPosition(substr($html, 0, $positionOpen), $tagName);
        }

        if ($html{$positionOpen + 1} == '/') {
            // closing tag
            $positionTag = $positionOpen + 2;
            $subTagName = trim(substr($html, $positionTag, $positionClose - 1));

            $openPosition = $this->getOpenPosition(substr($html, 0, $positionOpen), $subTagName);
            if ($openPosition === false) {
                return false;
            }

            return $this->getOpenPosition(substr($html, 0, $positionOpen), $tagName);
        }

        // open tag
        $positionSpace = strpos($html, ' ', $positionOpen);
        $positionTagEnd = min($positionSpace, $positionClose);

        $subTagName = trim(substr($html, $positionOpen + 1, $positionTagEnd - $positionOpen - 1));
        if ($subTagName == $tagName) {
            return $positionOpen;
        }

        return $this->getOpenPosition(substr($html, 0, $positionOpen), $tagName);
    }

}