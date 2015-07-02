<?php
namespace Elboletaire\Watimage;

class Watermark extends Image
{
    protected $resource_image, $size, $margin, $position;

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

    public function setPosition($position = null)
    {

        return $this;
    }

    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    public function setMargin($margin)
    {
        $this->margin = $margin;

        return $this;
    }

    public function apply()
    {

        return $this;
    }
}
