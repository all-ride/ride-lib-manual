<?php

namespace ride\library\manual;

use ride\library\system\file\browser\FileBrowser;
use ride\library\system\file\File;

/**
 * Model of the manual
 */
class Manual {

    /**
     * Directory to write new pages to
     * @var \ride\library\system\file\File
     */
    protected $directory;

    /**
     * Instance of a file browser
     * @var \ride\library\system\file\browser\FileBrowser
     */
    protected $fileBrowser;

    /**
     * Relative path in the file browser
     * @var string
     */
    protected $path;

    /**
     * Index of all available pages
     * @var array
     */
    protected $index;

    /**
     * Constructs a new manual model
     * @param ride\library\system\file\File $directory Directory to write
     * @param ride\library\system\file\browser\FileBrowser $fileBrowser File
     * browser to lookup manual pages
     * edited pages to
     * @param string $path Relative path for the file browser
     * @return null
     */
    public function __construct(File $directory, FileBrowser $fileBrowser = null, $path = null) {
        $this->fileBrowser = $fileBrowser;
        $this->directory = $directory;
        $this->path = rtrim($path, '/');

        $this->indexPages();
    }

    /**
     * Checks if a page exists
     * @param string $name URL encoded name
     * @param string $path The path of the page
     * @return boolean
     */
    public function hasPage($name, $path = '/') {
        return isset($this->index[$path][$name]);
    }

    /**
     * Gets the reference
     * @param string $name Name of the reference
     * @return array Array with the term as key and the description as value
     */
    public function getReference($name) {
        $reference = array();

        $files = $this->fileBrowser->getFiles($this->path . '/' . $name . '.ref');
        foreach ($files as $file) {
            $content = $file->read();

            $lines = explode("\n", $content);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!$line) {
                    continue;
                }

                $tokens = explode(' ', $line, 2);

                if (count($tokens) != 2) {
                    continue;
                }

                $reference[$tokens[0]] = $tokens[1];
            }
        }

        ksort($reference);

        return $reference;
    }

    /**
     * Gets a page
     * @param string $name URL encoded name
     * @param string $path The path of the page
     * @return Page|null
     */
    public function getPage($name, $path = '/') {
        if (!$name) {
            return null;
        }

        $file = $this->directory->getChild(ltrim($path, '/') . $name . '.md');
        if (!$file->exists()) {
            if (!$this->fileBrowser) {
                return null;
            }

            $file = $this->fileBrowser->getFile($this->path . $path . $name . '.md');
            if (!$file) {
                return null;
            }
        }

        $title = urldecode($name);
        $content = $file->read();
        $dateModified = $file->getModificationTime();

        $page = new Page($title, $content, $path, $dateModified);

        $this->index[$path][$name] = $page;

        return $page;
    }

    /**
     * Saves a page into the application directory
     * @param Page $page The page to save
     * @return null
     */
    public function savePage(Page $page) {
        $path = $page->getPath(true);
        if ($path) {
            $path .= '/';
        }

        $path .= $page->getName() . '.md';

        $file = $this->directory->getChild($path);

        $parent = $file->getParent();
        $parent->create();

        $file->write($page->getContent());
    }

    /**
     * Searches in the contents of the page for the provided query
     * @param string $query The search query
     * @return array Array with the name of the page as key and a Page instance
     * as value
     */
    public function searchPages($query) {
        $query = trim($query);
        if (!$query) {
            return array();
        }

        $tokens = explode(' ', $query);

        $ratio = count($tokens);
        if ($ratio == 1) {
            $tokens = array();
        } else {
            $ratio++;
        }
        $ratio = round(100 / $ratio);

        $result = new SearchResult();

        foreach ($this->index as $path => $pages) {
            foreach ($pages as $name => $null) {
                $page = $this->getPage($name, $path);
                $content = $page->getContent();

                if (strpos($content, $query) !== false) {
                    $result->addPage($page, 100);

                    continue;
                }

                $pageRatio = 0;

                foreach ($tokens as $token) {
                    if (strpos($content, $token) !== false) {
                        $pageRatio += $ratio;
                    }
                }

                if ($pageRatio) {
                    $result->addPage($page, $pageRatio);
                }
            }
        }

        return $result->getResult();
    }

    /**
     * Gets the index of the available pages
     * @return array Array with the name of the page as key
     */
    public function getIndex() {
        return $this->index;
    }

    /**
     * Generates the index of the manual pages
     * @return null
     */
    protected function indexPages() {
        $this->index = array();

        if ($this->fileBrowser) {
            $includeDirectories = array_reverse($this->fileBrowser->getIncludeDirectories());
        } else {
            $includeDirectories = array();
        }

        $includeDirectories[] = $this->directory;

        foreach ($includeDirectories as $includeDirectory) {
            if ($this->path) {
                $directory = $includeDirectory->getChild($this->path);
            } else {
                $directory = $includeDirectory;
            }

            if (!$directory->exists() || !$directory->isDirectory()) {
                continue;
            }

            $this->indexDirectory($directory);
        }
    }

    /**
     * Indexes the provided directory as a manual directory
     * @param ride\library\filesystem\File $directory
     * @param string $path Path for the files
     * @return null
     */
    protected function indexDirectory(File $directory, $path = '/') {
        $files = $directory->read();
        foreach ($files as $file) {
            if ($file->isDirectory()) {
                $this->indexDirectory($file, $path . $file->getName() . '/');

                continue;
            }

            if ($file->getExtension() != 'md') {
                continue;
            }

            if (!isset($this->index[$path])) {
                $this->index[$path] = array();
            }

            $name = substr($file->getName(), 0, -3);

            $this->index[$path][$name] = true;
        }
    }

}
