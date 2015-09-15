Watimage: GD Image Helper PHP Class
===================================
[![Build status](https://img.shields.io/travis/elboletaire/Watimage.svg?style=flat-square)](https://travis-ci.org/elboletaire/Watimage)
[![Coveralls](https://img.shields.io/coveralls/elboletaire/Watimage.svg?style=flat-square)](https://coveralls.io/github/elboletaire/Watimage)
[![License](https://img.shields.io/packagist/l/elboletaire/Watimage.svg?style=flat-square)](https://github.com/elboletaire/Watimage/blob/master/LICENSE.md)
[![Version](https://img.shields.io/packagist/v/elboletaire/Watimage.svg?style=flat-square)](https://packagist.org/packages/elboletaire/watimage)

Watimage is a group of PHP classes to help you resize, rotate, apply filters,
watermarks and a lot more of things to your images using the PHP's GD library.

It was initially a CakePHP component, later became a simple Vendor class and now
is a powerful set of classes to alter images.

And it maintains the transparencies in almost every scenario* :sunglasses:.

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

### PHP 5.4

If you're running PHP 5.4 you need to define the following constants in order to
get Watimage properly working (are required by the `flip` method):

```php
define('IMG_FLIP_HORIZONTAL', 1);
define('IMG_FLIP_VERTICAL', 2);
define('IMG_FLIP_BOTH', 3);
```

Installing
----------

With composer:

```bash
composer require elboletaire/watimage ~2.0
```

As a git submodule:

```bash
git submodule add https://github.com/elboletaire/Watimage.git Vendor/Elboletaire/Watimage
```

Usage
-----

### Image class

The Image class is the main Watimage class. Is the one for treating images and
other Image classes (like Watermark) extend it to get all its features.

You can start creating an image in two ways: with a new empty canvas using `create`
or from a given local file using `load`:

```php
use Elboletaire\Watimage\Image;
$image = new Image();
$image->load('test.png');
```

You can use the constructor to bypass this method call:

```php
use Elboletaire\Watimage\Image;
$image = new Image('test.png');
```

After loading the image you can start modifying it as you want (check the
[api section](https://elboletaire.github.io/Watimage/api/) for a full list of methods):

```php
// [...]
$image
    ->resize('resizecrop', 400, 300)
    ->sepia()
    ->flip();
```

To export your image, just call `generate`. Indicating a filename as a
first parameter you'll store the image in that location. If null passed the
image will be generated on-screen creating its proper headers.


```php
// [...]
$image->generate(); // will directly output to the browser
```

Last but not least, if you pass a second argument to `generate` with a desired
mime type you will change your image format to that one:


```php
// [...]
$image->generate('output-image.jpg', 'image/jpeg');
```

Everything together:

```php
use Elboletaire\Watimage\Image;
$image = new Image('test.png');
$image
    ->resize('resizecrop', 400, 300)
    ->sepia()
    ->flip()
    ->generate('output-image.jpg', 'image/jpeg');
```

#### Resizing images

There are different type of resizes available in the `Image` class. See their
differences:

- `classicResize`: Resizes maintaining aspect ratio. Available throurgh `resize`
  method using `classic` or `resize` as type.
- `reduce` (and `resizeMin`): Resizes ain image maintaining aspect ratio but ONLY
  if the given image is bigger than the specified size. Available through `resize`
  method using `reduce`, `resizemin` or `min` as type.
- `crop`: A straight centered crop. Available through `resize` method using `crop`
  type.
- `resizeCrop`: Resizes to max, then crops to center; this is the recommended
  crop resize type. Available through `resize` method using `resizecrop`.

### Watermark class

The Watermark class extends Image so you'll be able to apply any filter or
action (like crop or resize) to your watermark as you do with your images.

To start using Watermarks you'll need to use both classes:

```php
use Elboletaire\Watimage\Image;
use Elboletaire\Watimage\Watermark;

$image = new Image('test.png');
$watermark = new Watermark('watermark.png');
```

After you've loaded your files you can treat both images at your like:

```php
$image->flip()->negate();
$watermark->pixelate(3, true);
```

When you want to apply your watermark to your image, simply do `apply`:

```php
$watermark->apply($image)->generate();
```

Watermark's apply method returns the Image instance so we can directly generate
the resulting image.

You can use a unique watermark to watermark multiple images or you can even
use the same watermark to apply it multiple times to the same image!:

```php
// Apply watermark to image
$watermark->apply($image);
// Filter image after applying the watermark (this will pixelate the first watermark too)
$image->pixelate(4, true);
// Apply watermark again but changing position
$watermark->setPosition('top left')->apply($image);
// This will generate a pixelated image with two watermarks, one of them
// pixelated due to the pixelation of the image after applying the first watermark.
$image->generate();
```

Everything together:


```php
use Elboletaire\Watimage\Image;
use Elboletaire\Watimage\Watermark;

$image = new Image('test.png');
$watermark = new Watermark('watermark.png');

$image->flip()->negate();
$watermark->pixelate(3, true);
// Apply watermark to image
$watermark->apply($image);
// Filter image after applying the watermark (this will pixelate the first watermark too)
$image->pixelate(4, true);
// Apply watermark again but changing position
$watermark->setPosition('top left')->apply($image);
$image->generate();
```

A more advanced example, applying the same watermark in each border of the image:

```php
use Elboletaire\Watimage\Image;
use Elboletaire\Watimage\Watermark;

$image = new Image('test.png');
$watermark = new Watermark('watermark.png');

// Pixelate the image
$image->pixelate(4, true);
// Apply all the watermarks
$watermark->setPosition('top left')->apply($image);
$watermark->setPosition('top right')->apply($image);
$watermark->setPosition('bottom right')->apply($image);
$watermark->setPosition('bottom left')->apply($image);
$image
    // Add a vignette effect with watermarks included
    ->vignette()
    // And save to file
    ->generate('my-saved-image.png');
```


These are just examples but take in mind that order really matters here.

### Normalize class

The normalize class is a static helper class that normalizes all the parameters
passed to any Watimage class.

### Watimage backwards compatibility class

A `Watimage` class has been created for backwards compatibility. If you were
using Watimage prior to its 2.0 version with composer you'll find this helpful
if you don't want to update your code or you wanna use Watimage *the old way*.

> The `Watimage` class is just a bridge between the `Image` and the `Watermark`
class.

```php
// We need to use the composer's autoloader. Otherwise we'll need to load
// every required class by Watimage (that's more than 8 files)
// require_once 'vendor/autoload.php';
use Elboletaire\Watimage\Watimage;

$wm = new Watimage();
$wm->setImage('original_file.png');
$wm->setWatermark('watermark.png');

// Resize image to 400x400px
$wm->resize(array('type' => 'resizecrop', 'size' => 400));

// Flip it horitzontally
$wm->flip('horizontal');

// Rotate 90 degrees
$wm->rotate(90);

// Apply the watermark
$wm->applyWatermark();

// Generate and save image
if ( !$wm->generate('result.png') ) {
    // handle errors...
    print_r($wm->errors);
}
```

![result_example](http://www.racotecnic.com/wp-content/uploads/2011/04/test6.png "result_example")

API
---

Check out the API at https://elboletaire.github.io/Watimage/api/

### Exceptions

All Watimage exceptions extend from PHP's default Exception class. In Watimage
there are 5 custom exception classes:

- ExtensionNotLoadedException
- FileNotExistException
- InvalidArgumentException
- InvalidExtensionException
- InvalidMimeException

Knowing that, you can catch your exceptions one by one:

```php
use Elboletaire\Watimage\Exception\ExtensionNotLoadedException;
use Elboletaire\Watimage\Exception\FileNotExistException;
use Elboletaire\Watimage\Exception\InvalidArgumentException;
use Elboletaire\Watimage\Exception\InvalidExtensionException;
use Elboletaire\Watimage\Exception\InvalidMimeException;
use Elboletaire\Watimage\Image;
use Elboletaire\Watimage\Watermark;

try {

} catch (ExtensionNotLoadedException $e) {
} catch (FileNotExistException $e) {
} catch (InvalidArgumentException $e) {
} catch (InvalidExtensionException $e) {
} catch (InvalidMimeException $e) {
}
```

Or simply catch from `Exception`:

```php
use Elboletaire\Watimage\Image;
use Elboletaire\Watimage\Watermark;
use Exception;

try {

} catch (Exception $e) {
}
```

Examples
--------

You have a lot of examples under `examples` folder.

Before running the examples you'll need to `composer install` from `Watimage`
root folder in order to get composer's autoloader downloaded into `vendor` folder.

```bash
composer install
cd examples
php Image.php
php Watermark.php
# Backwards compatibility class examples
php Watimage.php
```

Or you could put Watimage in your local webhost webroot dir and then point there
your browser.

Testing
-------

### Unit tests

To run phpunit tests just run phpunit from `Watimage` root path. But first
ensure you have installed composer dependencies; tests need the composer
`autoload.php` file in order to work properly:

```bash
composer install
phpunit
# PHPUnit 4.8.6 by Sebastian Bergmann and contributors.
# ...........................................................
# Time: 34.53 seconds, Memory: 80.25Mb
# OK (59 tests, 182 assertions)
```

If you do not have phpunit installed system-wide just do:

```bash
composer install
./vendor/bin/phpunit
# PHPUnit 4.8.6 by Sebastian Bergmann and contributors.
# ...........................................................
# Time: 34.53 seconds, Memory: 80.25Mb
# OK (59 tests, 182 assertions)
```

The `testVignetteMethod` requires a bit more than other methods to finish. For
that reason I've added it to a phpunit `@group` named `slow` to easily skip it:

```bash
phpunit --exclude-group slow
# PHPUnit 4.8.6 by Sebastian Bergmann and contributors.
# .........................................................
# Time: 16.18 seconds, Memory: 77.00Mb
# OK (57 tests, 180 assertions)
```

You can skip them using the whole `effects` group too:

```bash
phpunit --exclude-group effects
# PHPUnit 4.8.6 by Sebastian Bergmann and contributors.
# ..........................................
# Time: 6.95 seconds, Memory: 31.00Mb
# OK (42 tests, 149 assertions)
```

#### About code coverage

Most of the filter methods plus some other methods that do not have much logic
have not been tested as it would be redundant to test core php methods.

### Visual tests

Inside `tests/visual` you'll find a script to visually check that all images
are generated properly.

To run them just cd into the visual tests folders and run the `run_them_all.php` script:

```bash
composer install
cd tests/visual
php run_them_all.php
```

It will generate a bunch of files in `tests/visual/results` where you can check
if everything is running as expected.

TODO
----

- Add a Text class to add texts to our images + a TTF class to use True Type
  Fonts on that texts.
- Add an `Effect` class to apply effects and move all the effects from Image to
  that new class.
- Add an `InstalikeEffect` class with a bunch more of image effects.
- Any new features are welcome!
- Fix transparency issues with gif images*

>\* Current transparency failing scenarios:
    - rotate and resize gif images.

Changelog
---------

Check out the [releases on github](https://github.com/elboletaire/Watimage/releases).
They have different order than expected because I've created tags recently to
not loose them and to have the whole changelog there.

LICENSE
-------

All the images given in this repository (for examples and testing) have a
Creative Commons by-nc-sa 4.0 License.

Since version 2.0 all the other non-image-files are licensed under a MIT license.

    The MIT License (MIT)

    Copyright (c) 2015 Òscar Casajuana <elboletaire at underave dot net>

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
    SOFTWARE.
