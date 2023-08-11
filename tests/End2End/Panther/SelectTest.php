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

class SelectTest extends PantherTestCase
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

    public function testItShouldSelectTheOption()
    {
        $this->client->expects($this->once())->method('extractActions')->willReturn([
            "actions" => [
                ['action' => 'click', 'xpath' => '//select/option[text()="Two"]']
            ],
            "version" => "test1"
        ]);

        $this->client->expects($this->once())->method('extractAssertions')->willReturn([
            "assertions" => [
                ['assertion' => "carbonate_assert(document.querySelector('select').value == '2');"]
            ],
            "version" => "test1"
        ]);

        $this->sdk->load(__DIR__ . '/../../fixtures/select.html');
        $this->sdk->action('select Two from the dropdown');

        $this->assertTrue($this->sdk->assertion('the dropdown should be set to Two'));
    }

    public function testShouldFailWhenTheAssertionIsWrong()
    {
        $this->client->expects($this->once())->method('extractActions')->willReturn([
            "actions" => [
                ['action' => 'click', 'xpath' => '//select/option[text()="Two"]']
            ],
            "version" => "test1"
        ]);

        $this->client->expects($this->once())->method('extractAssertions')->willReturn([
            "assertions" => [
                ['assertion' => "carbonate_assert(document.querySelector('select').value == '3');"]
            ],
            "version" => "test1"
        ]);

        $this->sdk->load(__DIR__ . '/../../fixtures/select.html');
        $this->sdk->action('select Two from the dropdown');

        $this->assertFalse($this->sdk->assertion('the dropdown should be set to Three'));
    }

    public function testItShouldSelectTheOptionThroughTheSelect()
    {
        $this->client->expects($this->once())->method('extractActions')->willReturn([
            "actions" => [
                ['action' => 'click', 'xpath' => '//select'],
                ['action' => 'click', 'xpath' => '//select/option[text()="Two"]'],
            ],
            "version" => "test1"
        ]);

        $this->client->expects($this->once())->method('extractAssertions')->willReturn([
            "assertions" => [
                ['assertion' => "carbonate_assert(document.querySelector('select').value == '2');"]
            ],
            "version" => "test1"
        ]);

        $this->sdk->load(__DIR__ . '/../../fixtures/select.html');
        $this->sdk->action('select Two from the dropdown');

        $this->assertTrue($this->sdk->assertion('the dropdown should be set to Two'));
    }

    public function testItShouldWorkForSelect2()
    {
        $this->markTestSkipped('Not currently supported');
    }
}