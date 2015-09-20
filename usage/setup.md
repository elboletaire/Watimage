---
layout: page
title: Watimage Setup
description: Kick-starting Watimage PHP class
image:
  feature: el-carmel-viewpoint.jpg
  credit: elboletaire
  creditlink: https://github.com/elboletaire/Watimage/blob/master/examples/files/LICENSE
share: true
modified: 2015-09-20
---

Installing Watimage is pretty easy if you use [composer](https://getcomposer.org):

~~~bash
composer require elboletaire/watimage ~2.0
~~~

After installing Watimage ensure you're loading composer's autoloader:

~~~php
<?php
// In your main project's file or wherever you wanna load Watimage
require_once 'vendor/autoload.php';
~~~

Without composer
----------------

If not using composer you can add Watimage however you want, but I recommend you
using git submodules:

~~~bash
git submodule add https://github.com/elboletaire/Watimage.git vendor/Watimage
~~~

After that you'll need to manually add each required file:

~~~php
require_once 'vendor/Watimage/src/Image.php';
require_once 'vendor/Watimage/src/Watermark.php';
require_once 'vendor/Watimage/src/Exception/ExtensionNotLoadedException.php';
require_once 'vendor/Watimage/src/Exception/FileNotExistException.php';
require_once 'vendor/Watimage/src/Exception/InvalidArgumentException.php';
require_once 'vendor/Watimage/src/Exception/InvalidExtensionException.php';
require_once 'vendor/Watimage/src/Exception/InvalidMimeException.php';
// any other required class
~~~

Just load those needed by you (e.g. if you ain't gonna use the `Watermark` class
you can skip it).

Since you've loaded Watimage you can start using it:

~~~php
use Elboletaire\Watimage\Image;
use Elboletaire\Watimage\Watermark;

$image = new Image('tripi.jpg');
$image
  ->pixelate(5, true)
  ->generate('pixelated-tripi.jpg');
~~~


Using Watimage with PHP 5.4
---------------------------

If you're running PHP 5.4 you need to define the following constants in order to
get Watimage properly working (are required by the `flip` method):

~~~php
define('IMG_FLIP_HORIZONTAL', 1);
define('IMG_FLIP_VERTICAL', 2);
define('IMG_FLIP_BOTH', 3);
~~~

{% include about-image-header.md %}

~~~php
$image = new Image('el-carmel-viewpoint.jpg');
$image
  ->contrast(-5)
  ->brightness(-60)
  ->colorize(['r' => 100, 'g' => 70, 'b' => 50, 'a' => 0])
  ->brightness(-30)
  ->contrast(-5)
  ->generate()
;
~~~

{% include about-image-footer.md %}
