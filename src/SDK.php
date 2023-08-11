<?php

namespace Carbonate;

use Carbonate\Api\Client;
use Carbonate\Exceptions\BrowserException;
use Carbonate\Exceptions\FailedExtractionException;
use Carbonate\Exceptions\InvalidXpathException;
use Carbonate\PhpUnit\Logger;
use Carbonate\Browser\BrowserInterface;
use PHPUnit\Framework\IncompleteTest;
use PHPUnit\Framework\SkippedTest;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class SDK
{
    /**
     * @var LoggerInterface|NullLogger
     */
    private $logger;

    /**
     * @var BrowserInterface
     */
    private $browser;

    /**
     * @var Client|null
     */
    private $client;

    private $testPrefix;
    private $testName;
    private $cacheDir;
    private $networkWhitelist = [];
    private $instructionCache = [];
    private $startedAt;
    private $actionIds = [];
    private $assertionIds = [];
    private $lookupIds = [];

    public function __construct(
        BrowserInterface $browser,
        $cacheDir = null,
        $apiUserId = null,
        $apiKey = null,
        LoggerInterface $logger = null,
        Client $client = null
    ) {
        $this->browser = $browser;
        $this->client = $client ?: new Client($apiUserId, $apiKey);
        $this->logger = $logger ?: new Logger();

        $this->cacheDir = $cacheDir ?: getenv('CARBONATE_CACHE_DIR');
    }

    public function getTestName()
    {
        if ($this->testPrefix) {
            return $this->testPrefix .': '. $this->testName;
        }

        return $this->testName;
    }

    public function waitForLoad($skipFunc)
    {
        $i = 0;
        $loggedDomUpdating = false;
        $loggedNetworkUpdating = false;

        while (
            ($domUpdating = $this->browser->evaluateScript('return window.carbonate_dom_updating')) ||
            $this->browser->evaluateScript('return window.carbonate_active_xhr')
        ) {
            if ($skipFunc()) {
                $this->logger->info("Found cached element, skipping DOM wait");
                break;
            }

            if ($domUpdating) {
                if (!$loggedDomUpdating) {
                    $this->logger->info("Waiting for DOM update to finish");
                    $loggedDomUpdating = true;
                }
            }
            else if (!$loggedNetworkUpdating) {
                $this->logger->info("Waiting for active Network to finish");
                $loggedNetworkUpdating = true;
            }

            if ($i > 240) {
                throw new BrowserException("Waited too long for DOM/XHR update to finish");
            }

            usleep(0.25 * 1000000);
            $i++;
        }
    }

    private function cachedActions($instruction)
    {
        if ($this->cacheDir && file_exists($this->getCachePath($instruction))) {
            $actions = json_decode(file_get_contents($this->getCachePath($instruction)), true);
            $this->logger->info("Using locally cached actions", ['actions' => $actions]);
            return $actions;
        }

        return null;
    }

    public function getCachePath($instruction)
    {
        return $this->cacheDir .'/'. Helpers::slugify($this->testName) .'/'. Helpers::slugify($instruction) .'.json';
    }

    private function extractActions($instruction)
    {
        $actions = $this->client->extractActions($this->getTestName(), $instruction, $this->browser->getHtml());

        if (count($actions['actions']) > 0) {
            $this->logger->info("Successfully extracted actions", ['actions' => $actions['actions']]);
            $this->cacheInstruction($actions, $instruction);
            return $actions;
        }

        throw new FailedExtractionException('Could not extract actions');
    }

    private function cacheInstruction($result, $instruction)
    {
        if ($this->cacheDir) {
            $this->instructionCache[$instruction] = $result;
        }
    }
    private function writeCache()
    {
        if (!$this->cacheDir) {
            throw new \LogicException("Cannot call writeCache without setting cacheDir");
        }

        if (!$this->testName) {
            throw new \LogicException("Test name not set, please call startTest first");
        }

        if (count($this->instructionCache) === 0) {
            return;
        }

        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir);
        }

        if (!file_exists($this->cacheDir .'/'. Helpers::slugify($this->testName))) {
            mkdir($this->cacheDir .'/'. Helpers::slugify($this->testName));
        }

        foreach ($this->instructionCache as $instruction => $result) {
            file_put_contents($this->getCachePath($instruction), json_encode($result));
        }

        $this->instructionCache = [];
    }

    private function cachedAssertions($instruction)
    {
        if ($this->cacheDir && file_exists($this->getCachePath($instruction))) {
            $assertions = json_decode(file_get_contents($this->getCachePath($instruction)), true);
            $this->logger->info("Using locally cached assertions", ['assertions' => $assertions]);
            return $assertions;
        }

        return null;
    }

    private function extractAssertions($instruction)
    {
        $assertions = $this->client->extractAssertions($this->getTestName(), $instruction, $this->browser->getHtml());

        if (count($assertions['assertions']) > 0) {
            $this->logger->info("Successfully extracted assertions", ['assertions' => $assertions['assertions']]);
            $this->cacheInstruction($assertions, $instruction);
            return $assertions;
        }

        throw new FailedExtractionException('Could not extract assertions');
    }

    public function action($instruction)
    {
        $this->logger->info("Querying action", ['test_name' => $this->getTestName(), 'instruction' => $instruction]);
        $this->browser->record('carbonate-instruction', ['instruction' => $instruction, 'type' => 'action']);

        $actions = $this->cachedActions($instruction);

        $this->waitForLoad(function () use ($actions) {
            return $actions !== null && Helpers::all($actions['actions'], function (array $action) {
                return count($this->browser->findByXpath($action['xpath'])) > 0;
            });
        });

        if ($actions === null) {
            $this->logger->notice("No actions found, extracting from page");
            $actions = $this->extractActions($instruction);
        }

        $this->actionIds[] = $actions['version'];

        if (count($this->browser->findByXpath($actions['actions'][0]['xpath'])) === 0) {
            throw new InvalidXpathException("Could not find element for xpath: " . $actions['actions'][0]['xpath']);
        }

        $this->performActions($actions['actions']);
    }

    private function performActions($actions)
    {
        $previous_actions = [];
        foreach ($actions as $action) {
            $this->logger->notice("Performing action", ['action' => $action]);
            $elements = $this->browser->findByXpath($action['xpath']);

            if (count($elements) == 0) {
                throw new InvalidXpathException("Could not find element for xpath " . $action['xpath']);
            }

            if (count($elements) > 1) {
                $this->logger->warning(
                    "More than one element found for xpath",
                    ['num' => count($elements), 'xpath' => $action['xpath']]
                );
                return $previous_actions;
            }

            $this->browser->record('carbonate-action', $action);
            $this->browser->performAction($action, $elements);
            $previous_actions[] = $action;
        }

        return $previous_actions;
    }

    public function assertion($instruction)
    {
        $this->logger->info("Querying assertion", ['test_name' => $this->getTestName(), 'instruction' => $instruction]);
        $this->browser->record('carbonate-instruction', ['instruction' => $instruction, 'type' => 'assertion']);

        $assertions = $this->cachedAssertions($instruction);

        $this->waitForLoad(function () use ($assertions) {
            return $assertions !== null && Helpers::all($assertions['assertions'], function (array $assertion) {
                try {
                    $this->performAssertion($assertion);
                    return true;
                } catch (BrowserException $e) {
                    return false;
                }
            });
        });

        if ($assertions === null) {
            $this->logger->notice("No assertions found, extracting from page");
            $assertions = $this->extractAssertions($instruction);
        }

        $this->assertionIds[] = $assertions['version'];
        return $this->performAssertions($assertions['assertions']);
    }

    private function performAssertions(array $assertions): bool
    {
        foreach ($assertions as $assertion) {
            $result = $this->performAssertion($assertion);
            $this->logger->info("Assertion result", ['assertion' => $result]);

            if (!$result) {
                return false;
            }
        }

        return true;
    }
    private function performAssertion($assertion)
    {
        $this->logger->notice("Performing assertion", ['assertion' => $assertion['assertion']]);
        $this->browser->record('carbonate-assertion', $assertion);

        return $this->browser->evaluateScript('window.carbonate_reset_assertion_result(); (function() { ' . $assertion['assertion'] . ' })(); return window.carbonate_assertion_result;');
    }

    private function cachedLookup($instruction)
    {
        if ($this->cacheDir && file_exists($this->getCachePath($instruction))) {
            $lookup = json_decode(file_get_contents($this->getCachePath($instruction)), true);
            $this->logger->info("Using locally cached lookup", ['lookup' => $lookup]);
            return $lookup;
        }

        return null;
    }

    private function extractLookup($instruction)
    {
        $lookup = $this->client->extractLookup($this->getTestName(), $instruction, $this->browser->getHtml());

        if ($lookup !== null) {
            $this->logger->info("Successfully extracted lookup", ['lookup' => $lookup]);
            $this->cacheInstruction($lookup, $instruction);

            return $lookup;
        }

        throw new FailedExtractionException('Could not extract lookup');
    }

    public function lookup($instruction)
    {
        $this->logger->info("Querying lookup", ['test_name' => $this->getTestName(), 'instruction' => $instruction]);
        $lookup = $this->cachedLookup($instruction);

        $this->waitForLoad(function () use ($lookup) {
            return $lookup !== null && count($this->browser->findByXpath($lookup['xpath'])) > 0;
        });

        if ($lookup === null) {
            $this->logger->notice("No lookup found, extracting from page");
            $lookup = $this->extractLookup($instruction);
        }

        $this->lookupIds[] = $lookup['version'];
        $elements = $this->browser->findByXpath($lookup['xpath']);

        if (count($elements) === 0) {
            throw new InvalidXpathException("Could not find element for xpath " . $lookup['xpath']);
        }

        return $elements[0];
    }

    public function startTest($testPrefix, $testName)
    {
        if (count($this->instructionCache) > 0) {
            throw new \LogicException("Instruction cache not empty, did you forget to call endTest()?");
        }

        if ($this->logger instanceof Logger) {
            $this->logger->clearLogs();
        }

        $this->testPrefix = $testPrefix;
        $this->testName = $testName;
        $this->startedAt = new \DateTimeImmutable();
        $this->actionIds = [];
        $this->assertionIds = [];
        $this->lookupIds = [];
    }

    public function endTest()
    {
        if ($this->cacheDir) {
            $this->writeCache();
        }
    }

    public function uploadRecording()
    {
        $recording = $this->browser->evaluateScript('return window.carbonate_rrweb_recording');

        $this->client->uploadRecording($this->getTestName(), $recording, $this->startedAt, $this->actionIds, $this->assertionIds, $this->lookupIds);
    }

    public function load($url)
    {
        $this->logger->info("Loading page", ['url' => $url, 'whitelist' => $this->networkWhitelist]);
        $this->browser->load($url, $this->networkWhitelist);
        $this->browser->record('carbonate-load', ['url' => $url]);
    }

    public function close()
    {
        $this->logger->info("Closing browser");
        $this->browser->close();
    }

    public function whitelistNetwork($url)
    {
        $this->networkWhitelist[] = $url;
    }

    public function handleFailedTest(\Throwable $t)
    {
        $this->instructionCache = [];
        $this->browser->record('carbonate-error', [
            'message' => $t->getMessage(),
            'trace' => $t->getTraceAsString(),
        ]);

        $this->uploadRecording();

        if ($this->logger instanceof Logger) {
            $logs = $this->logger->getLogs();

            if ($logs && !($t instanceof IncompleteTest) && !($t instanceof SkippedTest)) {
                throw new \Exception($logs .' '. $t->getMessage(), $t->getCode(), $t);
            }
        }
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function getBrowser()
    {
        return $this->browser;
    }
}
