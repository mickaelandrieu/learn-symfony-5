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

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional test for the controllers defined inside the UserController used
 * for managing the current logged user.
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
class UserControllerTest extends WebTestCase
{
    /**
     * @dataProvider getUrlsForAnonymousUsers
     */
    public function testAccessDeniedForAnonymousUsers(string $httpMethod, string $url): void
    {
        $client = static::createClient();
        $client->request($httpMethod, $url);

        $this->assertResponseRedirects(
            'http://localhost/login',
            Response::HTTP_FOUND,
            sprintf('The %s secure URL redirects to the login form.', $url)
        );
    }

    public function getUrlsForAnonymousUsers(): ?\Generator
    {
        yield ['GET', '/profile/edit'];
        yield ['GET', '/profile/change-password'];
    }

    public function testEditUser(): void
    {
        $newUserEmail = 'lea.changed@openclassrooms.com';

        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'léa',
            'PHP_AUTH_PW' => 'p4ssw0rd',
        ]);
        $response = $client->request('GET', '/profile/edit');
        $client->submitForm('Enregistrer', [
            'user[email]' => $newUserEmail,
        ]);

        $this->assertResponseRedirects('/profile/edit', Response::HTTP_FOUND);

        /** @var \App\Entity\User $user */
        $user = self::$container->get(UserRepository::class)->findOneByEmail($newUserEmail);

        $this->assertNotNull($user);
        $this->assertSame($newUserEmail, $user->getEmail());
    }

    public function testChangePassword(): void
    {
        $newUserPassword = 'new-password';

        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'léa',
            'PHP_AUTH_PW' => 'p4ssw0rd',
        ]);
        $client->request('GET', '/profile/change-password');
        $client->submitForm('Enregistrer', [
            'change_password[currentPassword]' => 'p4ssw0rd',
            'change_password[newPassword][first]' => $newUserPassword,
            'change_password[newPassword][second]' => $newUserPassword,
        ]);

        $this->assertResponseRedirects(
            '/logout',
            Response::HTTP_FOUND,
            'Changing password logout the user.'
        );
    }
}
