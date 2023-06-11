<?php

namespace Tests\End2End\Panther;

use Carbonate\Api\Client;
use Carbonate\Exceptions\InvalidXpathException;
use Carbonate\SDK;
use Carbonate\PhpUnit\Logger;
use Carbonate\Tester\PantherBrowser;
use DMore\ChromeDriver\ChromeDriver;
use PHPUnit\Framework\TestCase;
use Throwable;

class WaitCachedTest extends WaitTest
{
    protected function setUp(): void
    {
        $this->client = $this->createStub(Client::class);
        $this->sdk = new SDK($this->browser, __DIR__ .'/'. pathinfo(__FILE__, PATHINFO_FILENAME), null, null, null, $this->client);
        $this->sdk->startTest(__CLASS__, $this->getName());

        $this->client->method('extractActions')->willThrowException(new \LogicException('This should not be called'));
        $this->client->method('extractAssertions')->willThrowException(new \LogicException('This should not be called'));
        $this->client->method('extractLookup')->willThrowException(new \LogicException('This should not be called'));
    }
}