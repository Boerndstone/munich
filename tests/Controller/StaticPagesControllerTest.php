<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use PHPUnit\Framework\Attributes\TestWith;

class StaticPagesControllerTest extends WebTestCase
{
    #[TestWith(['/Datenschutz'])]
    #[TestWith(['/Impressum'])]
    public function testStaticPage(string $url): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, $url);
        $this->assertResponseIsSuccessful();
    }

    #[TestWith(['/Datenschutz'])]
    public function testDatenschutzPageContent(string $url): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, $url);
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



// <?php

// namespace Neubaukompass\Tests\Frontend\Controller;

// use Symfony\Component\HttpFoundation\Request;
// use Neubaukompass\Tests\Frontend\WebTestCase;
// use PHPUnit\Framework\Attributes\TestWith;
// use Symfony\Component\HttpFoundation\Response;

// class StaticPagesControllerTest extends WebTestCase
// {

//     #[TestWith(['/angebot-fuer-bautraeger-und-immobilien-vermarkter/'])]
//     #[TestWith(['/premium-magazin/'])]
//     #[TestWith(['/unternehmen/wir-ueber-uns/'])]
//     #[TestWith(['/unternehmen/presse/'])]
//     #[TestWith(['/unternehmen/jobs/'])]
//     #[TestWith(['/unternehmen/nachhaltigkeit/'])]
//     #[TestWith(['/unternehmen/referenzkunden/'])]
//     #[TestWith(['/unternehmen/kontakt/'])]
//     #[TestWith(['/unternehmen/datenschutz/'])]
//     public function testStaticPage(string $url): void {
//         $client = static::createClient();
//         $client->request(Request::METHOD_GET, $url);

//         $this->assertResponseIsSuccessful();
//     }
// }