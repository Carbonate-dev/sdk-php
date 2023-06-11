<?php

namespace Tests\Full;

use Carbonate\Api\Client;
use Carbonate\SDK;
use Carbonate\Tester\PantherBrowser;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\PantherTestCase;
use Tests\End2End\Panther\WaitTest;

class CarbonateTestFormTest extends PantherTestCase
{
    /**
     * @var PantherBrowser
     */
    protected $browser;

    /**
     * @var SDK
     */
    protected $sdk;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->browser = new PantherBrowser(PantherClient::createChromeClient());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->sdk = new SDK($this->browser, __DIR__ .'/'. pathinfo(__FILE__, PATHINFO_FILENAME));
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