<?php

/*
 * This file is part of the OpenClassrooms Symfony course project.
 *
 * (c) Mickaël Andrieu <mickael.andrieu@solvolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\Post;
use App\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional test for the controllers defined inside BlogController.
 *
 * See https://symfony.com/doc/current/testing.html#functional-tests
 *
 * Execute the application tests using this command (requires PHPUnit to be installed):
 *
 *     $ cd your-symfony-project/
 *     $ ./vendor/bin/phpunit
 */
class BlogControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        $this->assertCount(
            Paginator::PAGE_SIZE,
            $crawler->filter('article.post'),
            'La page d\'accueil affiche le bon nombre d\'articles.'
        );
    }

    public function testRss(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/rss.xml');

        $this->assertResponseHeaderSame('Content-Type', 'text/xml; charset=UTF-8');

        $this->assertCount(
            Paginator::PAGE_SIZE,
            $crawler->filter('item'),
            'Le fichier XML affiche le bon nombre d\articles.'
        );
    }

    /**
     * This test changes the database contents by creating a new comment. However,
     * thanks to the DAMADoctrineTestBundle and its PHPUnit listener, all changes
     * to the database are rolled back when this test completes. This means that
     * all the application tests begin with the same database contents.
     */
    public function testNewComment(): void
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'bruno',
            'PHP_AUTH_PW' => 's3cr3t',
        ]);
        $client->followRedirects();

        // Find first blog post
        $crawler = $client->request('GET', '/');
        $postLink = $crawler->filter('article.post > h2 a')->link();

        $client->click($postLink);
        $crawler = $client->submitForm('Publier', [
            'comment[content]' => 'Bravo pour cet article !',
        ]);

        $newComment = $crawler->filter('.post-comment')->first()->filter('div > p')->text();

        $this->assertSame('Bravo pour cet article !', $newComment);
    }

    public function testAjaxSearch(): void
    {
        $client = static::createClient();
        $client->xmlHttpRequest('GET', '/search', ['q' => 'PHP']);

        $results = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertCount(1, $results);
        $this->assertSame('PHP, langage plébiscité par les développeuses d\'OpenClassrooms', $results[0]['title']);
        $this->assertSame('Léa Dupont', $results[0]['author']);
    }
}
