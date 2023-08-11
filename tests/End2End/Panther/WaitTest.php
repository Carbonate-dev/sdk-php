<?php

namespace Tests\End2End\Panther;

use Carbonate\Api\Client;
use Carbonate\Exceptions\InvalidXpathException;
use Carbonate\SDK;
use Carbonate\PhpUnit\Logger;
use Carbonate\Browser\PantherBrowser;
use DMore\ChromeDriver\ChromeDriver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Panther\PantherTestCase;
use Throwable;
use Symfony\Component\Panther\Client as PantherClient;

class WaitTest extends PantherTestCase
{
    /**
     * @var PantherBrowser
     */
    protected static $browser;

    /**
     * @var SDK
     */
    protected $sdk;

    /**
     * @var Client|\PHPUnit\Framework\MockObject\MockObject
     */
    private $client;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$browser = new PantherBrowser(self::createPantherClient(['external_base_uri' => 'file:///']));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Client::class);
        $this->sdk = new SDK(self::$browser, null, null, null, null, $this->client);
        $this->sdk->startTest(__CLASS__, $this->getName());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->sdk->endTest();
    }

    protected function onNotSuccessfulTest(Throwable $t): void
    {
        if ($this->sdk) {
            $this->sdk->handleFailedTest($t);
        }

        parent::onNotSuccessfulTest($t);
    }

    public function testItShouldWaitForXhrBeforePerformingActions()
    {
        $this->client->expects($this->once())->method('extractActions')->willReturn([
            "actions" => [
                ['action' => 'type', 'xpath' => '//label[@for="input"]', 'text' => 'teststr']
            ],
            "version" => "test1"
        ]);

        $this->sdk->load(__DIR__ . '/../../fixtures/wait_xhr.html');

        $this->sdk->action('type "teststr" into the input');

        $this->assertTrue(
            $this->sdk->getBrowser()->evaluateScript("return document.querySelector('input').value == 'teststr'")
        );

        $this->assertStringContainsString('Waiting for active Network to finish', $this->sdk->getLogger()->getLogs());
    }

    public function testItShouldWaitForXhrBeforePerformingAssertions()
    {
        $this->client->expects($this->once())->method('extractAssertions')->willReturn([
            "assertions" => [
                ['assertion' => "carbonate_assert(document.querySelector('input').value == '');"]
            ],
            "version" => "test1"
        ]);

        $this->sdk->load(__DIR__ . '/../../fixtures/wait_xhr.html');

        $this->assertTrue(
            $this->sdk->assertion('the input should be empty')
        );

        $this->assertStringContainsString('Waiting for active Network to finish', $this->sdk->getLogger()->getLogs());
    }

    public function testItShouldWaitForFetchBeforePerformingActions()
    {
        $this->client->expects($this->once())->method('extractActions')->willReturn([
            "actions" => [
                ['action' => 'type', 'xpath' => '//label[@for="input"]', 'text' => 'teststr']
            ],
            "version" => "test1"
        ]);

        $this->sdk->load(__DIR__ . '/../../fixtures/wait_fetch.html');

        $this->sdk->action('type "teststr" into the input');

        $this->assertTrue(
            $this->sdk->getBrowser()->evaluateScript("return document.querySelector('input').value == 'teststr'")
        );

        $this->assertStringContainsString('Waiting for active Network to finish', $this->sdk->getLogger()->getLogs());
    }

    public function testItShouldWaitForFetchForAssertions()
    {
        $this->client->expects($this->once())->method('extractAssertions')->willReturn([
            "assertions" => [
                ['assertion' => "carbonate_assert(document.querySelector('input').value == '');"]
            ],
            "version" => "test1"
        ]);

        $this->sdk->load(__DIR__ . '/../../fixtures/wait_fetch.html');

        $this->assertTrue(
            $this->sdk->assertion('the input should be empty')
        )
        ;
        $this->assertStringContainsString('Waiting for active Network to finish', $this->sdk->getLogger()->getLogs());
    }

}