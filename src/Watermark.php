<?php
namespace Elboletaire\Watimage;

class Watermark extends Image
{
    protected $size, $margin = ['x' => 0, 'y' => 0], $position;

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

    public function destroy()
    {
        $this->size = null;
        $this->margin = ['x' => 0, 'y' => 0];
        $this->position = null;

        return parent::destroy();
    }

    public function setPosition($position = null)
    {
        $this->position = Normalize::watermarkPosition($position);

        return $this;
    }

    public function setSize($width, $height = null)
    {
        $this->size = Normalize::watermarkSize($width, $height);

        return $this;
    }

    public function setMargin($margin)
    {
        $this->margin = $margin;

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
        $this->calculatePosition($metadata);
        $this->calculateSize($metadata);
        $resource = $image->getImage();

        imagecopy(
            $resource, $this->image,
            $this->position['x'], $this->position['y'], 0, 0,
            $this->width, $this->height
        );

        $image->setImage($resource);

        return $image;
    }

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
            $x = $metadata['width'] - $this->width + $this->margin['x'];
        } elseif (preg_match('/left/', $this->position)) {
            $x = 0  + $this->margin['x'];
        } elseif (preg_match('/center/', $this->position)) {
            $x = $metadata['width'] / 2 - $this->width / 2  + $this->margin['x'];
        }

        // Vertical
        if (preg_match('/bottom/', $this->position)) {
            $y = $metadata['height'] - $this->height  + $this->margin['y'];
        } elseif (preg_match('/top/', $this->position)) {
            $y = 0  + $this->margin['y'];
        } elseif (preg_match('/center/', $this->position)) {
            $y = $metadata['height'] / 2 - $this->height / 2  + $this->margin['y'];
        }

        $this->position = compact('x', 'y');
    }

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

        $this->classicResize($width, $height);
    }
}
