<?php

namespace Tests\End2End\Panther;

use Carbonate\Api\Client;
use Carbonate\PhpUnit\Logger;
use Carbonate\SDK;
use Carbonate\Browser\PantherBrowser;
use Symfony\Component\Panther\PantherTestCase;
use Throwable;
use Symfony\Component\Panther\Client as PantherClient;

class WhitelistTest extends PantherTestCase
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

    public function testItShouldNotWaitForWhitelistedXhr()
    {
        $this->client->expects($this->once())->method('extractActions')->willReturn([
            ['action' => 'type', 'xpath' => '//label[@for="input"]', 'text' => 'teststr']
        ]);

        $this->sdk->whitelistNetwork('https://api.staging.carbonate.dev/internal/test_wait*');

        $this->sdk->load(__DIR__ . '/../../fixtures/whitelist_xhr.html');

        $this->sdk->action('type "teststr" into the input');

        $this->assertTrue($this->sdk->getBrowser()->evaluateScript("return document.querySelector('input').value == 'teststr'"));
        $this->assertStringNotContainsString('Waiting for active Network to finish', $this->sdk->getLogger()->getLogs());
    }

    public function testItShouldNotWaitForWhitelistedFetch()
    {
        $this->client->expects($this->once())->method('extractActions')->willReturn([
            ['action' => 'type', 'xpath' => '//label[@for="input"]', 'text' => 'teststr']
        ]);

        $this->sdk->whitelistNetwork('https://api.staging.carbonate.dev/internal/test_wait*');

        $this->sdk->load(__DIR__ . '/../../fixtures/whitelist_fetch.html');

        $this->sdk->action('type "teststr" into the input');

        $this->assertTrue($this->sdk->getBrowser()->evaluateScript("return document.querySelector('input').value == 'teststr'"));
        $this->assertStringNotContainsString('Waiting for active Network to finish', $this->sdk->getLogger()->getLogs());
    }
}