<?php

namespace Tests\End2End\Panther;

use Carbonate\Api\Client;
use Carbonate\Exceptions\InvalidXpathException;
use Carbonate\SDK;
use Carbonate\PhpUnit\Logger;
use Carbonate\Browser\PantherBrowser;
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
    private static $browser;

    /**
     * @var SDK
     */
    private $sdk;

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

    public function testItShouldErrorIfXpathIsNotFoundForAnAction()
    {
        $this->client->expects($this->once())->method('extractActions')->willReturn([
            "actions" => [
                ['action' => 'click', 'xpath' => '//select//option[text()=\'Birthday\']']
            ],
            "version" => "test1"
        ]);

        $this->sdk->load(__DIR__ . '/../../fixtures/select.html');

        $this->expectException(InvalidXpathException::class);
        $this->sdk->action('chose Birthday as the event type');
    }

    public function testItShouldErrorIfXpathIsNotFoundForALookup()
    {
        $this->client->expects($this->once())->method('extractLookup')->willReturn(
            ['xpath' => "//select//option[text()='Birthday']", 'version' => 'test1']
        );

        $this->sdk->load(__DIR__ . '/../../fixtures/select.html');

        $this->expectException(InvalidXpathException::class);
        $this->sdk->lookup('the event type dropdown');
    }

}