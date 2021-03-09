<?php

/*
 * This file is part of the OpenClassrooms Symfony course project.
 *
 * (c) Mickaël Andrieu <mickael.andrieu@solvolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Admin;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional test for the controllers defined inside the BlogController used
 * for managing the blog in the backend.
 *
 * See https://symfony.com/doc/current/testing.html#functional-tests
 *
 * Whenever you test resources protected by a firewall, consider using the
 * technique explained in:
 * https://symfony.com/doc/current/testing/http_authentication.html
 *
 * Execute the application tests using this command (requires PHPUnit to be installed):
 *
 *     $ cd your-symfony-project/
 *     $ ./vendor/bin/phpunit
 */
class BlogControllerTest extends WebTestCase
{
    /**
     * @dataProvider getUrlsForRegularUsers
     */
    public function testAccessDeniedForRegularUsers(string $httpMethod, string $url): void
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'bruno',
            'PHP_AUTH_PW' => 's3cr3t',
        ]);

        $client->request($httpMethod, $url);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function getUrlsForRegularUsers(): ?\Generator
    {
        yield ['GET', '/admin/post/'];
        yield ['GET', '/admin/post/1'];
        yield ['GET', '/admin/post/1/edit'];
        yield ['POST', '/admin/post/1/delete'];
    }

    public function testAdminBackendHomePage(): void
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'léa',
            'PHP_AUTH_PW' => 'p4ssw0rd',
        ]);
        $client->request('GET', '/admin/post/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists(
            'body#admin_post_index #main tbody tr',
            'The backend homepage displays all the available posts.'
        );
    }

    /**
     * This test changes the database contents by creating a new blog post. However,
     * thanks to the DAMADoctrineTestBundle and its PHPUnit listener, all changes
     * to the database are rolled back when this test completes. This means that
     * all the application tests begin with the same database contents.
     */
    public function testAdminNewPost(): void
    {
        $postTitle = 'Blog Post Title '.mt_rand();
        $postSummary = $this->generateRandomString(255);
        $postContent = $this->generateRandomString(1024);

        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'léa',
            'PHP_AUTH_PW' => 'p4ssw0rd',
        ]);
        $client->request('GET', '/admin/post/new');
        $client->submitForm('Créer un article', [
            'post[title]' => $postTitle,
            'post[summary]' => $postSummary,
            'post[content]' => $postContent,
        ]);

        $this->assertResponseRedirects('/admin/post/', Response::HTTP_FOUND);

        /** @var \App\Entity\Post $post */
        $post = self::$container->get(PostRepository::class)->findOneByTitle($postTitle);
        $this->assertNotNull($post);
        $this->assertSame($postSummary, $post->getSummary());
        $this->assertSame($postContent, $post->getContent());
    }

    public function testAdminNewDuplicatedPost(): void
    {
        $postTitle = 'Blog Post Title '.mt_rand();
        $postSummary = $this->generateRandomString(255);
        $postContent = $this->generateRandomString(1024);

        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'léa',
            'PHP_AUTH_PW' => 'p4ssw0rd',
        ]);
        $crawler = $client->request('GET', '/admin/post/new');
        $form = $crawler->selectButton('Enregistrer')->form([
            'post[title]' => $postTitle,
            'post[summary]' => $postSummary,
            'post[content]' => $postContent,
        ]);
        $client->submit($form);

        // post titles must be unique, so trying to create the same post twice should result in an error
        $client->submit($form);

        $this->assertSelectorTextSame('form .form-group.has-error label', 'Titre');
        $this->assertSelectorTextContains('form .form-group.has-error .help-block', 'Ce titre est déjà utilisé et il doit être unique.');
    }

    public function testAdminShowPost(): void
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'léa',
            'PHP_AUTH_PW' => 'p4ssw0rd',
        ]);
        $client->request('GET', '/admin/post/1');

        $this->assertResponseIsSuccessful();
    }

    /**
     * This test changes the database contents by editing a blog post. However,
     * thanks to the DAMADoctrineTestBundle and its PHPUnit listener, all changes
     * to the database are rolled back when this test completes. This means that
     * all the application tests begin with the same database contents.
     */
    public function testAdminEditPost(): void
    {
        $newBlogPostTitle = 'Blog Post Title '.mt_rand();

        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'léa',
            'PHP_AUTH_PW' => 'p4ssw0rd',
        ]);
        $client->request('GET', '/admin/post/1/edit');
        $client->submitForm('Enregistrer', [
            'post[title]' => $newBlogPostTitle,
        ]);

        $this->assertResponseRedirects('/admin/post/1/edit', Response::HTTP_FOUND);

        /** @var \App\Entity\Post $post */
        $post = self::$container->get(PostRepository::class)->find(1);
        $this->assertSame($newBlogPostTitle, $post->getTitle());
    }

    /**
     * This test changes the database contents by deleting a blog post. However,
     * thanks to the DAMADoctrineTestBundle and its PHPUnit listener, all changes
     * to the database are rolled back when this test completes. This means that
     * all the application tests begin with the same database contents.
     */
    public function testAdminDeletePost(): void
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'léa',
            'PHP_AUTH_PW' => 'p4ssw0rd',
        ]);
        $crawler = $client->request('GET', '/admin/post/1');
        $client->submit($crawler->filter('#delete-form')->form());

        $this->assertResponseRedirects('/admin/post/', Response::HTTP_FOUND);

        $post = self::$container->get(PostRepository::class)->find(1);
        $this->assertNull($post);
    }

    private function generateRandomString(int $length): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return mb_substr(str_shuffle(str_repeat($chars, ceil($length / mb_strlen($chars)))), 1, $length);
    }
}
