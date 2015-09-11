<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('UTC');

$current_path = dirname(__FILE__) . DIRECTORY_SEPARATOR;
$files_path = $current_path . 'files' . DIRECTORY_SEPARATOR;
$output_path = $current_path . 'output' . DIRECTORY_SEPARATOR;
$image = $files_path . 'test.png';
$watermark = $files_path . 'watermark.png';

require realpath($current_path . '../vendor/autoload.php');

use Elboletaire\Watimage\Watimage;

/************************
 *** APPLY WATERMARKS ***
 ************************/
$wm = new Watimage();
$wm->setImage(array('file' => $image, 'quality' => 70)); // file to use and export quality
$wm->setWatermark(array('file' => $watermark, 'position' => 'top right')); // watermark to use and its position
$wm->applyWatermark(); // apply watermark to the canvas
if ( !$wm->generate($output_path . 'test1.png') ) {
	// handle errors...
	print_r($wm->errors);
}

/*********************
 *** RESIZE IMAGES ***
 *********************/
$wm = new Watimage($image);
// allowed types: resize, resizecrop, resizemin, crop and reduce
$wm->resize(array('type' => 'resizecrop', 'size' => array(400, 200)));
if ( !$wm->generate($output_path . 'test2.png') ) {
	// handle errors...
	print_r($wm->errors);
}


/*********************
 *** ROTATE IMAGES ***
 *********************/
$wm = new Watimage($image);
$wm->rotate(90);
if ( !$wm->generate($output_path . 'test3.png') ) {
	// handle errors...
	print_r($wm->errors);
}

/**********************************
 *** EXPORTING TO OTHER FORMATS ***
 **********************************/
$wm = new Watimage($image);
if ( !$wm->generate($output_path . 'test4.jpg', 'image/jpeg') ) {
	// handle errors...
	print_r($wm->errors);
}

/*******************
 *** FLIP IMAGES ***
 *******************/
$wm = new Watimage($image);
$wm->flip('vertical'); // or "horizontal"
if ( !$wm->generate($output_path . 'test5.png') ) {
	// handle errors...
	print_r($wm->errors);
}


/***********************
 *** CROPPING IMAGES ***
 ***********************/
// Usefull for cropping plugins like https://github.com/tapmodo/Jcrop
$wm = new Watimage($image);
$wm->crop(array( // values from the cropper
	'width' => 500, // the cropped width
	'height' => 500, // "     "	   height
	'x' => 50,
	'y' => 80
));
if ( !$wm->generate($output_path . 'test6.png') ) {
	// handle errors...
	print_r($wm->errors);
}


/***************************
 *** EVERYTHING TOGETHER ***
 ***************************/

$wm = new Watimage();

// Set the image
$wm->setImage($image); // you can also set the quality with setImage, you only need to change it with an array: array('file' => $image, 'quality' => 70)

// Set the export quality
$wm->setQuality(80);

// Set a watermark
$wm->setWatermark(array(
	'file' => $watermark,  // the watermark file
	'position' => 'center center', // the watermark position works like CSS backgrounds positioning
	'margin' => array('x' => -20, 'y' => 10), // you can set some 'margins' to the watermark for better positioning
	'size' => 'full' // you can set the size of the watermark using a percentage or the word "full" for getting a full width/height watermark
));

// Resize the image
$wm->resize(array('type' => 'resize', 'size' => 400));

// Flip it
$wm->flip('horizontal');

// Now rotate it 30deg
$wm->rotate(30);

// It's time to apply the watermark
$wm->applyWatermark();

// Export the file
if ( !$wm->generate($output_path . 'test7.png') ) {
	// handle errors...
	print_r($wm->errors);
}


echo "All examples are now available under the 'output' folder\n";
// END OF FILE
