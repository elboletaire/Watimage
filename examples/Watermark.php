<?php
use Elboletaire\Watimage\Image;
use Elboletaire\Watimage\Watermark;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php';

$image_file = FILES . 'test.png';
$watermark_file = FILES . 'watermark.png';

/***********************
 *** APPLY WATERMARK ***
 ***********************/
$image = new Image($image_file);
$watermark = new Watermark($watermark_file);

$watermark
    ->setPosition('bottom right')
    ->apply($image)
    // apply method returns the Image instance (not the Watermark)
    // that's why we can directly generate
    ->generate(OUTPUT . 'watermark1-apply.png');

/**************************
 *** ALTERING WATERMARK ***
 **************************/
$image = new Image($image_file);
$watermark = new Watermark($watermark_file);
$watermark
    // Watermark extends Image, so we can use any method from there to
    // modify the watermark
    ->rotate(90)
    ->negate()
    ->setPosition('bottom right')
    ->apply($image)
    ->generate(OUTPUT . 'watermark2-rotate.png');

/***************************
 *** EVERYTHING TOGETHER ***
 ***************************/
$image = new Image($image_file);
$watermark = new Watermark($watermark_file);
$watermark
    // let's rotate the watermark just 45 deg.
   ->rotate(45)
   ->setPosition('bottom right')
   // change its size
   ->setSize(250, 90)
   // set a margin
   ->setMargin(-20)
   // and apply to the image
   ->apply($image)
;

// Apply a second watermark
$watermark
    // but this one top left
    ->setPosition('top left')
    // and adjust its margin to the new position
    ->setMargin(20)
    // then apply it
    ->apply($image)
;

$image
    // Then rotate the image
    ->rotate(45)
     // and resize it
    ->resize('min', 400)
;

$watermark
    // Rotate the watermark
    ->rotate(-45)
    // resize it
    ->resize('resize', 400)
    // change its position
    ->setPosition('centered')
    // and apply it
    ->apply($image);
;

// Create a new Watermark object to watermark the resulting file
$watermark = new Watermark($watermark_file);
$watermark->setPosition('bottom right')->apply($image);

// generate the resulting image
$image->generate(OUTPUT . 'watermark3-all-together.png');

echo "All examples are now available under the 'output' folder\n";
// END OF FILE
