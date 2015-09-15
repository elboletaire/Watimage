<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('UTC');

$current_path = dirname(__FILE__) . DIRECTORY_SEPARATOR;
$files_path = $current_path . 'files' . DIRECTORY_SEPARATOR;
$output_path = $current_path . 'output' . DIRECTORY_SEPARATOR;
$image_file = $files_path . 'peke.jpg';
$orientate = $files_path . 'tripi.jpg';

require realpath($current_path . '../vendor/autoload.php');

use Elboletaire\Watimage\Image;

/*********************
 *** RESIZE IMAGES ***
 *********************/
$image = new Image($image_file);
// allowed types: resize [or classic], reduce, min [or resizemin], crop and resizecrop
$image->resize('resizecrop', 400, 200)
    ->generate($output_path . 'image1-resizecrop.jpg');

/*********************
 *** ROTATE IMAGES ***
 *********************/
$image = new Image($image_file);
// check out Normalize::color to see all the allowed possibilities about how
// to set colors. Angle must be specified in degrees (positive is clockwise)
$image->rotate(90, '#fff')
    ->generate($output_path . 'image2-rotate.jpg');
// Images are automatically orientated by default when using the constructor
// instead of load. You can skip it and you can also auto-orientate images later:

// disable auto-orientate on load
$image = new Image($orientate, false);
// we can later use the autoOrientate method if we not did it previously:
$image->autoOrientate()
    ->generate($output_path . 'image3-auto-orientate.jpg');

/**********************************
 *** EXPORTING TO OTHER FORMATS ***
 **********************************/
$image = new Image($image_file);
$image->generate($output_path . 'image4-formats.png', 'image/png');

/*******************
 *** FLIP IMAGES ***
 *******************/
$image = new Image($image_file);
// vertical [or y, or v], horizontal [or x, or h]
// check out Normalize::flip to see all the allowed possibilities
$image->flip('vertical')
    ->generate($output_path . 'image5-flip.jpg');

/***********************
 *** CROPPING IMAGES ***
 ***********************/
// Usefull for cropping plugins like https://github.com/tapmodo/Jcrop
$image = new Image($image_file);
// Values from the cropper
// check out Normalize::crop to see all the allowed possibilities
$image->crop([
        'width'  => 500, // the cropped width
        'height' => 500, //  "     "    height
        'x'      => 50,
        'y'      => 80
    ])
    ->generate($output_path . 'image6-crop.jpg');

/************************
 *** APPLYING FILTERS ***
 ************************/

$image = new Image($image_file);
$image
    // ->edgeDetection()
    ->blur()
    ->sepia()
    ->pixelate(3, true)
    // ->brightness(10)
    // ->contrast(10)
    // ->colorize('#f00')
    // ->emboss()
    // ->meanRemove()
    // ->negate()
    ->vignette()
    ->generate($output_path . 'image7-effects.jpg');

/********************************
 *** DIRECTLY TREATING IMAGES ***
 ********************************/

$image = new Image($image_file);
// Get the resource image
$resource = $image->getImage();
// Add a string as a note in the top left side
$color = imagecolorallocate($resource, 0, 0, 0);
imagestring($resource, 5, 10, 10, "My cat, peke", $color);
// Return the image resource to the Image instance
$image->setImage($resource)
    // and save
    ->generate($output_path . 'image8-treating-images.jpg');

echo "All examples are now available under the 'output' folder\n";
// END OF FILE
