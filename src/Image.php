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

        if ($bgcolor === -1) {
            $bgcolor = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
        }

        $this->image = imagerotate($this->image, $degrees * -1, $bgcolor);

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
        $this->metadata = self::getMetadataFromFile($this->filename);

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
     *  Applies some values to image for handling transparency
     *
     * @return void
     */
    protected function handleTransparency(&$image)
    {
        imagesavealpha($image, true);
        imagealphablending($image, true);
    }
}
