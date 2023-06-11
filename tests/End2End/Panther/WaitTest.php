<?php

namespace Tests\End2End\Panther;

use Carbonate\Api\Client;
use Carbonate\Exceptions\InvalidXpathException;
use Carbonate\SDK;
use Carbonate\PhpUnit\Logger;
use Carbonate\Tester\PantherBrowser;
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

    public function testItShouldWaitForXhr()
    {
        $this->client->method('extractActions')->willReturn([
            ['action' => 'type', 'xpath' => '//label[@for="input"]', 'text' => 'teststr']
        ]);

        $this->client->method('extractAssertions')->willReturn([
            ['assertion' => "document.querySelector('input').value == 'teststr'"]
        ]);

        $this->sdk->load('file:///'. __DIR__ . '/../../fixtures/wait_xhr.html');

        $this->sdk->action('type "teststr" into the input');

        $this->assertTrue($this->sdk->assertion('the input should have the contents "teststr"'));
    }

    public function testItShouldWaitForXhrForAssertions()
    {
        $this->client->method('extractAssertions')->willReturn([
            ['assertion' => "document.querySelector('input').value == ''"]
        ]);

        $this->sdk->load('file:///'. __DIR__ . '/../../fixtures/wait_xhr.html');

        $this->assertTrue($this->sdk->assertion('the input should be empty'));
    }

    public function testItShouldWaitForFetch()
    {
        $this->client->method('extractActions')->willReturn([
            ['action' => 'type', 'xpath' => '//label[@for="input"]', 'text' => 'teststr']
        ]);

        $this->client->method('extractAssertions')->willReturn([
            ['assertion' => "document.querySelector('input').value == 'teststr'"]
        ]);

        $this->sdk->load('file:///'. __DIR__ . '/../../fixtures/wait_fetch.html');

        $this->sdk->action('type "teststr" into the input');

        $this->assertTrue($this->sdk->assertion('the input should have the contents "teststr"'));
    }

    public function testItShouldWaitForFetchForAssertions()
    {
        $this->client->method('extractAssertions')->willReturn([
            ['assertion' => "document.querySelector('input').value == ''"]
        ]);

        $this->sdk->load('file:///'. __DIR__ . '/../../fixtures/wait_fetch.html');

        $this->assertTrue($this->sdk->assertion('the input should be empty'));
    }

}