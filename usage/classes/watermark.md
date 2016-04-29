---
layout: page
title: Watimage Watermark Class Usage
image:
  feature: el-carmel-viewpoint.jpg
  credit: elboletaire
  creditlink: https://github.com/elboletaire/Watimage/blob/master/examples/files/LICENSE
comments: true
modified: 2016-04-29
---

The Watermark class extends Image so you'll be able to apply any filter or
action (like crop or resize) to your watermark as you do with your images.

To start adding Watermarks you'll need to use both classes:

~~~php?start_inline=1
use Elboletaire\Watimage\Image;
use Elboletaire\Watimage\Watermark;

$image = new Image('test.png');
$watermark = new Watermark('watermark.png');
~~~

After you've loaded your files you can treat both images at your like:

~~~php?start_inline=1
$image->flip()->negate();
$watermark->pixelate(3, true);
~~~

When you want to apply your watermark to your image, simply do `apply`:

~~~php?start_inline=1
$watermark->apply($image)->generate();
~~~

Watermark's `apply` method returns the `Image` instance so you can directly generate
the resulting image.

You can use a unique watermark to watermark multiple images or you can even
use the same watermark to apply it multiple times to the same image!

~~~php?start_inline=1
// Apply watermark to image
$watermark->apply($image);
// Filter image after applying the watermark (pixelating the first watermark too)
$image->pixelate(4, true);
// Apply watermark again but changing position
$watermark->setPosition('top left')->apply($image);
// This will generate a pixelated image with two watermarks, one of them
// pixelated due to the filter applied to the image after adding the first watermark.
$image->generate();
~~~

Everything together:

~~~php?start_inline=1
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
~~~

An advanced example, applying the same watermark in each border of the image:

~~~php?start_inline=1
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
~~~

[← Go back to Watimage Classes Usage]({{ site.url }}/usage/classes)

{% include about-image/el-carmel-viewpoint.md %}
