<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StaticPagesControllerTest extends WebTestCase
{
    /**
     * @dataProvider staticPageUrlProvider
     */
    public function testStaticPage(string $url): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, $url);
        $this->assertResponseIsSuccessful();
    }

    public static function staticPageUrlProvider(): array
    {
        return [
            'Datenschutz page' => ['/Datenschutz'],
            'Impressum page' => ['/Impressum'],
        ];
    }

    public function testDatenschutzPageContent(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/Datenschutz');
        $this->assertSelectorTextContains('h1', 'Datenschutzerklärung');
        $this->assertSelectorExists('div.card-body');
    }

    // public function testImpressumPage(): void
    // {
    //     $client = static::createClient();
    //     $crawler = $client->request('GET', '/Impressum');

    //     $this->assertResponseIsSuccessful();
    // }
}