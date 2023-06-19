<?php

namespace Carbonate\Browser;

use Behat\Mink\Exception\UnsupportedDriverActionException;
use Carbonate\Action;
use Carbonate\Exceptions\BrowserException;
use Facebook\WebDriver\Exception\JavascriptErrorException;
use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\Client as PantherClient;

class PantherBrowser implements BrowserInterface
{
    private $browser;
    private $injectJs;

    public function __construct(PantherClient $driver)
    {
        $this->browser = $driver;

        $injectJsPath = __DIR__ . "/../../resources/carbonate.js";
        $this->injectJs = file_get_contents($injectJsPath);
    }

    public function getHtml()
    {
        return $this->evaluateScript('return document.documentElement.innerHTML');
    }

    public function load($url, $whitelist = [])
    {
        $this->browser->start();

        if ($this->evaluateScript('return typeof window.carbonate_dom_updating === "undefined"')) {
            $this->browser->getWebDriver()->executeCustomCommand('/session/:sessionId/chromium/send_command', 'POST', [
                'cmd' => 'Page.addScriptToEvaluateOnNewDocument',
                'params' => (object)[
                    "source" => $this->injectJs,
                ],
            ]);
        }

        $this->browser->get($url);
        $this->evaluateScript('window.carbonate_set_xhr_whitelist(' . json_encode($whitelist) . ')');
    }

    public function close()
    {
        $this->browser->quit();
    }

//    /**
//     * @return string
//     * @throws UnsupportedDriverActionException
//     * @throws \Behat\Mink\Exception\DriverException
//     */
//    public function getScreenshot()
//    {
//        return $this->browser->takeScreenshot();
//    }

    /**
     * @param $xpath
     * @return \Facebook\WebDriver\WebDriverElement[]
     */
    function findByXpath($xpath)
    {
        return $this->browser->findElements(WebDriverBy::xpath($xpath));
    }

    /**
     * @param $xpath
     * @return \Facebook\WebDriver\WebDriverElement[]
     */
    function findById($id)
    {
        return $this->browser->findElements(WebDriverBy::id($id));
    }

    /**
     * @return mixed
     */
    public function evaluateScript($script)
    {
        try {
            return $this->browser->executeScript($script);
        }
        catch (JavascriptErrorException $e) {
            throw new BrowserException("Could not evaluate script: ". $script, 0, $e);
        }
    }

    /**
     * @param $action
     * @param array|\Facebook\WebDriver\WebDriverElement[] $elements
     */
    public function performAction($action, array $elements)
    {
        if ($action['action'] == Action::CLICK) {
            $elements[0]->click();
        } elseif ($action['action'] == Action::TYPE) {
            $nonTypeable = ['date', 'datetime-local', 'month', 'time', 'week', 'color', 'range'];
            if ($elements[0]->getTagName() == 'input' && in_array($elements[0]->getAttribute('type'), $nonTypeable)) {
                // Not ideal but filling via the UI is not currently possible
                $this->browser->executeScript(
                    "arguments[0].value = arguments[1]; ".
                    // Trigger onchange manually
                    "arguments[0].dispatchEvent(new Event('change'), {bubbles: true});",
                    [$elements[0], $action['text']]
                );
                return;
            }
            if ($elements[0]->getTagName() == 'label') {
                $elements = $this->findById($elements[0]->getAttribute('for'));
            }

            $elements[0]->sendKeys($action['text']);

            $this->evaluateScript('!!document.activeElement ? document.activeElement.blur() : 0');
        } elseif ($action['action'] == Action::KEY) {
            $elements[0]->sendKeys($action['key']);
        }
    }

    public function getLogs()
    {
        return $this->browser->getWebDriver()->manage()->getLog('browser');
    }
}
