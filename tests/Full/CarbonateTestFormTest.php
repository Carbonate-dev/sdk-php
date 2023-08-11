<?php

namespace Tests\Full;

use Carbonate\Api\Client;
use Carbonate\SDK;
use Carbonate\Browser\PantherBrowser;
use Facebook\WebDriver\WebDriverSelect;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\PantherTestCase;
use Tests\End2End\Panther\WaitTest;
use Throwable;

class CarbonateTestFormTest extends PantherTestCase
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

        self::$browser = new PantherBrowser(self::createPantherClient(['external_base_uri' => 'https://carbonate.dev/']));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->sdk = new SDK(self::$browser, null);
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


    public function testSelectBirthdayFromTheDropdown()
    {
        $this->sdk->load(
            'https://carbonate.dev/demo-form.html'
        );

        $this->sdk->action('select Birthday from the event type dropdown');

        $this->assertTrue(
            $this->sdk->assertion('the event type dropdown should be set to Birthday')
        );
    }

    public function testSelectBirthdayFromTheDropdownAdvanced()
    {
        $this->sdk->load(
            'https://carbonate.dev/demo-form.html'
        );

        $select = new WebDriverSelect($this->sdk->lookup('the event type dropdown'));

        $select->selectByVisibleText('Birthday');

        $this->assertSame(
            'Birthday',
            $select->getFirstSelectedOption()->getAttribute('value')
        );
    }
}