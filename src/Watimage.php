<?php
namespace Elboletaire\Watimage;

use Elboletaire\Watimage\Exception\InvalidArgumentException;

/**
 * This is a backwards compatibility class. It just has the old Watimage methods
 * and workflow so you can upgrade any project to the new Watimage without
 * changing your code.
 *
 * @author Òscar Casajuana Alonso <elboletaire@underave.net>
 * @copyright 2015 Òscar Casajuana <elboletaire at underave dot net>
 * @link https://github.com/elboletaire/Watimage
 * @license https://opensource.org/licenses/MIT MIT
 */
class Watimage
{
    /**
     * Image handler.
     *
     * @var Image
     */
    protected $image;

    /**
     * Watermark handler.
     *
     * @var Watermark False if no watermark set, Watermark otherwise.
     */
    protected $watermark = false;

    /**
     * Any error returned by the class will be stored here
     * @public array $errors
     */
    public $errors = [];

    /**
     * Construct method. Accepts file and watermark as parameters so you
     * can avoid the setImage and setWatermark methods.
     *
     * @param mixed $file       Image details if is an array, image file
     *                          location otherwise
     * @param mixed $watermark  Watermark details if is an array, watermark file
     *                          location otherwise.
     */
    public function __construct($file = null, $watermark = null)
    {
        $this->image = new Image($file);

        if (!is_null($watermark)) {
            $this->watermark = new Watermark();
            // This setWatermark method is backwards compatible!
            $this->setWatermark($watermark);
        }
    }

    /**
     *  Sets image and (optionally) its options
     *
     *  @param mixed $filename Filename string or array containing both filename and quality
     *  @return Watimage
     *  @throws Exception
     */
    public function setImage($filename)
    {
        try {
            $this->image->load($filename);

            return true;
        } catch (Exception $e) {
            array_push($this->errors, $e->getMessage());

            return false;
        }
    }

    /**
     * Sets quality for gif and jpg files.
     *
     * @param int $quality A value from 0 (zero quality) to 100 (max quality).
     */
    public function setQuality($quality)
    {
        try {
            $this->image->setQuality($quality);

            return true;
        } catch (Exception $e) {
            array_push($this->errors, $e->getMessage());

            return false;
        }
    }

    /**
     * Sets compression for png files.
     *
     * @param int $compression A value from 0 (no compression, not recommended) to 9.
     */
    public function setCompression($compression)
    {
        try {
            $this->image->setQuality($compression);

            return true;
        } catch (Exception $e) {
            array_push($this->errors, $e->getMessage());

            return false;
        }
    }

    /**
     * Set watermark and (optionally) its options.
     *
     * @param mixed $options You can set the watermark without options or you can
     *                       set an array with any of these $options = [
     *                           'file'     => 'watermark.png',
     *                           'position' => 'bottom right', // default
     *                           'margin'   => ['20', '10'] // 0 by default,
     *                           'size'     => 'full' // 100% by default
     *                       ];
     * @return true on success; false on failure
     */
    public function setWatermark($options = [])
    {
        try {
            if (!$this->watermark) {
                $this->watermark = new Watermark();
            }

            if (!is_array($options)) {
                $this->watermark->load($options);

                return true;
            }

            if (!isset($options['file'])) {
                throw new InvalidArgumentException("Watermark \"file\" param not specified");
            }

            $this->watermark->load($options['file']);

            foreach (['position', 'margin', 'size'] as $option) {
                if (!array_key_exists($option, $options)) {
                    continue;
                }

                $method = 'set' . ucfirst($option);
                $this->watermark->$method($options[$option]);
            }

            return true;
        } catch (Exception $e) {
            array_push($this->errors, $e->getMessage());

            return false;
        }
    }

    /**
     *  Resizes the image.
     *
     *  @param array $options = [
     *                  'type' => 'resizemin|resizecrop|resize|crop|reduce',
     *                  'size' => ['x' => 2000, 'y' => 500]
     *               ]
     *               You can also set the size without specifying x and y:
     *               [2000, 500]. Or directly 'size' => 2000 (takes 2000x2000)
     *  @return bool true on success; otherwise false
     */
    public function resize($options = [])
    {
        try {
            $this->image->resize($options['type'], $options['size']);

            return true;
        } catch (Exception $e) {
            array_push($this->errors, $e->getMessage());

            return false;
        }
    }

    /**
     * Crops an image based on specified coords and size.
     *
     * @param mixed $options Specifying x & y position and width & height, like
     *                       so [
     *                           'x'      => 23,
     *                           'y'      => 23,
     *                           'width'  => 230,
     *                           'height' => 230
     *                        ]
     * @return bool success
     */
    public function crop($options = [])
    {
        try {
            $this->image->crop($options);

            return true;
        } catch (Exception $e) {
            array_push($this->errors, $e->getMessage());

            return false;
        }
    }

    /**
     *  Rotates an image.
     *
     *  @param mixed $options Can either be an integer with the degrees or an array with
     *                        keys `bgcolor` for the rotation bgcolor and `degrees` for
     *                        the angle.
     *  @return bool
     */
    public function rotateImage($options = [])
    {
        try {
            if (is_array($options)) {
                if (empty($options['bgcolor'])) {
                    $options['bgcolor'] = -1;
                }
                $this->image->rotate($options['degrees'], $options['bgcolor']);
            } else {
                $this->image->rotate($options);
            }

            return true;
        } catch (Exception $e) {
            array_push($this->errors, $e->getMessage());

            return false;
        }
    }

    /**
     *  rotateImage alias.
     *
     * @see self::rotateImage()
     */
    public function rotate($options = [])
    {
        return $this->rotateImage($options);
    }

    /**
     *  Applies a watermark to the image. Needs to be initialized with $this->setWatermark()
     *
     *  @return true on success, otherwise false
     */
    public function applyWatermark()
    {
        try {
            $this->watermark->apply($this->image);

            return true;
        } catch (Exception $e) {
            array_push($this->errors, $e->getMessage());

            return false;
        }
    }

    /**
     *  Flips an image.
     *
     *  @param string $type type of flip: horizontal / vertical / both
     *  @return true on success. Otherwise false
     */
    public function flip($type = 'horizontal')
    {
        try {
            $this->image->flip($type);

            return true;
        } catch (Exception $e) {
            array_push($this->errors, $e->getMessage());

            return false;
        }
    }

    /**
     *  Generates the image file.
     *
     *  @param string $path if not specified image will be printed on screen
     *  @param string $output mime type for output image (image/png, image/gif, image/jpeg)
     *  @return true on success. Otherwise false
     */
    public function generate($path = null, $output = null)
    {
        try {
            $this->image->generate($path, $output);

            return true;
        } catch (Exception $e) {
            array_push($this->errors, $e->getMessage());

            return false;
        }
    }
}
