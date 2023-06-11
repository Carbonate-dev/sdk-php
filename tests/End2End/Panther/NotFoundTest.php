<?php

namespace Tests\End2End\Panther;

use Carbonate\Api\Client;
use Carbonate\Exceptions\InvalidXpathException;
use Carbonate\SDK;
use Carbonate\PhpUnit\Logger;
use Carbonate\Tester\PantherBrowser;
use DMore\ChromeDriver\ChromeDriver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\PantherTestCase;
use Throwable;

class NotFoundTest extends PantherTestCase
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

    public function testItShouldErrorIfXpathIsNotFoundForAnAction()
    {
        $this->client->method('extractActions')->willReturn([
            ['action' => 'click', 'xpath' => '//select//option[text()=\'Birthday\']']
        ]);

        $this->sdk->load('file:///'. __DIR__ . '/../../fixtures/select.html');

        $this->expectException(InvalidXpathException::class);
        $this->sdk->action('chose Birthday as the event type');
    }

    public function testItShouldErrorIfXpathIsNotFoundForALookup()
    {
        $this->client->method('extractLookup')->willReturn(
            ['xpath' => "//select//option[text()='Birthday']"]
        );

        $this->sdk->load('file:///'. __DIR__ . '/../../fixtures/select.html');

        $this->expectException(InvalidXpathException::class);
        $this->sdk->lookup('the event type dropdown');
    }

}