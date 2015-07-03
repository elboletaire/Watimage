<?php
namespace Elboletaire\Watimage;

class Watermark extends Image
{
    protected $size;

    protected $margin = [0, 0];

    protected $position;

    /**
     * {@inheritdoc}
     *
     * @param string $file    Filepath of the watermark to be loaded.
     * @param array  $options Array of options to be set, with keys: size,
     *                        position and/or margin.
     */
    public function __construct($file = null, $options = [])
    {
        if (!empty($options)) {
            foreach ($options as $option => $values) {
                $method = 'set' . ucfirst($option);
                if (!method_exists($this, $method)) {
                    continue;
                }

                $this->$method($values);
            }
        }

        return parent::__construct($file);
    }

    /**
     * {@inheritdoc}
     *
     * @return Watermark
     */
    public function destroy()
    {
        $this->size = null;
        $this->margin = [0, 0];
        $this->position = null;

        return parent::destroy();
    }

    /**
     * Sets the position of the watermark.
     *
     * @param  mixed  $x  Can be a position ala CSS, just position X or an array
     *                    containing both params.
     * @param  int    $y  Position Y.
     */
    public function setPosition($x, $y = null)
    {
        $this->position = Normalize::watermarkPosition($x, $y);

        return $this;
    }

    /**
     * Sets the position of the watermark.
     *
     * This method has been added for backwards compatibility. If you wanna resize
     * the watermark you can directly call ->resize from Watermark object.
     *
     * @param  mixed  $width  Can be just width or an array containing both params.
     * @param  int    $height Height.
     */
    public function setSize($width, $height = null)
    {
        $this->size = Normalize::watermarkSize($width, $height);

        return $this;
    }

    /**
     * Sets a margin for the watermark. Useful if you're using positioning ala CSS.
     *
     * @param  mixed  $x  Can be just x position or an array containing both params.
     * @param  int    $y  Y position.
     */
    public function setMargin($x, $y = null)
    {
        $this->margin = Normalize::margin($x, $y);

        return $this;
    }

    /**
     * Applies the watermark to the given image.
     *
     * @param  Image  $image The image where apply the watermark.
     * @return Image         The resulting watermarked Image, so you can
     *                       do $watermark->apply($image)->generate().
     */
    public function apply(Image $image)
    {
        $metadata = $image->getMetadata();
        $this->calculateSize($metadata);
        list($x, $y) = $this->calculatePosition($metadata);
        $resource = $image->getImage();

        // @codingStandardsIgnoreStart
        imagecopy(
            $resource, $this->image,
            $x, $y, 0, 0,
            $this->width, $this->height
        );
        // @codingStandardsIgnoreEnd

        $image->setImage($resource);

        return $image;
    }

    /**
     * Calculates the position of the watermark.
     *
     * @param  array $metadata Image to be watermarked metadata.
     * @return array           Position in array x,y
     */
    protected function calculatePosition($metadata)
    {
        // Force center alignement if 'full' size has been set
        if ($this->size == 'full') {
            $this->position = 'center center';
        }

        if (is_array($this->position)) {
            return $this->position;
        }

        if (empty($this->position)) {
            $this->position = 'center center';
        }

        $x = $y = 0;

        // Horizontal
        if (preg_match('/right/', $this->position)) {
            $x = $metadata['width'] - $this->width + $this->margin[0];
        } elseif (preg_match('/left/', $this->position)) {
            $x = 0  + $this->margin[0];
        } elseif (preg_match('/center/', $this->position)) {
            $x = $metadata['width'] / 2 - $this->width / 2  + $this->margin[0];
        }

        // Vertical
        if (preg_match('/bottom/', $this->position)) {
            $y = $metadata['height'] - $this->height  + $this->margin[1];
        } elseif (preg_match('/top/', $this->position)) {
            $y = 0  + $this->margin[1];
        } elseif (preg_match('/center/', $this->position)) {
            $y = $metadata['height'] / 2 - $this->height / 2  + $this->margin[1];
        }

        return [$x, $y];
    }

    /**
     * Calculates the required size for the watermark from $this->size.
     *
     * @param  array $metadata Image metadata
     * @return void
     */
    protected function calculateSize($metadata)
    {
        if (!isset($this->size)) {
            return;
        }

        if (is_array($this->size)) {
            list($width, $height) = $this->size;
        } elseif (preg_match('/[0-9]{1,3}%$/', $this->size)) {
            $ratio = $this->size / 100;

            $width = $this->width * $ratio;
            $height = $this->height * $ratio;
        } else {
            // size == 'full' or any other string
            $width = $this->width;
            $height = $this->height;

            if ($this->width > $metadata['width'] * 1.05 && $this->height > $metadata['height'] * 1.05) {
                // both are already larger than the original by at least 5%...
                // we need to make the watermark *smaller* for this one.
                // where is the largest difference?
                $wdiff = $width - $metadata['width'];
                $hdiff = $height - $metadata['height'];
                if ($wdiff > $hdiff) {
                    // the width has the largest difference - get percentage
                    $ratio = ($wdiff / $width) - 0.05;
                } else {
                    $ratio = ($hdiff / $height) - 0.05;
                }
                $width -= $width * $ratio;
                $height -= $height * $ratio;
            } else {
                // the watermark will need to be enlarged for this one
                // where is the largest difference?
                $wdiff = $metadata['width'] - $width;
                $hdiff = $metadata['height'] - $height;
                if ($wdiff > $hdiff) {
                // the width has the largest difference - get percentage
                    $ratio = ($wdiff / $width) + 0.05;
                } else {
                    $ratio = ($hdiff / $height) + 0.05;
                }
                $width += $width * $ratio;
                $height += $height * $ratio;
            }
        }

        // Això s'ha de repensar tu, que sinó cada cop que s'aplica la marca
        // d'aigua es va fent més petita, i no interessa...!
        $this->classicResize($width, $height);
    }
}
