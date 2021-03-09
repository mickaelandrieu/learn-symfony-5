Le Blog de Léa
==============

Le Blog de Léa est une application qui sert de fil rouge et de projet final au cours "Concevez un site web avec le framework Symfony" disponible sur le site d'OpenClassrooms.

Il est basé sur l'application de [démonstration de l'équipe Symfony](https://github.com/symfony/demo), qui est une référence pour expliquer aux débutantes et débutants comment développer leurs applications dans le respect des meilleures pratiques.

Pré-requis
----------

  * PHP 7.2.5 ou plus;
  * L'extension PHP PDO-SQLite activée;
  * Le logiciel Symfony CLI;
  * et [les pré-requis usuels du framework Symfony][1].

Installation
------------

Exécutez les commandes suivantes pour installer le projet:

```bash
$ git clone https://github.com/OpenClassrooms-Student-Center/learn-symfony-5.git
$ cd learn-symfony-5
$ php bin/console do:sc:up --force
$ php bin/console do:fi:lo
```

Usage
-----

Il n'y a rien à faire de spécial pour démarrer l'application. Exécutez la commande suivante qui exécute un serveur web et accéder à l'url suivante à l'aide d'un navigateur <http://localhost:8000>:

```bash
$ cd learn-symfony-5/
$ symfony serve
```

Tests
-----

La commande suivante exécutera la suite de tests :

```bash
$ cd learn-symfony-5/
$ ./vendor/bin/simple-phpunit
```

[1]: https://symfony.com/doc/current/reference/requirements.html
