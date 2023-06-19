<?php

namespace Tests\End2End\Panther;

use Carbonate\Api\Client;
use Carbonate\SDK;
use Carbonate\Browser\PantherBrowser;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\PantherTestCase;
use Throwable;

class RenderTest extends PantherTestCase
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
    
    public function testItShouldWaitForRendersToFinishBeforePerformingActions()
    {
        $this->client->expects($this->once())->method('extractActions')->willReturn([
            ['action' => 'type', 'xpath' => '//label[@for="input"]', 'text' => 'teststr']
        ]);

        $this->sdk->load(__DIR__ . '/../../fixtures/render.html');

        $this->sdk->action('type "teststr" into the input');

        $this->assertTrue(
            $this->sdk->getBrowser()->evaluateScript("return document.querySelector('input').value == 'teststr'")
        );

        $this->assertStringContainsString('Waiting for DOM update to finish', $this->sdk->getLogger()->getLogs());
    }

    public function testItShouldWaitForRendersToFinishBeforePerformingAssertions()
    {
        $this->client->expects($this->once())->method('extractAssertions')->willReturn([
            ['assertion' => "carbonate_assert(document.querySelector('label').innerText == 'Test');"]
        ]);

        $this->sdk->load(__DIR__ . '/../../fixtures/render.html');

        $this->assertTrue(
            $this->sdk->assertion('there should be a label with the text "test"')
        );

        $this->assertStringContainsString('Waiting for DOM update to finish', $this->sdk->getLogger()->getLogs());
    }

}