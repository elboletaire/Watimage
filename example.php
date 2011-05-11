<?php 
error_reporting(E_ALL);
ini_set('display_errors', '1');
require 'watermark.php';

// Applying watermarks
$wm = new Watermark();
$wm->setImage(array('file' => 'test.png', 'quality' => 50));
$wm->setWatermark(array('file' => 'watermark.png', 'position' => 'top right'));
$wm->applyWatermark();
if ( !$wm->generate('test1.png') ) {
	// handle error...
	print_r($wm->errors);
}

// Resize
$wm = new Watermark('test.png');
$wm->resize(array('type' => 'resizecrop', 'size' => array(400, 200)));
if ( !$wm->generate('test2.png') ) {
	// handle error...
	print_r($wm->errors);
}


// Rotate
$wm = new Watermark('test.png');
$wm->rotate(90);
if ( !$wm->generate('test3.png') ) {
	// handle error...
	print_r($wm->errors);
}

// Exporting to other format
$wm = new Watermark('test.png');
if ( !$wm->generate('test4.jpg') ) {
	// handle error...
	print_r($wm->errors);
}

// Flip
$wm = new Watermark('test.png');
$wm->flip('vertical');
if ( !$wm->generate('test5.png') ) {
	// handle error...
	print_r($wm->errors);
}

// All together
$wm = new Watermark('test.png', 'watermark.png');
$wm->resize(array('type' => 'resizecrop', 'size' => 400));
$wm->flip('horizontal');
$wm->rotate(90);
$wm->applyWatermark();
if ( !$wm->generate('test6.png') ) {
	// handle error...
	print_r($wm->errors);
}