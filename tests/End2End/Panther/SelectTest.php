<?php

namespace Tests\End2End\Panther;

use Carbonate\Api\Client;
use Carbonate\SDK;
use Carbonate\PhpUnit\Logger;
use Carbonate\Tester\PantherBrowser;
use DMore\ChromeDriver\ChromeDriver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\PantherTestCase;
use Throwable;

class SelectTest extends PantherTestCase
{
    /**
     * @var PantherBrowser
     */
    private $browser;

    /**
     * @var SDK
     */
    private $sdk;

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

    public function testSelectSuccessful()
    {
        $this->client->method('extractActions')->willReturn([
            ['action' => 'click', 'xpath' => '//select/option[text()="Two"]']
        ]);

        $this->client->method('extractAssertions')->willReturn([
            ['assertion' => "document.querySelector('select').value == '2'"]
        ]);

        $this->sdk->load('file:///'. __DIR__ . '/../../fixtures/select.html');
        $this->sdk->action('select Two from the dropdown');

        $this->assertTrue($this->sdk->assertion('the dropdown should be set to Two'));
    }

    public function testSelectNotSuccessful()
    {
        $this->client->method('extractActions')->willReturn([
            ['action' => 'click', 'xpath' => '//select/option[text()="Two"]']
        ]);

        $this->client->method('extractAssertions')->willReturn([
            ['assertion' => "document.querySelector('select').value == '3'"]
        ]);

        $this->sdk->load('file:///'. __DIR__ . '/../../fixtures/select.html');
        $this->sdk->action('select Two from the dropdown');

        $this->assertFalse($this->sdk->assertion('the dropdown should be set to Three'));
    }

    public function testLabelFor()
    {
        $this->client->method('extractActions')->willReturn([
            ['action' => 'type', 'xpath' => '//label[@for="input"]', 'text' => 'teststr']
        ]);

        $this->client->method('extractAssertions')->willReturn([
            ['assertion' => "document.querySelector('input').value == 'teststr'"]
        ]);

        $this->sdk->load('file:///'. __DIR__ . '/../../fixtures/label.html');
        $this->sdk->action('type "teststr" into the input');

        $this->assertTrue($this->sdk->assertion('the input should have the contents "teststr"'));
    }

}