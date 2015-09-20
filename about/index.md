---
layout: page
title: About Watimage
image:
  feature: peke-sepia.jpg
  credit: elboletaire
  creditlink: https://github.com/elboletaire/Watimage/blob/master/examples/files/LICENSE
comments: false
modified: 2015-09-20
---

Watimage started in 2011 as a CakePHP component that just crop and rotate images.
Currently is a vendor class with multiple image manipulation methods that you can
add to any PHP project.

What Watimage brings to the table:
----------------------------------

- Really easy to use image manipulation PHP classes bringing you a lot of features:
  * Resize and/or crop images.
  * Rotate images.
  * Flip images.
  * Apply multiple effects to your images (blur, negate, grayscale, sepia,
    vignette, pixelate, emboss, edge detection, colorize, brightness,
    contrast...).
  * Merge images / apply watermarks.
  * JPEG image auto-orientation from EXIF information.
  * And more! Check out the [usage documentation](/usage) or the [API](/api) for
    more information.
- Backwards compatibility with old Watimage class.
- [Method chaining](https://en.wikipedia.org/wiki/Method_chaining).
- Testing.

<div markdown="0">
  <a href="{{ site.url }}/setup/" class="btn btn-info">Watimage Setup</a>
  <a href="https://github.com/elboletaire/Watimage" class="btn btn-success">Download Watimage</a>
</div>

{% include about-image-header.md %}

~~~php
$image = new Image('peke.jpg');
$image
    ->sepia(60)
    ->vignette(.3)
    ->generate()
;
~~~

{% include about-image-footer.md %}
