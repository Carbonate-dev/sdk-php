<?php

namespace Carbonate\Tester;

use Behat\Mink\Exception\UnsupportedDriverActionException;

interface BrowserInterface
{
    public function getHtml();

    public function load($url, $whitelist = []);

    public function close();

    /**
     * @return string
     * @throws UnsupportedDriverActionException
     * @throws \Behat\Mink\Exception\DriverException
     */
    public function getScreenshot();

    /**
     * @param $xpath
     * @return \Behat\Mink\Element\NodeElement[]
     */
    function findByXpath($xpath);

    /**
     * @param $xpath
     * @return \Behat\Mink\Element\NodeElement[]
     */
    function findById($id);

    /**
     * @return mixed
     */
    public function evaluateScript($script);

    /**
     * @param $action
     * @param array $elements
     * @return array|\Behat\Mink\Element\NodeElement[]
     */
    public function performAction($action, array $elements);
}