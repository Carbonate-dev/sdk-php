<?php

namespace Carbonate\Browser;

use Facebook\WebDriver\WebDriverElement;

interface BrowserInterface
{
    public function getHtml();

    public function load($url, $whitelist = []);

    public function close();

    /**
     * @param $xpath
     * @return WebDriverElement[]
     */
    function findByXpath($xpath);

    /**
     * @param $xpath
     * @return WebDriverElement[]
     */
    function findById($id);

    /**
     * @return mixed
     */
    public function evaluateScript($script);

    /**
     * @param array $action
     * @param array|WebDriverElement[] $elements
     */
    public function performAction($action, array $elements);
}