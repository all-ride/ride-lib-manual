<?php

namespace ride\library\manual;

use ride\library\decorator\Decorator;
use ride\library\html\HtmlParser;
use ride\library\String;

/**
 * Data container for a wiki page
 */
class Page {

    /**
     * Encoded name of the page
     * @var string
     */
    private $name;

    /**
     * Title of the page
     * @var string
     */
    private $title;

    /**
     * The content of the page
     * @var string
     */
    private $content;

    /**
     * The relative path of the page in the wiki directory
     * @var string
     */
    private $path;

    /**
     * Timestamp of the last modification
     * @var integer
     */
    private $dateModified;

    /**
     * The URL of the page
     * @var string
     */
    private $url;

    /**
     * Constructs a new page
     * @param string $title The title of the page
     * @param string $content The content of the page
     * @param string $path The path of the page
     * @param integer $dateModified Timestamp of the last modification
     * @return null
     */
    public function __construct($title, $content = null, $path = '/', $dateModified = null) {
        $this->setTitle($title);
        $this->setContent($content);
        $this->setPath($path);
        $this->setDateModified($dateModified);

        $this->url = null;
    }

    /**
     * Gets the name of the page
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Sets the title of the page
     * @param string $title
     * @return null
     */
    public function setTitle($title) {
        $this->name = urlencode($title);
        $this->title = $title;
    }

    /**
     * Gets the title of the page
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Sets the content of the page
     * @param string $content
     * @return null
     */
    public function setContent($content) {
        $this->content = $content;
    }

    /**
     * Gets the content of the page
     * @return string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * Gets the parsed markup of the page's content into HTML
     * @param \ride\library\decorator\Decorator $decorator Deorator for the body
     * @param string $baseUrl Base URL for the anchors
     * @param boolean $generateSubmenu Flag to see if the content should be
     * prefixed with a submenu based on content titles
     * @return string HTML of the content of the page
     */
    public function getParsedContent(Decorator $decorator, $baseUrl, $generateSubmenu = true) {
        if (!$this->content || !trim($this->content)) {
            return;
        }

        if ($generateSubmenu) {
            $replacements = array();
            $structure = $this->generateSubmenu($replacements);
        }

        $content = $decorator->decorate($this->content);

        $htmlParser = new HtmlParser($content);
        $htmlParser->makeAnchorsAbsolute($baseUrl);
        $content = $htmlParser->getHtml();

        if ($generateSubmenu && $structure) {
            $content = str_replace(array_keys($replacements), $replacements, $content);
            $content = '<div class="manual-structure">' . $structure . '</div>' . $content;
        }

        return $content;
    }

    /**
     * Generates a HTML submenu based on the titles
     * @param array $replacements Array with the text to replace as key and the
     * replacements as value. The replacements contain the titles prefixed with
     * a anchor destination for the submenu
     * @return string HTML of the submenu
     */
    protected function generateSubmenu(array &$replacements) {
        $html = '';
        $previousLevel = 0;
        $minLevel = 999;

        $lines = explode("\n", $this->content);
        foreach ($lines as $line) {
            if (strpos($line, '#') !== 0) {
                continue;
            }

            $level = 0;
            do {
                $char = substr($line, $level, 1);
                $level++;
            } while ($char == '#');
            $title = trim(substr($line, $level));
            $level -= 1;

            $slug = new String($title);
            $slug = $slug->safeString();

            $original = '<h' . $level . '>' . $title . '</h' . $level . '>';
            $replacement = '<a name="' . $slug . '"></a>' . $original;
            $replacements[$original] = $replacement;

            if ($previousLevel == 0) {
                $html .= "<ul>\n<li>";
            } elseif ($level > $previousLevel) {
                $html .= str_repeat("<ul>\n<li>", $level - $previousLevel);
            } elseif ($level < $previousLevel) {
                $html .= str_repeat("</li>\n</ul>\n</li>\n", $previousLevel - $level) . '<li>';
            } else {
                $html .= "</li>\n<li>";
            }

            $html .= '<a href="#' . $slug . '">' . $title . '</a>';

            $minLevel = min($minLevel, $level);
            $previousLevel = $level;
        }

        if ($minLevel != 999) {
            $multiplier = $previousLevel - $minLevel + 1;
            $html .= str_repeat("</li>\n</ul>\n", $multiplier);
        }

        return $html;
    }

    /**
     * Sets the path of the page in the wiki directory
     * @param string $path
     * @return null
     */
    public function setPath($path) {
        $path = trim($path, '/');

        if (!$path) {
            $path = '/';
        } else {
            $path = '/' . $path . '/';
        }

        $this->path = $path;
    }

    /**
     * Gets the path of the page in the wiki directory
     * @param boolean $trim True to trim the / before and after the path
     * @return string
     */
    public function getPath($trim = true) {
        if ($trim) {
            return trim($this->path, '/');
        }

        return $this->path;
    }

    /**
     * Gets the modification date of the page
     * @return integer Timestamp
     */
    protected function setDateModified($time) {
        $this->dateModified = $time;
    }

    /**
     * Gets the modification date of the page
     * @return integer Timestamp
     */
    public function getDateModified() {
        return $this->dateModified;
    }

    /**
     * Gets the route of the page.
     * @return string Path concatted with the name
     */
    public function getRoute() {
        return $this->path . $this->name;
    }

    /**
     * Sets the URL of the page
     * @param string $url
     * @return null
     */
    public function setUrl($url) {
        $this->url = $url;
    }

    /**
     * Gets the url of the page
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }

}
