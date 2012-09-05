#About
Watimage is a PHP class to resize, rotate and apply watermark to images.

It was initially thought as a CakePHP Component, so it's really easy to use it as is.


##Usage

### As standalone class

```
require_once 'watimage.php';

$wm = new Watimage();
$wm->setImage('test.png');
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
		if ( !$this->Watimage->generate('result.png') ) {
			// handle errors...
			debug($this->Watimage->errors);
		}
	}
}
```

![result_example](http://www.racotecnic.com/wp-content/uploads/2011/04/test6.png "result_example")

You have an example.php file with more examples.

## Changelog

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
