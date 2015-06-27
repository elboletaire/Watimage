<?php
/***********************
 *** RESIZING IMAGES ***
 ***********************/

error_reporting(E_ALL);
ini_set('display_errors', '1');

date_default_timezone_set('UTC');

require_once '../../src/watimage.php';
use Elboletaire\Watimage\Watimage;

function resizeImage($image, $crop_type, $output_image)
{
    $wm = new Watimage();
    $wm->setImage(array('file' => "files/{$image}", 'quality' => 90)); // file to use and export quality
    $wm->resize(array('type' => $crop_type, 'size' => 400));
    if (!$wm->generate("results/{$output_image}")) {
        // handle errors...
        print_r($wm->errors);
    }
}

function resizeAndWatermarkImage($image, $output_image)
{
    $wm = new Watimage();
    $wm->setImage(array('file' => "files/{$image}", 'quality' => 90)); // file to use and export quality
    $wm->setWatermark(array('file' => "files/watermark.png", 'position' => 'center')); // watermark to use and its position
    $wm->resize(array('type' => 'resizemin', 'size' => 400));
    $wm->applyWatermark(); // apply watermark to the canvas
    if (!$wm->generate("results/{$output_image}")) {
        // handle errors...
        print_r($wm->errors);
    }
}

// Reizing a jpg file with resizecrop
resizeImage('image.jpg', 'resizecrop', 'resize_jpg_with_resizecrop.jpg');
// Reizing a jpg file with resizemin
resizeImage('image.jpg', 'resizemin', 'resize_jpg_with_resizemin.jpg');
// Reizing a jpg file with resize
resizeImage('image.jpg', 'resize', 'resize_jpg_with_resize.jpg');
// Reizing a jpg file with reduce
resizeImage('image.jpg', 'reduce', 'resize_jpg_with_reduce.jpg');
// Reizing a jpg file with crop
resizeImage('image.jpg', 'crop', 'resize_jpg_with_crop.jpg');

// Resizing a png file
resizeImage('image.png', 'resizemin', 'resize_png.png');
// Resizing a gif file
resizeImage('image.gif', 'resizemin', 'resize_gif.gif');

echo "Images have been rotated.\n";

// Resize a png image and apply watermark
resizeAndWatermarkImage('image.png', 'resize_and_watermark_png.png');
// Resize a gif image and apply watermark
resizeAndWatermarkImage('image.gif', 'resize_and_watermark_gif.gif');

echo "Resizing and applying watermarks ended as well.\n";
