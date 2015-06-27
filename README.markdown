Watimage: Watermark and Image PHP Class
=======================================

Watimage is a PHP class to resize, rotate and apply watermark to images.

It was initially thought as a CakePHP Component, but now is a standalone class,
so you can use it everywhere :D

Installing
----------

With composer:

```bash
composer require elboletaire/watimage 1.0.0
```

As a git submodule:

```bash
git submodule add https://github.com/elboletaire/Watimage.git Vendor/Elboletaire/Watimage
```

Usage
-----

### As standalone class

```php
// In top of your document (if you use composer's autoloader)
use Elboletaire\Watimage\Watimage;
// If you're not using composer's autoloader, then require it manually
// require_once 'Vendor/Elboletaire/Watimage/src/Watimage.php';

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

You have an example.php file with more examples.

Testing
-------

Currently there are only methods to visually check that all images are properly
generated.

To run them just cd into the visual tests folders and run the `run_them_all.php` script:

```bash
cd tests/visual
php run_them_all.php
```

It will generate a bunch of files in `tests/visual/results` where you can check
if everything is running as expected.

## Changelog

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


## More info

* http://www.racotecnic.com
* Spanish post (newer): http://www.racotecnic.com/2011/04/clase-php-para-tratar-imagenes-rotar-redimensionar-anadir-marcas-de-agua/
* English post (without new features): http://www.racotecnic.com/2010/04/watermark-image-component-for-cakephp/

### LICENSE

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
