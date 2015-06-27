<?php
/*********************
 *** ROTATE IMAGES ***
 *********************/

error_reporting(E_ALL);
ini_set('display_errors', '1');

date_default_timezone_set('UTC');

require_once '../../src/watimage.php';
use Elboletaire\Watimage\Watimage;

function rotateImage($image, $deg, $output_image)
{
    $wm = new Watimage();
    $wm->setImage(array('file' => "files/{$image}", 'quality' => 90)); // file to use and export quality
    $wm->rotate($deg);
    if (!$wm->generate("results/{$output_image}")) {
        // handle errors...
        print_r($wm->errors);
    }
}

rotateImage('image.jpg', 45, 'rotate_jpg_45.jpg');
rotateImage('image.jpg', 90, 'rotate_jpg_90.jpg');
rotateImage('image.jpg', 180, 'rotate_jpg_180.jpg');
rotateImage('image.jpg', 270, 'rotate_jpg_270.jpg');

############################
### Check transparencies ###
############################

// Rotate png 45 deg
rotateImage('image.png', 45, 'rotate_png_45.png');

// Rotate gif 45 deg
rotateImage('image.gif', 45, 'rotate_gif_45.gif');
rotateImage('image.gif', 90, 'rotate_gif_90.gif');

echo "All images have been rotated.\n";
