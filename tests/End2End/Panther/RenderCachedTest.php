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

class RenderCachedTest extends PantherTestCase
{
    /**
     * @var PantherBrowser
     */
    protected static $browser;

    /**
     * @var SDK
     */
    protected $sdk;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$browser = new PantherBrowser(self::createPantherClient(['external_base_uri' => 'file:///']));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Client::class);
        $this->sdk = new SDK(self::$browser, __DIR__ .'/'. pathinfo(__FILE__, PATHINFO_FILENAME), null, null, null, $this->client);
        $this->sdk->startTest(__CLASS__, $this->getName());

        $this->client->method('extractActions')->willThrowException(new \LogicException('This should not be called'));
        $this->client->method('extractAssertions')->willThrowException(new \LogicException('This should not be called'));
        $this->client->method('extractLookup')->willThrowException(new \LogicException('This should not be called'));
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
        $this->client->expects($this->never())->method('extractActions');

        $this->sdk->load(__DIR__ . '/../../fixtures/render.html');

        $this->sdk->action('type "teststr" into the input');

        $this->assertTrue(
            $this->sdk->getBrowser()->evaluateScript("return document.querySelector('input').value == 'teststr'")
        );
        $this->assertStringContainsString('Waiting for DOM update to finish', $this->sdk->getLogger()->getLogs());
    }

    public function testItShouldWaitForRendersToFinishBeforePerformingAssertions()
    {
        $this->client->expects($this->never())->method('extractAssertions');

        $this->sdk->load(__DIR__ . '/../../fixtures/render.html');

        $this->assertTrue(
            $this->sdk->assertion('there should be a label with the text "test"')
        );
        $this->assertStringContainsString('Waiting for DOM update to finish', $this->sdk->getLogger()->getLogs());
    }
}