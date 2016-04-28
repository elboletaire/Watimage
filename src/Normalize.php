<?php
namespace Elboletaire\Watimage;

use Elboletaire\Watimage\Exception\InvalidArgumentException;

/**
 * The Normalize class. It takes params in a lot of different ways, but it
 * always return our expected params :D
 *
 * @author Òscar Casajuana <elboletaire at underave dot net>
 * @copyright 2015 Òscar Casajuana <elboletaire at underave dot net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link https://github.com/elboletaire/Watimage
 */
class Normalize
{
    /**
     * Returns the proper color array for the given color.
     *
     * It accepts any (or almost any) imaginable type.
     *
     * @param  mixed  $color Can be an array (sequential or associative) or
     *                       hexadecimal. In hexadecimal allows 3 and 6 characters
     *                       for rgb and 4 or 8 characters for rgba.
     * @return array         Containing all 4 color channels.
     * @throws InvalidArgumentException
     */
    public static function color($color)
    {
        if ($color === Image::COLOR_TRANSPARENT) {
            return [
                'r' => 0,
                'g' => 0,
                'b' => 0,
                'a' => 127
            ];
        }

        // rgb(a) arrays
        if (is_array($color) && in_array(count($color), [3, 4])) {
            $allowedKeys = [
                'associative' => ['red', 'green', 'blue', 'alpha'],
                'reduced'     => ['r', 'g', 'b', 'a'],
                'numeric'     => [0, 1, 2, 3]
            ];

            foreach ($allowedKeys as $keys) {
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

            throw new InvalidArgumentException("Invalid array color value %s.", $color);
        }

        // hexadecimal
        if (!is_string($color)) {
            throw new InvalidArgumentException("Invalid color value \"%s\"", $color);
        }

        $color = ltrim($color, '#');
        if (in_array(strlen($color), [3, 4])) {
            $color = str_split($color);
            $color = array_map(function ($item) {
                return str_repeat($item, 2);
            }, $color);
            $color = implode($color);
        }
        if (strlen($color) == 6) {
            list($r, $g, $b) = [
                $color[0] . $color[1],
                $color[2] .$color[3],
                $color[4] . $color[5]
            ];
        } elseif (strlen($color) == 8) {
            list($r, $g, $b, $a) = [
                $color[0] . $color[1],
                $color[2] . $color[3],
                $color[4] . $color[5],
                $color[6] . $color[7]
            ];
        } else {
            throw new InvalidArgumentException("Invalid hexadecimal color value \"%s\"", $color);
        }

        return [
            'r' => hexdec($r),
            'g' => hexdec($g),
            'b' => hexdec($b),
            'a' => isset($a) ? hexdec($a) : 0
        ];
    }

    /**
     * Normalizes crop arguments returning an array with them.
     *
     * You can pass arguments one by one or an array passing arguments
     * however you like.
     *
     * @param  int $x      X position where start to crop.
     * @param  int $y      Y position where start to crop.
     * @param  int $width  New width of the image.
     * @param  int $height New height of the image.
     * @return array       Array with numeric keys for x, y, width & height
     * @throws InvalidArgumentException
     */
    public static function crop($x, $y = null, $width = null, $height = null)
    {
        if (!isset($y, $width, $height) && is_array($x)) {
            $values = $x;
            $allowedKeys = [
                'associative' => ['x', 'y', 'width', 'height'],
                'reduced'     => ['x', 'y', 'w', 'h'],
                'numeric'     => [0, 1, 2, 3]
            ];

            foreach ($allowedKeys as $keys) {
                list($x, $y, $width, $height) = $keys;
                if (isset($values[$x], $values[$y], $values[$width], $values[$height])) {
                    return [
                        $values[$x],
                        $values[$y],
                        $values[$width],
                        $values[$height]
                    ];
                }
            }
        }

        if (!isset($x, $y, $width, $height)) {
            throw new InvalidArgumentException(
                "Invalid options for crop %s.",
                compact('x', 'y', 'width', 'height')
            );
        }

        return [$x, $y, $width, $height];
    }

    /**
     * Normalizes flip type from any of the allowed values.
     *
     * @param  mixed $type  Can be either:
     *                      v, y, vertical or IMG_FLIP_VERTICAL
     *                      h, x, horizontal or IMG_FLIP_HORIZONTAL
     *                      b, xy, yx, both or IMG_FLIP_BOTH
     * @return int
     * @throws InvalidArgumentException
     */
    public static function flip($type)
    {
        switch (strtolower($type)) {
            case 'x':
            case 'h':
            case 'horizontal':
            case IMG_FLIP_HORIZONTAL:
                return IMG_FLIP_HORIZONTAL;
                break;

            case 'y':
            case 'v':
            case 'vertical':
            case IMG_FLIP_VERTICAL:
                return IMG_FLIP_VERTICAL;
                break;

            case 'b':
            case 'both':
            case IMG_FLIP_BOTH:
                return IMG_FLIP_BOTH;
                break;

            default:
                throw new InvalidArgumentException("Incorrect flip type \"%s\"", $type);
                break;
        }
    }

    /**
     * An alias of self::position but returning a customized message for Watermark.
     *
     * @param  mixed  $x Can be just x or an array containing both params.
     * @param  int    $y Can only be y.
     * @return array     With x and y in a sequential array.
     * @throws InvalidArgumentException
     */
    public static function margin($x, $y = null)
    {
        try {
            list($x, $y) = self::position($x, $y);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("Invalid margin %s.", compact('x', 'y'));
        }

        return [$x, $y];
    }

    /**
     * Normalizes position (x and y).
     *
     * @param  mixed  $x Can be just x or an array containing both params.
     * @param  int    $y Can only be y.
     * @return array     With x and y in a sequential array.
     * @throws InvalidArgumentException
     */
    public static function position($x, $y = null)
    {
        if (is_array($x)) {
            if (isset($x['x']) || isset($x['y'])) {
                extract($x);
            } else {
                @list($x, $y) = $x;
            }
        }

        if (is_numeric($x) && !isset($y)) {
            $y = $x;
        }

        if (!isset($x, $y) || !(is_numeric($x) && is_numeric($y))) {
            throw new InvalidArgumentException("Invalid position %s.", compact('x', 'y'));
        }

        return [$x, $y];
    }

    /**
     * Normalizes size (width and height).
     *
     * @param  mixed  $width  Can be just width or an array containing both params.
     * @param  int    $height Can only be height.
     * @return array          With width and height in a sequential array.
     * @throws InvalidArgumentException
     */
    public static function size($width, $height = null)
    {
        if (!isset($height) && is_array($width)) {
            $allowedKeys = [
                [0, 1],
                ['x', 'y'],
                ['w', 'h'],
                ['width', 'height'],
            ];

            foreach ($allowedKeys as $keys) {
                list($x, $y) = $keys;


                if (isset($width[$x])) {
                    if (isset($width[$y])) {
                        $height = $width[$y];
                    }
                    $width = $width[$x];
                    break;
                }
            }
        }

        if (isset($width) && !isset($height)) {
            $height = $width;
        }

        if (!isset($width, $height) || !(is_numeric($width) && is_numeric($height))) {
            throw new InvalidArgumentException(
                "Invalid resize arguments %s",
                compact('width', 'height')
            );
        }

        return [
            self::fitInRange($width, 0),
            self::fitInRange($height, 0)
        ];
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
    public static function fitInRange($value, $min = false, $max = false)
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
     * Normalizes position + position ala css.
     *
     * @param  mixed $position Array with x,y or string ala CSS.
     * @return mixed           Returns what you pass (array or string).
     * @throws InvalidArgumentException
     */
    public static function cssPosition($position)
    {
        try {
            $position = self::position($position);
        } catch (InvalidArgumentException $e) {
            if (!is_string($position)) {
                throw new InvalidArgumentException("Invalid watermark position %s.", $position);
            }

            if (in_array($position, ['center', 'centered'])) {
                $position = 'center center';
            }

            if (!preg_match('/((center|top|bottom|right|left) ?){2}/', $position)) {
                throw new InvalidArgumentException("Invalid watermark position %s.", $position);
            }
        }

        return $position;
    }

    /**
     * Returns proper size argument for Watermark.
     *
     * @param  mixed  $width  Can be a percentage, just width or an array containing both params.
     * @param  int    $height Can only be height.
     * @return mixed
     */
    public static function watermarkSize($width, $height = null)
    {
        try {
            $width = self::size($width, $height);
        } catch (InvalidArgumentException $e) {
            if (!is_string($width) || !preg_match('/([0-9]{1,3}%|full)$/', $width)) {
                throw new InvalidArgumentException(
                    "Invalid size arguments %s",
                    compact('width', 'height')
                );
            }
        }

        return $width;
    }
}
