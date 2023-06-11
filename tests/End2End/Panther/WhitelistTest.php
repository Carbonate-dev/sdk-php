<?php

namespace Tests\End2End\Panther;

use Carbonate\Api\Client;
use Carbonate\SDK;
use Carbonate\Tester\PantherBrowser;
use Symfony\Component\Panther\PantherTestCase;
use Throwable;
use Symfony\Component\Panther\Client as PantherClient;

class WhitelistTest extends PantherTestCase
{
    /**
     * @var PantherBrowser
     */
    protected $browser;

    /**
     * @var SDK
     */
    protected $sdk;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->browser = new PantherBrowser(PantherClient::createChromeClient());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createStub(Client::class);
        $this->sdk = new SDK($this->browser, null, null, null, null, $this->client);
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
        $this->client->method('extractActions')->willReturn([
            ['action' => 'type', 'xpath' => '//label[@for="input"]', 'text' => 'teststr']
        ]);

        $this->client->method('extractAssertions')->willReturn([
            ['assertion' => "document.querySelector('input').value == 'teststr'"]
        ]);

        $this->sdk->whitelistNetwork('https://api.staging.carbonate.dev/internal/test_wait*');

        $this->sdk->load('file:///'. __DIR__ . '/../../fixtures/whitelist_xhr.html');

        $this->sdk->action('type "teststr" into the input');

        $this->assertTrue($this->sdk->assertion('the input should have the contents "teststr"'));
    }

    public function testItShouldNotWaitForWhitelistedFetch()
    {
        $this->client->method('extractActions')->willReturn([
            ['action' => 'type', 'xpath' => '//label[@for="input"]', 'text' => 'teststr']
        ]);

        $this->client->method('extractAssertions')->willReturn([
            ['assertion' => "document.querySelector('input').value == 'teststr'"]
        ]);

        $this->sdk->whitelistNetwork('https://api.staging.carbonate.dev/internal/test_wait*');

        $this->sdk->load('file:///'. __DIR__ . '/../../fixtures/whitelist_fetch.html');

        $this->sdk->action('type "teststr" into the input');

        $this->assertTrue($this->sdk->assertion('the input should have the contents "teststr"'));
    }
}