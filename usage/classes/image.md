---
layout: page
title: Watimage Image Class Usage
image:
  feature: freeparty.jpg
  credit: elboletaire
  creditlink: https://github.com/elboletaire/Watimage/blob/master/examples/files/LICENSE
comments: true
modified: 2016-04-29
---

The `Image` class is the main Watimage class.

Is the one for treating images and other `Image` related classes (like Watermark)
extend it to get all its features.

You can start creating an image in at least three ways:

- With a new empty canvas using `create`.
- From a string, using `fromString`.
- From a given local file using `load`.

Let's start with the commonly used `load`:

~~~php?start_inline=1
use Elboletaire\Watimage\Image;
$image = new Image();
$image->load('test.png');
~~~

You can use the constructor to bypass this method call:

~~~php?start_inline=1
use Elboletaire\Watimage\Image;
$image = new Image('test.png');
~~~

Creating canvas from scratch
----------------------------

Using the `create` method you can create an empty canvas:

~~~php?start_inline=1
use Elboletaire\Watimage\Image;

$image = new Image();
$resource = $image
  ->create(250, 250);  // creates a 250px square canvas
~~~

You can also use the `create` method to empty the current canvas:

~~~php?start_inline=1
use Elboletaire\Watimage\Image;

$image = new Image('test.png');
$resource = $image
  ->create();  // creates an empty canvas with test.png image's size
~~~

Loading images from strings
---------------------------

With `fromString` you can easily generate an image from a string. It can be
either base64 encoded or not, Watimage will understand it anyway:

~~~php?start_inline=1
use Elboletaire\Watimage\Image;

$encoded = file_get_contents('base64image.txt');

$image = new Image();
$resource = $image
  ->fromString($encoded);
~~~

In case the base64 encoded image has the preceding `data:` string, which contains
the image mime type, Watimage will use that information for outputting the proper
format.

If there's no `data:` string preceding the encoded image, Watimage will
use [`fileinfo`](http://php.net/manual/en/function.finfo-buffer.php)
(in case it's available on your system) to guess the image format.

In case there's no `data:` nor `fileinfo` installed on your system, you'll need
to [manually specify the desired output format](#image-output-and-format-conversion).

Resizing and cropping images
----------------------------

Watimage comes with four bundled resize methods, all of them maintain the aspect
ratio:

- `reduce`: Resizes an image making sure it fits within the max width and max
  height. In case the image is bigger than that, will not be enlarged.
- `classicResize`: Resizes an image making sure it fits within the max width and
  max height. Note that using this you'll enlarge small images too.
- `classicCrop`: Crops and image at specified position with the desired width and height.
- `resizeCrop`: Resizes to max possible size and then crops maintaining the image
  centered. Recommended method for thumbnail generation.

~~~php?start_inline=1
use Elboletaire\Watimage\Image;

$image = new Image('test.png');
$resource = $image
  ->reduce(1024, 768)
  ->save();
~~~

Or you can use the `resize` method to call any of them:

~~~php?start_inline=1
use Elboletaire\Watimage\Image;

$image = new Image('test.png');
$resource = $image
  ->resize('reduce', 1024, 768)
  ->save();
~~~

If you wanna specify a square size, there's no need for the `$height` argument:

~~~php?start_inline=1
use Elboletaire\Watimage\Image;

$image = new Image('test.png');
$resource = $image
  ->resize('resizeCrop', 1024) // will generate a 1024x1024 image
  ->save();
~~~

In case you would like to resize not maintaining the aspect ratio you should
[treat images directly](#treating-images-directly).

Applying image filters
----------------------

Please, take a look to [the Watimage api docs]({{ site.url }}/api)
to see all the available filter methods.

Here are some of them:

~~~php?start_inline=1
use Elboletaire\Watimage\Image;

$image = new Image('test.png');
$resource = $image
  ->blur('selective', 2)
  ->brigthness(-10)
  ->contrast(-5)
  ->colorize('#fafafa')
  ->pixelate(2, true)
  ->emboss()
  ->grayscale()
  ->negate()
  ->sepia()
  ->smooth()
  ->vignette()
  ->edgeDetection()
  ->meanRemove()
;
~~~

Treating images directly
------------------------

You can get the image resource at any time and do whatever you want with it
using the `getImage` and `setImage` methods.

In the example below you'll see how to resize an image without maintaining the
aspect ratio:

~~~php?start_inline=1
use Elboletaire\Watimage\Image;

$image = new Image('input.jpg');
// Create an empty canvas for the resized image
$resized = new Image();
$resized->create(250, 250);
// Get the resource objects
$resImage = $image->getImage();
$resResized = $resized->getImage();
// Obtain original image metadata (we need its height and width)
$metadata = $image->getMetadata();
// Copy to destiny resizing without maintaining aspect ratio
imagecopyresampled(
  $resResized, $resImage, // resources
  0, 0, 0, 0, // position
  250, 250, $metadata['width'], $metadata['height'] // sizes
);

// Return the image resource to the Image instance
$resized->setImage($resResized)
    // and save
    ->generate('resized.jpg');
~~~

Image output and format conversion
----------------------------------

To output files you have two methods: `generate` and `save`.

BTW, `save` calls `generate` passing the current filename in case we've not
specified one. That means that calling `save` without arguments will overwrite
the loaded file:

~~~php?start_inline=1
use Elboletaire\Watimage\Image;

$image = new Image('test.png');
$resource = $image
  ->save(); // overwrites `test.png`
~~~

Unlike with `save`, if you call `generate` without arguments you will outputting
that file directly to the browser:

~~~php?start_inline=1
use Elboletaire\Watimage\Image;

$image = new Image('test.png');
$resource = $image
  ->generate(); // shows test.png on screen (generating the proper headers for it)
~~~

Watimage guesses the output format from the providen file extension so every time
you define a different file format you will be doing format conversion:

~~~php?start_inline=1
use Elboletaire\Watimage\Image;

$image = new Image('test.png');
$resource = $image
  ->generate('test.jpeg');
~~~

In case you wanna output an image changing the file format, you'll need to
specify it's mime type as second argument instead:

~~~php?start_inline=1
use Elboletaire\Watimage\Image;

$image = new Image('test.png');
$resource = $image
  ->generate(null, 'image/jpeg');
~~~

[← Go back to Watimage Classes Usage]({{ site.url }}/usage/classes)

{% include about-image/freeparty.md %}
