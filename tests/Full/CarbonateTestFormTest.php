<?php

namespace Tests\Full;

use Carbonate\Api\Client;
use Carbonate\SDK;
use Carbonate\Browser\PantherBrowser;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\PantherTestCase;
use Tests\End2End\Panther\WaitTest;

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

        self::$browser = new PantherBrowser(self::createPantherClient(['external_base_uri' => '']));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->sdk = new SDK(self::$browser, null);
        $this->sdk->startTest(__CLASS__, $this->getName());
    }

    public function testBirthdayEventType()
    {
        $this->sdk->load(
            # 'https://carbonate.dev/demo-form',
            'https://testbot-website.vercel.app/demo-form'
        );

        $this->sdk->action('chose Birthday as the event type');

        $this->assertTrue(
            $this->sdk->assertion('the event type should be Birthday')
        );

        $this->assertEquals(
            'Birthday',
            $this->sdk->lookup('the event type dropdown')->getAttribute('value')
        );
    }
}