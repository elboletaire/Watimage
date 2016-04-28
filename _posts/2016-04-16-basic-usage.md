---
layout: post
title: "Basic Watimage Usage"
modified: 2016-04-16 19:53:21 +0200
tags: [tutorial,question]
image:
  feature: peke-sepia.jpg
  credit: elboletaire
  creditlink: https://github.com/elboletaire/Watimage/blob/master/examples/files/LICENSE
comments:
share:
---

After you [configured Watimage](/usage/setup) you're ready to start using it and
here's an easy usage introduction.

The common use scenario usually is:

- User uploads an image.
- Store the uploaded file on the server.
- Modify it (resizing, applying a watermark or whatever).
- Save the resulting image in a new file to preserve to original, so we can
  re-use it in the future.

Let's see how to do this —remember that I'm considering that you already have
loaded composer's autoloader.

~~~php
use Elboletaire\Watimage\Image;

$original = '/path/for/your/original/image-file.jpeg';
$resized = '/path/for/your/resized/image-file.jpeg';

// I'm using move_uploaded_file just for comprehension purposes. Use here
// whatever upload strategy you usually use.
if (move_uploaded_file($_FILES['image']['tmp_name'], $original)) {
  $image = new Image($original);

  $image
    // Reduce it to 800x600 (maintaining aspect ratio)
    ->resize('reduce', 800, 600)
    ->generate($resized)
  ;

  // Let's say we've finished
  return true;
}
~~~

Easy, right?

Let's see now how can you add a Watermark:

~~~php
use Elboletaire\Watimage\Image;
use Elboletaire\Watimage\Watermark;

$original = '/path/for/your/original/image-file.jpeg';
$resized = '/path/for/your/resized/image-file.jpeg';
$watermark = '/path/to/your/watermark.png';

if (move_uploaded_file($_FILES['image']['tmp_name'], $original)) {
  $image = new Image($original);
  $watermark = new Watermark($watermark);

  $image
    ->resize('reduce', 800, 600)
  ;

  $watermark
    ->apply($image)
    // apply method returns the Image instance,
    // that's why we can directly generate it
    ->generate($resized)
  ;

  return true;
}
~~~

Watimage guesses the file type from its mime type. In case the mime type cannot
be detected, will guess the file type from its extension.

For that reason you cannot use `load` to load a non-uploaded file
[as temaqueja tried](https://github.com/elboletaire/Watimage/issues/2).

But there's a workaround for this.

Since version 2.0.5 you have two new methods, `fromString` and `toString`, that
can help you handle situations like this.

Particulary the `fromString` method is the one that we want here:

~~~php
use Elboletaire\Watimage\Image;

$image = new Image();
$image->fromString(file_get_contents($_FILES['image']['tmp_name']));
// now do whatever you want with the image
$image
  ->vignette()
  ->contrast(-5)
  ->generate('output.jpg')
;
~~~

{% include about-image/peke-sepia.md %}
