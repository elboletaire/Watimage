Watimage: GD Image Helper PHP Class
===================================

[![Build status](https://img.shields.io/travis/elboletaire/Watimage.svg?style=flat-square)](https://travis-ci.org/elboletaire/Watimage)
[![Code coverage](https://img.shields.io/coveralls/elboletaire/Watimage.svg?style=flat-square)](https://coveralls.io/github/elboletaire/Watimage)
[![License](https://img.shields.io/packagist/l/elboletaire/Watimage.svg?style=flat-square)](https://github.com/elboletaire/Watimage/blob/master/LICENSE.md)
[![Latest Stable Version](https://img.shields.io/github/release/elboletaire/Watimage.svg?style=flat-square)](https://github.com/elboletaire/Watimage/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/elboletaire/Watimage.svg?style=flat-square)](https://packagist.org/packages/elboletaire/Watimage)
[![Code Climate](https://img.shields.io/codeclimate/github/elboletaire/Watimage.svg?style=flat-square)](https://codeclimate.com/github/elboletaire/Watimage)

Watimage is a group of PHP classes to help you resize, rotate, apply filters,
watermarks and a lot more of things to your images using the PHP's GD library.

It was initially a CakePHP component, later became a simple Vendor class and now
is a powerful set of classes to alter images.

And it maintains the transparencies in every scenario.

Requirements
------------

You need php > 5.4 and the [php GD](http://php.net/manual/book.image.php)
package installed.

With aptitude:

```bash
sudo apt-get install php5-gd
```

With yum:

```bash
sudo yum install php-gd
```

Installing
----------

With composer:

```bash
composer require elboletaire/watimage:~2.0.0
```

Check out the Watimage pages for
[more information about installation](https://elboletaire.github.io/Watimage/usage/setup).

Usage
-----

See all the information you need to know
[at the Watimage pages](https://elboletaire.github.io/Watimage/usage)

API
---

Check out [the API](https://elboletaire.github.io/Watimage/api)
[at the Watimage pages](https://elboletaire.github.io/Watimage/usage).

Examples
--------

There are lot of examples at the `examples` folder, plus a lot more of examples
and tutorials [in the Watimage pages](https://elboletaire.github.io/Watimage).

Before running the given examples, you need to `composer install` from
`Watimage` root folder in order to get composer's autoloader downloaded into
`vendor` folder.

```bash
composer install
cd examples
php Image.php
php Watermark.php
php Watimage.php
```

You can also put the `Watimage` folder in your local webhost webroot dir and
[point there](http://localhost/Watimage/examples/Image.php) your browser.

Testing
-------

Please, see the information about testing in
[the Watimage pages](https://elboletaire.github.io/Watimage/usage/testing).

Patches & Features
------------------

+ Fork
+ Mod, fix
+ Test - this is important, so it's not unintentionally broken
+ Commit - do not mess with license, todo, version, etc. (if you do change any, bump them into commits of
their own that I can ignore when I pull)
+ Pull request - bonus point for topic branches

Bugs & Feedback
---------------

See the [issues section](https://github.com/elboletaire/Watimage/issues).

Changelog
---------

Check out the [releases on github](https://github.com/elboletaire/Watimage/releases).
They have different order than expected because I've created tags recently to
not loose them and to have the whole changelog there.

LICENSE
-------

See [LICENSE.md](./LICENSE.md).
