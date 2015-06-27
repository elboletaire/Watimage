<?php
/*******************
 *** FLIP IMAGES ***
 *******************/

error_reporting(E_ALL);
ini_set('display_errors', '1');

date_default_timezone_set('UTC');

require_once '../../src/watimage.php';
use Elboletaire\Watimage\Watimage;

function flipImage($image, $mode, $output_image)
{
    $wm = new Watimage();
    $wm->setImage(array('file' => "files/{$image}", 'quality' => 90)); // file to use and export quality
    $wm->flip($mode);
    if (!$wm->generate("results/{$output_image}")) {
        // handle errors...
        print_r($wm->errors);
    }
}

// Flip horizontally
flipImage('image.jpg', 'horizontal', 'flip_jpg_horizontally.jpg');
// Flip vertically
flipImage('image.jpg', 'vertical', 'flip_jpg_vertically.jpg');
// Flip on both axis
flipImage('image.jpg', 'both', 'flip_jpg_both.jpg');

############################
### Check transparencies ###
############################

// Flip a png image
flipImage('image.png', 'horizontal', 'flip_png_horizontally.png');
// Flip a gif image. This does not maintain transparent background
// due to a php.net bug. See https://github.com/elboletaire/Watimage/issues/16
flipImage('image.gif', 'horizontal', 'flip_gif_horizontally.gif');

echo "All images have been flipped.\n";
