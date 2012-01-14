#About
Watimage is a PHP class to resize, rotate and apply watermark to images.

It was initially thought as a CakePHP Component, so it's really easy to use it as is.


##Usage

### As standalone class

```
require_once 'watimage.php';

$wm = new Watimage('test.png', 'watermark.png');

// Resize image to 400x400px
$wm->resize(array('type' => 'resizecrop', 'size' => 400));

// Flip it horitzontally
$wm->flip('horizontal');

// Rotate 90 degrees
$wm->rotate(90);

// Apply the watermark
$wm->applyWatermark();

// Generate and save image
if ( !$wm->generate('test6.png') ) {
	// handle errors...
	print_r($wm->errors);
}
```

### As a CakePHP Component

Before using Watimage as a CakePHP Component, you must uncoment this:

```
class Watermark//Component extends Object
```

So that it is..

```
class WatermarkComponent extends Object
```

Example Controller:

```
class FooController extends AppController
{
	public $components = array(
		// You can set this later using setQuality()
		'Watimage' => array('quality' => 80)
	);
	
	public function bar()
	{
		// Set image and watermark
		$this->Watimage->setImage('test.png');
		$this->Watimage->setWatermark('watermark.png');
		// Resize image to 400x400px
		$this->Watimage->resize(array('type' => 'resizecrop', 'size' => 400));

		// Flip it horitzontally
		$this->Watimage->flip('horizontal');

		// Rotate 90 degrees
		$this->Watimage->rotate(90);

		// Apply the watermark
		$this->Watimage->applyWatermark();

		// Generate and save image
		if ( !$this->Watimage->generate('test6.png') ) {
			// handle errors...
			print_r($this->Watimage->errors);
		}
	}
}
```

You have an example.php file with more examples.

## Changelog

* [2012/01/14] 0.5 Resolved all transparency issues
* [2012/01/12] 0.2.3
	* Resolved transparency issues on rotate (by https://github.com/fcjurado)
* [2011/05/14] 0.2.2
	* Fix exponential reduction of image quality when inside a loop
* [2011/05/11] 0.2.1 
	* added 'setQuality' method. Solved png exportation issue (bad quality calc)
	* Also added 'initialize' method for CakePHP, allowing to set the quality when component loads
* [2011/04/16] 0.2
	* Now works with Exceptions. 
	* mime_content_type function has been removed (as it was deprecated).
	* Added flip function. 
	* Minor bugfixes

## More info

http://www.racotecnic.com
· Spanish post (newer): http://www.racotecnic.com/2011/04/clase-php-para-tratar-imagenes-rotar-redimensionar-anadir-marcas-de-agua/
· English post (without new features): http://www.racotecnic.com/2010/04/watermark-image-component-for-cakephp/


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
