<?php
namespace Elboletaire\Watimage;

use Exception;
use Elboletaire\Watimage\Exception\ExtensionNotLoadedException;
use Elboletaire\Watimage\Exception\FileNotExistException;
use Elboletaire\Watimage\Exception\InvalidArgumentException;
use Elboletaire\Watimage\Exception\InvalidExtensionException;
use Elboletaire\Watimage\Exception\InvalidMimeException;

class Image
{
    const COLOR_TRANSPARENT = -1;

    protected $filename, $image, $metadata = [], $width, $height;

    /**
     * Image export quality for gif and jpg files.
     *
     * You can set it with setQuality or setImage methods.
     *
     * @var integer
     */
    private $quality = 80;

    /**
     * Image compression value for png files.
     *
     * You can set it with setCompression method.
     *
     * @var integer
     */
    private $compression = 9;

    public function __construct($file = null)
    {
        if (!extension_loaded('gd')) {
            throw new ExtensionNotLoadedException("GD");
        }

        if (!empty($file)) {
            $this->load($file);
        }

        return $this;
    }

    public function __destruct()
    {
        $this->destroy();
    }

    /**
     * Creates a resource image.
     *
     * This method was using imagecreatefromstring but I decided to switch after
     * reading this: https://thenewphalls.wordpress.com/2012/12/27/imagecreatefromstring-vs-imagecreatefromformat
     *
     * @param  string $filename Image file path/name.
     * @param  string $format   Image format (gif, png or jpeg).
     * @return resource
     * @throws InvalidMimeException
     */
    public function createResourceImage($filename, $format)
    {
        switch ($format) {
            case 'gif':
                $image = imagecreatefromgif($filename);
                break;

            case 'png':
                $image = imagecreatefrompng($filename);
                break;

            case 'jpeg':
                $image =  imagecreatefromjpeg($filename);
                break;

            default:
                throw new InvalidMimeException($this->metadata['mime']);
        }

        // Handle transparencies
        imagesavealpha($image, true);
        imagealphablending($image, true);

        return $image;
    }

    /**
     * Cleans up everything to start again.
     *
     * @return Image
     */
    public function destroy()
    {
        if (!is_null($this->image) && get_resource_type($this->image) == 'gd') {
            imagedestroy($this->image);
        }
        $this->metadata = [];
        $this->filename = $this->width = $this->height = null;

        return $this;
    }

    /**
     * Outputs or saves the image.
     *
     * @param  string $filename Filename to be saved. Empty to directly print on screen.
     * @param string $output Use it to overwrite the output format when no $filename is passed.
     * @return void
     * @throws InvalidArgumentException If output format is not recognised.
     */
    public function generate($filename = null, $output = null)
    {
        if (!empty($filename)) {
            $output = $this->getMimeFromExtension($filename);
        } else {
            $output = $output ?: $this->metadata['mime'];
            header("Content-type: {$output}");
        }

        switch ($output) {
            case 'image/gif':
                imagegif($this->image, $filename, $this->quality);
                break;
            case 'image/png':
                imagepng($this->image, $filename, $this->compression);
                break;
            case 'image/jpeg':
                imageinterlace($this->image, true);
                imagejpeg($this->image, $filename, $this->quality);
                break;
            default:
                throw new InvalidArgumentException("Invalid output format \"%s\"", $output);
        }

        return $this;
    }

    /**
     * Similar to generate, except that passing an empty $filename here will
     * overwrite the original file.
     *
     * @param  string $filename Filename to be saved. Empty to overwrite original file.
     * @return bool
     */
    public function save($filename = null)
    {
        $filename = $filename ?: $this->filename;

        return $this->generate($filename);
    }

    /**
     *  Loads image and (optionally) its options.
     *
     *  @param mixed $filename Filename string or array containing both filename and quality
     *  @return Watimage
     *  @throws FileNotExistException
     *  @throws InvalidArgumentException
     */
    public function load($filename)
    {
        if (is_array($filename)) {
            if (isset($filename['quality'])) {
                $this->setQuality($filename['quality']);
            }
            $filename = $filename['file'];
        }

        if (empty($filename)) {
            throw new InvalidArgumentException("Image file has not been set.");
        }

        if (!file_exists($filename)) {
            throw new FileNotExistException($filename);
        }

        $this->destroy();

        $this->filename = $filename;
        $this->getMetadataForImage();
        $this->image = $this->createResourceImage($filename, $this->metadata['format']);

        return $this;
    }

    /**
     * Rotates an image clockwise.
     *
     * @param  int    $degrees Rotation angle in degrees.
     * @param  mixed  $bgcolor Background to be used for the background, transparent by default.
     * @return Image
     */
    public function rotate($degrees, $bgcolor = self::COLOR_TRANSPARENT)
    {
        $color = Normalize::color($bgcolor);
        $bgcolor = imagecolorallocatealpha($this->image, $color['r'], $color['g'], $color['b'], $color['a']);

        $this->image = imagerotate($this->image, $degrees * -1, $bgcolor);

        $this->updateSize();

        return $this;
    }

    /**
     * All in one method for all resize methods.
     *
     * @param  string $type   Type of resize: resize, resizemin, reduce, crop & resizecrop.
     * @param  mixed  $width  Can be just max width or an array containing both params.
     * @param  int    $height Max height.
     * @return Image
     */
    public function resize($type, $width, $height = null)
    {
        $types = [
            'classic'    => 'classicResize',
            'resize'     => 'classicResize',
            'reduce'     => 'reduce',
            'resizemin'  => 'resizeMin',
            'min'        => 'resizeMin',
            'crop'       => 'classicCrop',
            'resizecrop' => 'resizeCrop'
        ];

        $lowertype = strtolower($type);

        if (!array_key_exists($lowertype, $types)) {
            throw new InvalidArgumentException("Invalid resize type %s.", $type);
        }

        return $this->$types[$lowertype]($width, $height);
    }

    /**
     * Resizes maintaining aspect ratio.
     *
     * Maintains the aspect ratio of the image and makes sure that it fits
     * within the max width and max height (thus some side will be smaller).
     *
     * TODO: Needs to be checked.
     *
     * @param  mixed $width  Can be just max width or an array containing both params.
     * @param  int   $height Max height.
     * @return Image
     */
    public function classicResize($width, $height = null)
    {
        list($width, $height) = Normalize::resize($width, $height);

        if ($this->width == $width && $this->height == $width) {
            return $this;
        }

        if ($this->width > $this->height) {
            $height = ($this->height * $width) / $this->width;
        } elseif ($this->width < $this->height) {
            $height = $this->height;
            $width = ($this->width * $height) / $this->height;
        } elseif ($this->width == $this->height) {
            $width = $height = $width;
        }

        $this->image = $this->imagecopy($width, $height);

        $this->updateSize();

        return $this;
    }

    /**
     * Generates an image which do not exceed the specified boundaries
     * ($width and $height) under any circumstances, while maintaining the
     * original aspect ratio.
     *
     * TODO: Check differences with resize.
     *
     * @param  mixed $width  Can be just max width or an array containing both params.
     * @param  int   $height Max height.
     * @return Image
     */
    public function resizeMin($width, $height = null)
    {
        list($width, $height) = Normalize::resize($width, $height);

        // image will be left "as is", unless it is eligible for resizing
        $ratio_resize = 1;

        // `true` when source image is smaller than both the requested boundaries
        $needs_resize = !($this->width < $width && $this->height < $height);

        if ($needs_resize) {
            $ratio_x = $this->width / $width;
            $ratio_y = $this->height / $height;

            // we need to choose one of the most convenient ratios (among
            // these two) for our resize. The biggest one, it is.
            $ratio_resize = $ratio_x > $ratio_y ? $ratio_x : $ratio_y;
        }

        if ($ratio_resize === 1) {
            return $this;
        }

        $width  = $this->width / $ratio_resize;
        $height = $this->height / $ratio_resize;

        $this->image = $this->imagecopy($width, $height);

        $this->updateSize();

        return $this;
    }

    /**
     * A straight centered crop.
     *
     * @param  mixed $width  Can be just max width or an array containing both params.
     * @param  int   $height Max height.
     * @return Image
     */
    public function classicCrop($width, $height = null)
    {
        list($width, $height) = Normalize::resize($width, $height);

        $start_y = ($this->height - $height) / 2;
        $start_x = ($this->width - $width) / 2;

        $this->image = $this->imagecopy($width, $height, $start_x, $start_y, $width, $height);

        $this->updateSize();

        return $this;
    }

    /**
     * Resizes to max, then crops to center.
     *
     * @param  mixed $width  Can be just max width or an array containing both params.
     * @param  int   $height Max height.
     * @return Image
     */
    public function resizeCrop($width, $height = null)
    {
        list($width, $height) = Normalize::resize($width, $height);

        $ratio_x = $width / $this->width;
        $ratio_y = $height / $this->height;
        $src_w = $this->width;
        $src_h = $this->height;

        if ($ratio_x < $ratio_y) {
            $start_x = round(($this->width - ($width / $ratio_y)) / 2);
            $start_y = 0;
            $src_w = round($width / $ratio_y);
        } else {
            $start_x = 0;
            $start_y = round(($this->height - ($height / $ratio_x)) / 2);
            $src_h = round($height / $ratio_x);
        }

        $this->image = $this->imagecopy($width, $height, $start_x, $start_y, $src_w, $src_h);

        $this->updateSize();

        return $this;
    }

    /**
     * Resizes maintaining aspect ratio but not exceeding width / height.
     *
     * @param  mixed $width  Can be just max width or an array containing both params.
     * @param  int   $height Max height.
     * @return Image
     */
    public function reduce($width, $height = null)
    {
        list($width, $height) = Normalize::resize($width, $height);

        if ($this->width < $width && $this->height < $height) {
            return $this;
        }

        $ratio_x = $this->width / $width;
        $ratio_y = $this->height / $height;

        $ratio = $ratio_x > $ratio_y ? $ratio_x : $ratio_y;

        // Getting the new image size
        $width = (int)($this->width / $ratio);
        $height = (int)($this->height / $ratio);

        $this->image = $this->imagecopy($width, $height);

        return $this;
    }

    /**
     * Flips an image. If PHP version is 5.5.0 or greater will use
     * proper php gd imageflip method. Otherwise will fallback to
     * convenienceflip.
     *
     * @param  string $type Type of flip, can be any of: horizontal, vertical, both
     * @return Image
     */
    public function flip($type = 'horizontal')
    {
        if (version_compare(PHP_VERSION, '5.5.0', '<')) {
            return $this->convenienceFlip($type);
        }

        imageflip($this->image, Normalize::flip($type));

        return $this;
    }

    /**
     * Flip method for PHP versions < 5.5.0
     *
     * @param  string $type Type of flip, can be any of: horizontal, vertical, both
     * @return Image
     */
    public function convenienceFlip($type = 'horizontal')
    {
        $type = Normalize::flip($type);

        $resampled = $this->imagecreate($this->width, $this->height);

        switch ($type) {
            case IMG_FLIP_VERTICAL:
                imagecopyresampled(
                    $resampled, $this->image,
                    0, 0, 0, ($this->height - 1),
                    $this->width, $this->height, $this->width, 0 - $this->height
                );
                break;
            case IMG_FLIP_HORIZONTAL:
                imagecopyresampled(
                    $resampled, $this->image,
                    0, 0, ($this->width - 1), 0,
                    $this->width, $this->height, 0 - $this->width, $this->height
                );
                break;
            // same as $this->rotate(180)
            case IMG_FLIP_BOTH:
                imagecopyresampled(
                    $resampled, $this->image,
                    0, 0, ($this->width - 1), ($this->height - 1),
                    $this->width, $this->height, 0 - $this->width, 0 - $this->height
                );
                break;
        }

        $this->image = $resampled;

        return $this;
    }

    /**
     * Creates an empty canvas.
     *
     * If no arguments are passed and we have previously created an
     * image it will create a new canvas with the previous canvas size.
     * Due to this, you can use this method to "empty" the current canvas.
     *
     * @param  int $width  Canvas width.
     * @param  int $height Canvas height.
     * @return Image
     */
    public function create($width = null, $height = null)
    {
        if (!isset($width)) {
            if (!isset($this->width, $this->height)) {
                throw new InvalidArgumentException("You must set the canvas size.");
            }
            $width = $this->width;
            $height = $this->height;
        }

        if (!isset($height)) {
            $height = $width;
        }

        $this->image = $this->imagecreate($width, $height);
        $this->metadata['exif'] = null;
        $this->updateSize();

        return $this;
    }

    /**
     * Creates an empty canvas.
     *
     * @param  int $width  Canvas width.
     * @param  int $height Canvas height.
     * @return resource    Image resource with the canvas.
     */
    protected function imagecreate($width, $height)
    {
        $image = imagecreatetruecolor($width, $height);
        // Required for transparencies in png and gif files
        imagealphablending($image, false);
        imagesavealpha($image, true);
        // Required for transparencies while cropping gif images
        imagecolortransparent($image, imagecolorallocatealpha($image, 0, 0, 0, 127));

        return $image;
    }

    /**
     * Helper method for all resize methods and others that require
     * imagecopyresampled method.
     *
     * @param  int  $dst_w New width.
     * @param  int  $dst_h New height.
     * @param  int  $src_x Starting source point X.
     * @param  int  $src_y Starting source point Y.
     * @return resource    GD image resource containing the resized image.
     */
    protected function imagecopy($dst_w, $dst_h, $src_x = 0, $src_y = 0, $src_w = false, $src_h = false)
    {
        $dest_image = $this->imagecreate($dst_w, $dst_h);

        if ($src_w === false) {
            $src_w = $this->width;
        }

        if ($src_h === false) {
            $src_h = $this->height;
        }

        imagecopyresampled(
            $dest_image, $this->image,
            0, 0, $src_x, $src_y,
            $dst_w, $dst_h, $src_w, $src_h
        );

        return $dest_image;
    }

    /**
     * Fills current canvas with specified color.
     *
     * It works with newly created canvas. If you want to overwrite the current
     * canvas you must first call `create` method to empty current canvas.
     *
     * @param  mixed $color The color. Check out getColorArray for allowed formats.
     * @return Image
     */
    public function fill($color = '#fff')
    {
        $color = Normalize::color($color);
        $color = imagecolorallocatealpha($this->image, $color['r'], $color['g'], $color['b'], $color['a']);
        imagefill($this->image, 0, 0, $color);

        return $this;
    }

    /**
     * Crops an image based on specified coords and size.
     *
     * You can pass arguments one by one or an array passing arguments
     * however you like.
     *
     * @param  int $x      X position where start to crop.
     * @param  int $y      Y position where start to crop.
     * @param  int $width  New width of the image.
     * @param  int $height New height of the image.
     * @return Image
     */
    public function crop($x, $y = null, $width = null, $height = null)
    {
        list($x, $y, $width, $height) = Normalize::crop($x, $y, $width, $height);

        $crop = $this->imagecreate($width, $height);

        imagecopyresampled(
            $crop, $this->image,
            0, 0, $x, $y,
            $width, $height, $width, $height
        );

        $this->image = $crop;

        $this->updateSize();

        return $this;
    }

    /**
     * Blurs the image.
     *
     * @param  mixed   $type   Type of blur to be used between: gaussian, selective.
     * @param  integer $passes Number of times to apply the filter.
     * @return Image
     * @throws InvalidArgumentException
     */
    public function blur($type = null, $passes = 1)
    {
        switch (strtolower($type)) {
            case IMG_FILTER_GAUSSIAN_BLUR:
            case 'selective':
                $type = IMG_FILTER_GAUSSIAN_BLUR;
                break;

            case null: // gaussian by default (just because I like it more)
            case 'gaussian':
            case IMG_FILTER_SELECTIVE_BLUR:
                $type = IMG_FILTER_SELECTIVE_BLUR;
                break;

            default:
                throw new InvalidArgumentException("Incorrect blur type \"%s\"", $type);
        }

        for ($i = 0; $i < $this->fitInRange($passes, 1); $i++) {
            imagefilter($this->image, $type);
        }

        return $this;
    }

    /**
     * Changes the brightness of the image.
     *
     * @param  integer $level Brightness value; range between -255 & 255.
     * @return Image
     */
    public function brightness($level)
    {
        imagefilter(
            $this->image,
            IMG_FILTER_BRIGHTNESS,
            $this->fitInRange($level, -255, 255)
        );

        return $this;
    }

    /**
     * Like grayscale, except you can specify the color.
     *
     * @param  mixed  $color Color in any format accepted by Normalize::color
     * @return Image
     */
    public function colorize($color)
    {
        $color = Normalize::color($color);

        imagefilter(
            $this->image,
            IMG_FILTER_COLORIZE,
            $color['r'],
            $color['g'],
            $color['b'],
            $color['a']
        );

        return $this;
    }

    /**
     * Changes the contrast of the image.
     *
     * @param  integer $level Use for adjunting level of contrast (-100 to 100)
     * @return Image
     */
    public function contrast($level)
    {
        imagefilter(
            $this->image,
            IMG_FILTER_CONTRAST,
            $this->fitInRange($level, -100, 100)
        );

        return $this;
    }

    /**
     * Uses edge detection to highlight the edges in the image.
     *
     * @return Image
     */
    public function edgeDetection()
    {
        imagefilter($this->image, IMG_FILTER_EDGEDETECT);

        return $this;
    }

    /**
     * Embosses the image.
     *
     * @return Image
     */
    public function emboss()
    {
        imagefilter($this->image, IMG_FILTER_EMBOSS);

        return $this;
    }

    /**
     * Applies grayscale filter.
     *
     * @return Image
     */
    public function grayscale()
    {
        imagefilter($this->image, IMG_FILTER_GRAYSCALE);

        return $this;
    }

    /**
     * Uses mean removal to achieve a "sketchy" effect.
     *
     * @return Image
     */
    public function meanRemove()
    {
        imagefilter($this->image, IMG_FILTER_MEAN_REMOVAL);

        return $this;
    }

    /**
     * Reverses all colors of the image.
     *
     * @return Image
     */
    public function negate()
    {
        imagefilter($this->image, IMG_FILTER_NEGATE);

        return $this;
    }

    /**
     * Pixelates the image.
     *
     * @param  int     $block_size Block size in pixels.
     * @param  boolean $advanced   Set to true to enable advanced pixelation.
     * @return Image
     */
    public function pixelate($block_size = 3, $advanced = false)
    {
        imagefilter(
            $this->image,
            IMG_FILTER_PIXELATE,
            $this->fitInRange($block_size, 1),
            $advanced
        );

        return $this;
    }

    /**
     * A combination of various effects to achieve a sepia like effect.
     *
     * TODO: Create an additional class with instagram-like effects and move it there.
     *
     * @param  int   $alpha Defines the transparency of the effect: from 0 to 100
     * @return Image
     */
    public function sepia($alpha = 0)
    {
        return $this
            ->grayscale()
            ->contrast(-3)
            ->brightness(-15)
            ->colorize([
                'r' => 100,
                'g' => 70,
                'b' => 50,
                'a' => $this->fitInRange($alpha, 0, 100)
            ]
        );
    }

    /**
     * Makes the image smoother.
     *
     * @param  int   $level Level of smoothness, between -15 and 15.
     * @return Image
     */
    public function smooth($level)
    {
        imagefilter(
            $this->image,
            IMG_FILTER_SMOOTH,
            $this->fitInRange($level, -15, 15)
        );

        return $this;
    }

    /**
     * Adds a vignette to image.
     *
     * @param  float  $size  Size of the vignette, between 0 and 10. Low is sharper.
     * @param  float  $level Vignete transparency, between 0 and 1
     * @return Image
     * @link   http://php.net/manual/en/function.imagefilter.php#109809
     */
    public function vignette($size = 0.7, $level = 0.8)
    {
        for ($x = 0; $x < $this->width; ++$x) {
            for ($y = 0; $y < $this->height; ++$y) {
                $index = imagecolorat($this->image, $x, $y);
                $rgb = imagecolorsforindex($this->image, $index);

                $this->vignetteEffect($size, $level, $x, $y, $rgb);
                $color = imagecolorallocate($this->image, $rgb['red'], $rgb['green'], $rgb['blue']);

                imagesetpixel($this->image, $x, $y, $color);
            }
        }

        return $this;
    }

    /**
     * Sets quality for gif and jpg files.
     *
     * @param int $quality A value from 0 (zero quality) to 100 (max quality).
     * @return Image
     */
    public function setQuality($quality)
    {
        $this->quality = $quality;

        return $this;
    }

    /**
     * Sets compression for png files.
     *
     * @param int $compression A value from 0 (no compression, not recommended) to 9.
     * @return Image
     */
    public function setCompression($compression)
    {
        $this->compression = $compression;

        return $this;
    }

    /**
     * Allows you to set the current image resource.
     *
     * This is intented for use it in conjuntion with getImage.
     *
     * @param resource $image Image resource to be set.
     * @throws Exception      If given image is not a GD resource.
     */
    public function setImage($image)
    {
        if (!get_resource_type($image) == 'gd') {
            throw new Exception("Given image is not a GD image resource");
        }

        $this->image = $image;
        $this->updateSize();
    }

    /**
     * Returns image resource, so you can use it however you wan.
     *
     * @return resource
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Returns metadata for current image.
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Gets metadata information from given $filename.
     *
     * @param  string $filename File path
     * @return array
     */
    public static function getMetadataFromFile($filename)
    {
        $info = getimagesize($filename);

        $metadata = [
            'width'  => $info[0],
            'height' => $info[1],
            'mime'   => $info['mime'],
            'format' => preg_replace('@^image/@', '', $info['mime']),
            'exif'   => null // set later, if necessary
        ];

        if (function_exists('exif_read_data') && $metadata['format'] == 'jpeg') {
            $metadata['exif'] = exif_read_data($filename);
        }

        return $metadata;
    }

    /**
     * Loads metadata to internal variables.
     *
     * @return void
     */
    protected function getMetadataForImage()
    {
        $this->metadata = $this->getMetadataFromFile($this->filename);

        $this->width = $this->metadata['width'];
        $this->height = $this->metadata['height'];
    }

    /**
     * Gets mime for an image from its extension.
     *
     * @param  string $filename Filename to be checked.
     * @return string           Mime for the filename given.
     * @throws InvalidExtensionException
     */
    protected function getMimeFromExtension($filename)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';
            case 'png':
                return 'image/png';
            case 'gif':
                return 'image/gif';
            default:
                throw new InvalidExtensionException($extension);
        }
    }

    /**
     * Checks that the given value is between our defined range.
     *
     * Can check just for min or max if setting the other value to false.
     *
     * @param  int  $value Value to be checked,
     * @param  bool $min   Minimum value. False to just use max.
     * @param  bool $max   Maximum value. False to just use min.
     * @return int         The value itself.
     */
    protected static function fitInRange($value, $min = false, $max = false)
    {
        if ($min !== false && $value < $min) {
            $value = $min;
        }

        if ($max !== false && $value > $max) {
            $value = $max;
        }

        return $value;
    }

    /**
     * Resets width and height of the current image.
     *
     * @return void
     */
    protected function updateSize()
    {
        $this->width  = imagesx($this->image);
        $this->height = imagesy($this->image);
    }

    /**
     * Required by vignette to generate the propper colors.
     *
     * @param  float  $size  Size of the vignette, between 0 and 10. Low is sharper.
     * @param  float  $level Vignete transparency, between 0 and 1
     * @param  int    $x     X position of the pixel.
     * @param  int    $y     Y position of the pixel.
     * @param  array  &$rgb  Current pixel olor information.
     * @return void
     */
    protected function vignetteEffect($size , $level, $x, $y, &$rgb)
    {
        $l = sin(M_PI / $this->width * $x) * sin(M_PI / $this->height * $y);
        $l = pow($l, $this->fitInRange($size , 0, 10));

        $l = 1 - $this->fitInRange($level, 0, 1) * (1 - $l);

        $rgb['red'] *= $l;
        $rgb['green'] *= $l;
        $rgb['blue'] *= $l;
    }
}
