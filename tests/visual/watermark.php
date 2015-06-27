<?php
/************************
 *** APPLY WATERMARKS ***
 ************************/

error_reporting(E_ALL);
ini_set('display_errors', '1');

date_default_timezone_set('UTC');

require_once '../../src/watimage.php';
use Elboletaire\Watimage\Watimage;

function applyWatermark($image, $watermark, $output_image)
{
    $wm = new Watimage();
    $wm->setImage(array('file' => "files/{$image}", 'quality' => 90)); // file to use and export quality
    $wm->setWatermark(array('file' => "files/{$watermark}", 'position' => 'center')); // watermark to use and its position
    $wm->applyWatermark(); // apply watermark to the canvas
    if (!$wm->generate("results/{$output_image}")) {
        // handle errors...
        print_r($wm->errors);
    }
}

// Watermark a jpg file with a png
applyWatermark('image.jpg', 'watermark.png', 'watermark_jpg_with_png.jpg');
// Watermark a jpg file with a gif
applyWatermark('image.jpg', 'watermark.gif', 'watermark_jpg_with_gif.jpg');

// Watermark a png file with a png
applyWatermark('image.png', 'watermark.png', 'watermark_png_with_png.png');
// Watermark a png file with a gif
applyWatermark('image.png', 'watermark.gif', 'watermark_png_with_gif.png');

// Watermark a gif file with a png
applyWatermark('image.gif', 'watermark.png', 'watermark_gif_with_png.gif');
// Watermark a gif file with a gif
applyWatermark('image.gif', 'watermark.gif', 'watermark_gif_with_gif.gif');

echo "All images have been watermarked.\n";
