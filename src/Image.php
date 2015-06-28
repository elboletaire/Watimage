<?php
namespace Elboletaire\Watimage;

use Exception;

class Image
{
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
            throw new Exception("PHP GD extension is required by Watimage but it's not loaded");
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
     * @throws Exception If mime type is not allowed or recognised.
     */
    public function createResourceImage($filename, $format)
    {
        switch ($format) {
            case 'gif':
                return imagecreatefromgif($filename);

            case 'png':
                return imagecreatefrompng($filename);

            case 'jpeg':
                return imagecreatefromjpeg($filename);

            default:
                throw new Exception("Mime type \"{$this->metadata['mime']}\" not allowed or not recognised");
        }
    }

    /**
     * Cleans up everything to start again.
     *
     * @return void
     */
    public function destroy()
    {
        if (!is_null($this->image) && get_resource_type($this->image) == 'gd') {
            imagedestroy($this->image);
        }
        $this->metadata = [];
        $this->filename = $this->width = $this->height = null;
    }

    /**
     * Outputs or saves the image.
     *
     * @param  string $filename Filename to be saved. Empty to directly print on screen.
     * @param string $output Use it to overwrite the output format when no $filename is passed.
     * @return void
     * @throws Exception If output format is not recognised.
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
                throw new Exception("Invalid output format \"{$output}\"");
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
     *  @throws Exception
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
            throw new Exception("Image file has not been set");
        }

        if (!file_exists($filename)) {
            throw new Exception("Image file \"$filename\" does not exist");
        }

        $this->destroy();

        $this->filename = $filename;
        $this->getMetadataForImage();
        $this->image = $this->createResourceImage($filename, $this->metadata['format']);
        $this->handleTransparency($this->image);

        return $this;
    }

    /**
     * Rotates an image clockwise.
     *
     * @param  int    $degrees Rotation angle in degrees.
     * @param  mixed  $bgcolor [description]
     * @return Image
     */
    public function rotate($degrees, $bgcolor = null)
    {
        if (is_null($bgcolor)) {
            $bgcolor = -1;
        }

        $color = $this->getColorArray($bgcolor);
        $bgcolor = imagecolorallocatealpha($this->image, $color['r'], $color['g'], $color['b'], $color['a']);

        $this->image = imagerotate($this->image, $degrees * -1, $bgcolor);

        $this->updateSize();

        return $this;
    }

    /**
     * Flips an image. If PHP version is 5.5.0 or greater will use
     * proper php gd imageflip method. Otherwise will fallback to
     * convenienceflip.
     *
     * @param  string $type Type of flip, can be any of: horizontal, vertical, both
     * @return Image
     * @throws Exception    If invalid flip $type
     */
    public function flip($type = 'horizontal')
    {
        if (version_compare(PHP_VERSION, '5.5.0', '<')) {
            return $this->convenienceFlip($type);
        }

        $types = [
            'horizontal' => IMG_FLIP_HORIZONTAL,
            'vertical'   => IMG_FLIP_VERTICAL,
            'both'       => IMG_FLIP_BOTH
        ];

        if (!array_key_exists($type, $types)) {
            throw new Exception("Incorrect flip type \"{$type}\"");
        }

        imageflip($this->image, $types[$type]);

        return $this;
    }

    /**
     * Flip method for PHP versions < 5.5.0
     *
     * @param  string $type Type of flip, can be any of: horizontal, vertical, both
     * @return Image
     * @throws Exception    If invalid flip $type
     */
    public function convenienceFlip($type = 'horizontal')
    {
        $type = strtolower($type);

        if ($type == 'both') {
            return $this->rotate(180);
        }

        $resampled = imagecreatetruecolor($this->width, $this->height);
        imagealphablending($resampled, false);
        imagesavealpha($resampled, true);

        switch ($type) {
            case 'vertical':
                for ($y = 0; $y < $this->height; $y++) {
                    imagecopy($resampled, $this->image, 0, $y, 0, $this->height - $y - 1, $this->width, 1);
                }
                break;
            case 'horizontal':
                for ($x = 0; $x < $this->width; $x++) {
                    imagecopy($resampled, $this->image, $x, 0, $this->width - $x - 1, 0, 1, $this->height);
                }
                break;
            default:
                imagedestroy($resampled);
                throw new Exception("Incorrect flip type \"{$type}\"");
        }

        $this->image = $resampled;

        return $this;
    }

    /**
     * Blurs the image.
     *
     * @param  mixed   $type   Type of blur to be used between: gaussian, selective.
     * @param  integer $passes Number of times to apply the filter.
     * @return Image
     */
    public function blur($type = null, $passes = 1)
    {
        switch (strtolower($type)) {
            case IMG_FILTER_GAUSSIAN_BLUR:
            case 'selective':
                $type = IMG_FILTER_GAUSSIAN_BLUR;
                break;

            case null:
            case 'gaussian':
            case IMG_FILTER_SELECTIVE_BLUR:
                $type = IMG_FILTER_SELECTIVE_BLUR;
                break;

            default:
                throw new Exception("Incorrect blur type \"{$type}\"");
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
     * @param  mixed  $color Color in any format accepted by getColorArray
     * @return Image
     */
    public function colorize($color)
    {
        $color = $this->getColorArray($color);

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
     * @param [type] $image [description]
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
     * Returns the proper color array for the given color.
     *
     * It accepts any (or almost any) imaginable type.
     *
     * @param  mixed  $color Can be an array (sequential or associative) or
     *                       hexadecimal. In hexadecimal allows 3 and 6 characters
     *                       for rgb and 4 or 8 characters for rgba.
     * @return array         Containing all 4 color channels.
     */
    public static function getColorArray($color)
    {
        if ($color === -1) {
            return [
                'r' => 0,
                'g' => 0,
                'b' => 0,
                'a' => 127
            ];
        }
        if (is_array($color) && in_array(count($color), [3,4])) {
            $allowed_keys = [
                'associative' => ['red', 'green', 'blue', 'alpha'],
                'reduced'     => ['r', 'g', 'b', 'a'],
                'numeric'     => [0, 1, 2, 3]
            ];

            foreach ($allowed_keys as $keys) {
                list($r, $g, $b, $a) = $keys;

                if (!isset($color[$r], $color[$g], $color[$b])) {
                    continue;
                }

                return [
                    'r' => self::fitInRange($color[$r], 0, 255),
                    'g' => self::fitInRange($color[$g], 0, 255),
                    'b' => self::fitInRange($color[$b], 0, 255),
                    'a' => self::fitInRange(isset($color[$a]) ? $color[$a] : 0, 0, 127),
                ];
            }

            $encoded = json_encode($color);
            throw new Exception("Invalid array color value $encoded");
        }

        if (is_string($color)) {
            $color = ltrim($color, '#');
            if (strlen($color) == 6) {
                list($r, $g, $b) = [
                    $color[0].$color[1],
                    $color[2].$color[3],
                    $color[4].$color[5]
                ];
            } elseif (strlen($color) == 3) {
                list($r, $g, $b) = array(
                    $color[0].$color[0],
                    $color[1].$color[1],
                    $color[2].$color[2]
                );
            } elseif (strlen($color) == 8) {
                list($r, $g, $b, $a) = [
                    $color[0].$color[1],
                    $color[2].$color[3],
                    $color[4].$color[5],
                    $color[6].$color[7]
                ];
            } elseif (strlen($color) == 4) {
                list($r, $g, $b, $a) = [
                    $color[0].$color[0],
                    $color[1].$color[1],
                    $color[2].$color[2],
                    $color[3].$color[3]
                ];
            } else {
                throw new Exception("Invalid hexadecimal color value \"$color\"");
            }

            return [
                'r' => hexdec($r),
                'g' => hexdec($g),
                'b' => hexdec($b),
                'a' => isset($a) ? hexdec($a) : 0
            ];
        }

        throw new Exception("Invalid color value \"$color\"");
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
     * @throws Exception        If extension is not recognised.
     */
    protected function getMimeFromExtension($filename)
    {
        $info = pathinfo($filename);
        switch ($info['extension']) {
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';
            case 'png':
                return 'image/png';
            case 'gif':
                return 'image/gif';
            default:
                throw new Exception("Extension \"{$info['extension']}\" not allowed");
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
     *  Applies some values to image for handling transparency
     *
     * @return void
     */
    protected function handleTransparency(&$image)
    {
        imagesavealpha($image, true);
        imagealphablending($image, true);
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
