<?php

namespace Carbonate\Tester;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Carbonate\Action;
use Carbonate\Exceptions\BrowserException;

class MinkBrowser implements BrowserInterface
{
    private $mink;
    private $browser;
    private $injectJs;

    public function __construct(DriverInterface $driver)
    {
        $this->browser = new Session($driver);
        $this->mink = new Mink([
            'browser' => $this->browser
        ]);
        $this->browser->start();

        $injectJsPath = __DIR__ . "/../../resources/carbonate.js";
        $this->injectJs = file_get_contents($injectJsPath);
    }

    public function getHtml()
    {
        return $this->browser->evaluateScript('return document.documentElement.innerHTML');
    }

    public function load($url)
    {
        $this->browser->start();
        $this->browser->executeScript($this->injectJs);
        $this->browser->visit($url);
    }

    public function close()
    {
        $this->browser->reset();
    }

    /**
     * @return string
     * @throws UnsupportedDriverActionException
     * @throws \Behat\Mink\Exception\DriverException
     */
    public function getScreenshot()
    {
        return $this->browser->getDriver()->getScreenshot();
    }

    /**
     * @param $xpath
     * @return \Behat\Mink\Element\NodeElement[]
     */
    function findByXpath($xpath)
    {
        return $this->browser->getPage()->findAll('xpath', $xpath);
    }

    /**
     * @param $xpath
     * @return \Behat\Mink\Element\NodeElement[]
     */
    function findById($id)
    {
        return $this->browser->getPage()->findAll('css', '#'. $id);
    }

    /**
     * @return mixed
     */
    public function evaluateScript($script)
    {
        try {
            return $this->browser->evaluateScript($script);
        }
        catch (DriverException $e) {
            throw new BrowserException("Could not evaluate script: ". $script, 0, $e);
        }
    }

    /**
     * @param $action
     * @param array $elements
     * @return array|\Behat\Mink\Element\NodeElement[]
     */
    public function performAction($action, array $elements)
    {
        if ($action['action'] == Action::CLICK) {
            $elements[0]->click();
        } elseif ($action['action'] == Action::TYPE) {
            if ($elements[0]->getTagName() == 'label') {
                $elements = $this->findById($elements[0]->getAttribute('for'));
            }

            $elements[0]->setValue($action['text']);
        } elseif ($action['action'] == Action::KEY) {
            $elements[0]->keyPress($action['key']);
        }
    }
}
