<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('UTC');

$current_path = dirname(__FILE__) . DIRECTORY_SEPARATOR;
$files_path = $current_path . 'files' . DIRECTORY_SEPARATOR;
$output_path = $current_path . 'output' . DIRECTORY_SEPARATOR;
$image_file = $files_path . 'test.png';
$watermark_file = $files_path . 'watermark.png';

require realpath($current_path . '../vendor/autoload.php');

use Elboletaire\Watimage\Image;
use Elboletaire\Watimage\Watermark;

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
    ->generate($output_path . 'watermark1-apply.png');

/**************************
 *** ALTERING WATERMARK ***
 **************************/
$image = new Image($image_file);
$watermark = new Watermark($watermark_file);
$watermark
    // Watermark extends Image, so we can use any method from there to
    // modify the watermark
    ->rotate(90)
    ->setPosition('bottom right')
    ->apply($image)
    ->generate($output_path . 'watermark2-rotate.png');

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

// generate the resulting image
$image->generate($output_path . 'watermark3-all-together.png');

echo "All examples are now available under the 'output' folder\n";
// END OF FILE
