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

class InputTest extends PantherTestCase
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

    public function inputDataProvider()
    {
        return [
            ['color', '//input[@id="color"]', '#ff0000'],
            ['email', '//input[@id="email"]', 'test@example.org'],
            ['number', '//input[@id="number"]', '12'],
            ['password', '//input[@id="password"]', 'teststr'],
            ['range', '//input[@id="range"]', '50'],
            ['search', '//input[@id="search"]', 'teststr'],
            ['tel', '//input[@id="tel"]', '01234567890'],
            ['text', '//input[@id="text"]', 'teststr'],
            ['url', '//input[@id="url"]', 'http://example.org'],
            ['textarea', '//textarea[@id="textarea"]', "This\nis\na\ntest"],
        ];
    }

    public function dateDataProvider()
    {
        return [
            ['date', '//input[@id="date"]', '2022-01-01'],
            ['datetime-local', '//input[@id="datetime-local"]', '2022-01-01T00:00'],
            ['month', '//input[@id="month"]', '2022-01'],
            ['time', '//input[@id="time"]', '00:00:00'],
            ['week', '//input[@id="week"]', '2022-W01'],
        ];
    }

    public function checkDataProvider()
    {
        return [
            ['radio', '//input[@id="radio"]', '1'],
            ['checkbox', '//input[@id="checkbox"]', '1'],
        ];
    }

    /**
     * @dataProvider inputDataProvider
     * @dataProvider dateDataProvider
     */
    public function testItShouldFillInTheInput($name, $xpath, $value)
    {
        $this->client->method('extractActions')->willReturn([
            "actions" => [
                ['action' => 'type', 'xpath' => $xpath, 'text' => $value]
            ],
            "version" => "test1"
        ]);

        $encodedValue = json_encode($value);

        $this->client->method('extractAssertions')->willReturn([
            "assertions" => [
                ['assertion' => "carbonate_assert(document.querySelector('#{$name}').value == ${encodedValue});"]
            ],
            "version" => "test1"
        ]);

        $this->sdk->load( __DIR__ . '/../../fixtures/input.html');
        $this->sdk->action('type "'. $value .'" into the '. $name .' input');

        $this->assertTrue($this->sdk->assertion('the '. $name .' input should have the contents "'. $value .'"'));

        $this->assertTrue($this->sdk->getBrowser()->evaluateScript('return window.hasChanged["'. $name .'"]'));
    }

    /**
     * @dataProvider checkDataProvider
     */
    public function testItShouldClickTheElement($name, $xpath, $value)
    {
        $this->client->method('extractActions')->willReturn([
            "actions" => [
                ['action' => 'click', 'xpath' => $xpath]
            ],
            "version" => "test1"
        ]);

        $encodedValue = json_encode($value);

        $this->client->method('extractAssertions')->willReturn([
            "assertions" => [
                ['assertion' => "carbonate_assert(document.querySelector('#{$name}').value == ${encodedValue});"]
            ],
            "version" => "test1"
        ]);

        $this->sdk->load( __DIR__ . '/../../fixtures/input.html');
        $this->sdk->action('click the '. $name .' element');

        $this->assertTrue($this->sdk->assertion('the '. $name .' element should have the value "'. $value .'"'));

        $this->assertTrue($this->sdk->getBrowser()->evaluateScript('return window.hasChanged["'. $name .'"]'));
    }

    public function testItShouldFillInTheInputWhenGivenALabel()
    {
        $this->client->method('extractActions')->willReturn([
            "actions" => [
                ['action' => 'type', 'xpath' => '//label[@for="input"]', 'text' => 'teststr']
            ],
            "version" => "test1"
        ]);

        $this->client->method('extractAssertions')->willReturn([
            "assertions" => [
                ['assertion' => "carbonate_assert(document.querySelector('input').value == 'teststr');"]
            ],
            "version" => "test1"
        ]);

        $this->sdk->load(__DIR__ . '/../../fixtures/label.html');
        $this->sdk->action('type "teststr" into the input');

        $this->assertTrue($this->sdk->assertion('the input should have the contents "teststr"'));
    }
}