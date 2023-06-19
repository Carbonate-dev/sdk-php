<?php

namespace Tests\End2End\Panther;

use Carbonate\Api\Client;
use Carbonate\SDK;
use Carbonate\PhpUnit\Logger;
use Carbonate\Browser\PantherBrowser;
use DMore\ChromeDriver\ChromeDriver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\PantherTestCase;
use Throwable;

class ClickTest extends PantherTestCase
{
    /**
     * @var PantherBrowser
     */
    private static $browser;

    /**
     * @var SDK
     */
    private $sdk;

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

    public function elementDataProvider()
    {
        return [
            ['button', '//input[@id="button"]'],
            ['submit', '//input[@id="submit"]'],
            ['reset', '//input[@id="reset"]'],
            ['link', '//a[@id="link"]'],
        ];
    }

    /**
     * @dataProvider elementDataProvider
     */
    public function testItShouldClickTheElement($name, $xpath)
    {
        // TODO: Add expects
        $this->client->method('extractActions')->willReturn([
            ['action' => 'click', 'xpath' => $xpath]
        ]);

        $this->client->method('extractAssertions')->willReturn([
            ['assertion' => "carbonate_assert(window['{$name}_clicked'] === true);"]
        ]);

        $this->sdk->load( __DIR__ . '/../../fixtures/click.html');
        $this->sdk->action('click on the '. $name);

        $this->assertTrue($this->sdk->assertion('the '. $name .' should have been clicked'));
    }
}