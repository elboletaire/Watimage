---
layout: page
title: Watimage Class Usage
image:
  feature: sweet-home.jpg
  credit: elboletaire
  creditlink: https://github.com/elboletaire/Watimage/blob/master/examples/files/LICENSE
comments: false
modified: 2016-04-29
---

The `Watimage` class has been created for backwards compatibility purposes.

If you were using Watimage prior to its 2.0 version (with composer) you'll find
this helpful if you don't want to update your code or, maybe, you wanna use
Watimage *the old way* (not recommended at all).

> The `Watimage` class is just a bridge between the `Image` and the `Watermark`
class. Note that if you're not using composer you'll need to require **all** the
required Watimage files (that means all the exceptions too) to make everything
work as expected.

~~~php?start_inline=1
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
~~~

[← Go back to Watimage Classes Usage]({{ site.url }}/usage/classes)

{% include about-image/sweet-home.md %}
