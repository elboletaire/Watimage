<?php

namespace Elboletaire\Watimage\Text;

use Elboletaire\Watimage\Image;
use Elboletaire\Watimage\Normalize;

class Text extends Image
{
    protected $position;

    protected $margin;

    public function __construct($resource = null)
    {

    }

    public function setPosition($position)
    {
        $this->position = Normalize::cssPosition($position);

        return $this;
    }

    public function setMargin($margin)
    {
        $this->margin = Normalize::margin($margin);

        return $this;
    }

    public function string()
    {

    }

    protected function calculatePosition($metadata)
    {
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
}
