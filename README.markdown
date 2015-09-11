Watimage: GD Image Helper PHP Class
===================================

Watimage is a group of PHP classes to help you resize, rotate, apply filters,
watermarks and a lot more of things to your images using the PHP's GD library.

It was initially a CakePHP component, later became a simple Vendor class and now
is a powerful set of classes to alter images.

And it maintains the transparencies in almost every scenario :sunglasses:.

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
[api section](#user-content-api) for a full list of methods):

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

[working on it..]

### Exceptions

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

To run phpunit tests, composer install phpunit and then run it from `vendor`
folder:

```bash
composer install
vendor/bin/phpunit
# PHPUnit 4.8.6 by Sebastian Bergmann and contributors.
# ...........................
# Time: 4.75 seconds, Memory: 12.75Mb
# OK (27 tests, 77 assertions)
```

#### About code coverage

Most of the filter methods plus some other methods that do not have much logic
have not been tested as it would be redundant to test core php methods.

### Visual tests

Inside `tests/visual` you'll find a script to visually check that all images
are generate properly.

To run them just cd into the visual tests folders and run the `run_them_all.php` script:

```bash
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

Changelog
---------

* **2.0.0** [2015/09/XX] MAJOR UPGRADE
    * Split all logic into different classes
        - Image: Generate images and deals with them.
        - Watermark: Logic for applying watermarks
        - Normalizer: Used by functions to allow user pass arguments in a lot of
          different ways.
        - Watimage: backward compatible class. If you upgrade Watimage to this
          version from 1.0.2 you won't need to carry about the migration.
    * Added a lot of new methods to deal with images (see the [api in readme](../readme.md#user-content-api)).
    * Reads exif information for jpeg files by default.
    * Autoorientate method based on exif information.

* **1.0.2** [2015/06/27]
    * Minor coding style changes.
    * Fixed applying watermarks to gif files will make its background black.
    * Images are allways rotated clockwise (imagerotate was rotating them reversely).
    * Fixed imagerotate crashing when receiving a bgcolor of -1.
    * Added different compression value for png files.
    * Added visual tests.

* **1.0.1** [2015/06/25]
    * Fixed example to use namespaces.
    * Fixed issue with imagerotate.

* **1.0.0** [2014/12/08]
    * Added to packagist (composer)
    * Changed everything to work as a vendor class

* **0.8** [2014/10/02]
    * Fixed `resizemin` resizing method (thanks to @albertboada).
    * Fixed "indirect modification of overloaded property"

* **0.8** [2012/09/05]
    * Version for CakePHP 2.X (thanks to Pyo [pexiweb.be])

* **0.7** [2012/07/12]
    * Added new resizing method 'reduce' to resize images ONLY if they are bigger than the specified size.

* **0.6** [2012/04/18]
    * Added new method 'crop' for using it with cropping tools like jcrop
    * Minor bugfixes

* **0.5** [2012/01/14]
    * Resolved all transparency issues

* **0.2.3** [2012/01/12]
    * Resolved transparency issues on rotate (by https://github.com/fcjurado)

* **0.2.2** [2011/05/14]
    * Fix exponential reduction of image quality when inside a loop

* **0.2.1** [2011/05/11]
    * Added 'setQuality' method.
    * Solved png exportation issue (bad quality calc)
    * Also added 'initialize' method for CakePHP, allowing to set the quality when component loads

* **0.2** [2011/04/16]
    * Now works with Exceptions.
    * mime_content_type function has been removed (as it was deprecated).
    * Added flip function.
    * Minor bugfixes

* **0.1** [2010/06/10]
    * First version

LICENSE
-------

Images given under examples and test folders have a Creative Commons by-nc-sa
4.0 License.

Since version 2.0 all the other files are licensed under a MIT license.

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
