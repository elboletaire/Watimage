<?php
namespace Elboletaire\Watimage;
/**
 *
 * @author Òscar Casajuana Alonso <elboletaire@underave.net>
 * @version 1.0
 * @link https://github.com/elboletaire/Watimage
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 2.1 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */
class Watimage
{
	/**
	 * Sizes especified by user to enlarge or
	 * reduce watermark and / or final image
	 * @public array $size
	 */
	public $size;

	/**
	 * Any error returned by the class will be stored here
	 * @public array $errors
	 */
	public $errors;

	/**
	 * If enabled it will return more info on error
	 * @public bool $debug
	 */
	public $debug = false;

	/**
	 * Current sizes of image files
	 * @private array $sizes
	 */
	private $current_size;

	/**
	 * Resize values
	 * @private array $resize
	 */
	private $resize = false;

	/**
	 * Watermark margins
	 * @private array $margin
	 */
	private $margin = array('x' => 0, 'y' => 0);

	/**
	 * Watermark position
	 * @private string $position
	 */
	private $position = "bottom right";

	/**
	 *	Some other private vars...
	 */
	private $rotate = false;
	private $image;
	private $watermark = false;
	private $output;
	private $quality = 80; // image export quality. You can set it with $watermark->setQuality(50);
						   // or with $watermark->setImage(array('quality' => 50, 'file' => 'file.jpg')
	private $file = array();
	private $extension;

	public function __construct($file = null, $watermark = null)
	{
		if ( !empty($file) )
			$this->setImage($file);

		if ( !empty($watermark) )
			$this->setWatermark($watermark);
	}

	/**
	 *	Set image options
	 *	@param array $options [optional]
	 *	@return true on success; otherwise will return false
	 */
	public function setImage($file)
	{
		// Remove possible errors...
		$this->errors = array();
		try
		{
			if ( is_array($file) && isset($file['file']) )
			{
				if ( isset($file['quality']) )
					$this->setQuality($file['quality']);
				$file = $file['file'];
			}
			elseif ( empty($file) || (is_array($file) && !isset($file['file'])) )
			{
				throw new Exception('Empty file');
			}

			if ( file_exists($file) )
				$this->file['image'] = $file;
			else
				throw new Exception('File "' . $file . '" does not exist');

			// Obtain extension
			$this->extension['image'] = $this->getFileExtension($this->file['image']);
			// Obtain file sizes
			$this->getSizes();
			// Create image boundary
			$this->image = $this->createImage($this->file['image']);
			$this->handleTransparentImage();
		}
		catch ( Exception $e )
		{
			$this->error($e);
			return false;
		}
		return true;
	}

	public function setQuality($quality)
	{
		$this->quality = $quality;
	}

	/**
	 * Set watermark options
	 * @param mixed $options [optional] you can set the watermark without options
	 *				or you can set an array of options like:
	 *				$options = array(
	 *					'file' => 'watermark.png',
	 *					'position' => 'bottom center', // 'bottom center' by default
	 *					'margin' => array('20', '10') // 0 by default
	 *				);
	 * @return true on success; false on failure
	 */
	public function setWatermark($options = array())
	{
		try
		{
			if ( empty($options) )
				throw new Exception('You must set watermark options');

			if ( !is_array($options) )
			{
				$this->file['watermark'] = $options;
			}
			else
			{
				// Watermark image file
				if ( !empty($options['file']) )
					$this->file['watermark'] = $options['file'];
				else
					throw new Exception('You must set the watermark file');

				// Position
				if ( !empty($options['position']) )
					$this->position = $options['position'];

				// Margins
				if ( !empty($options['margin']) )
				{
					if ( !is_array($options['margin']) )
					{
						$this->margin['x'] = $this->margin['y'] = $options['margin'];
					}
					else
					{
						if ( count($options['margin']) == 1 )
						{
							$this->margin['x'] = $this->margin['y'] = array_pop($options['margin']);
						}
						else
						{
							if ( !empty($options['margin'][0]) )
							{
								$this->margin['x'] = $options['margin'][0];
								if (!empty($options['margin'][1]))
								{
									$this->margin['y'] = $options['margin'][1];
								}
							}
							else if ( !empty($options['margin']['x']) || !empty($options['margin']['y']) )
							{
								$this->margin = $options['margin'];
							}
						}
					}
				}

				// Watermark size
				if ( !empty($options['size']) )
				{
					$this->size['watermark'] = $options['size'];
				}
				else
				{
					$this->size['watermark'] = '100%';
				}
			}

			if ( !file_exists($this->file['watermark']) )
			{
				throw new Exception('Specified watermark file does not exist');
			}
			$this->getSizes();
		}
		catch ( Exception $e )
		{
			$this->error($e);
			return false;
		}
		return true;
	}

	/**
	 *	Resizes the image
	 *	@param array $options = array(
	 *					'type' => 'resizemin|resizecrop|resize|crop',
	 *					'size' => array('x' => 2000, 'y' => 500)
	 *				)
	 *			You can also set the size without specifying x and y: array(2000, 500). Or directly 'size' => 2000 (takes 2000x2000)
	 *	@return bool true on success; otherwise false
	 */
	public function resize($options = array())
	{
		if ( !empty($this->errors) ) return false;

		try
		{
			if ( !empty($options['type']) )
			{
				$this->resize['type'] = $options['type'];
				if ( !preg_match('/resize(min|crop)?|crop|reduce/', $this->resize['type']) )
				{
					throw new Exception('Specified resize type "'.$this->resize['type'].'" does not exist');
				}
			}
			else
			{
				throw new Exception('You must specify the type of resize (resizecrop|crop|resize|resizemin)');
			}

			if ( !empty($options['size']) )
			{
				if( is_array($options['size']) )
				{
					if ( array_key_exists('x', $options['size']) && array_key_exists('y', $options['size']) )
					{
						$this->resize['size'] = $options['size'];
					}
					else
					{
						if ( count($options['size']) == 2 )
						{
							$this->resize['size']['x'] = $options['size'][0];
							$this->resize['size']['y'] = $options['size'][1];
						}
						else
						{
							$this->resize['size']['x'] = $this->resize['size']['y'] = $options['size'][0];
						}
					}
				}
				else
				{
					$this->resize['size']['x'] = $this->resize['size']['y'] = $options['size'];
				}
			}
			else
			{
				throw new Exception('You must specify the size to being resized');
			}

			// Resize image!
			switch ( $this->resize['type'] )
			{
				/*
				 * Maintains the aspect ratio of the image and makes sure that it fits
				 * within the max width and max height (thus some side will be smaller)
				 */
				case 'resize':
					if ( $this->current_size['image']['width'] > $this->resize['size']['x'] || $this->current_size['image']['height'] > $this->resize['size']['x'] )
					{
						if ( $this->current_size['image']['width'] > $this->current_size['image']['height'] )
						{
							$new_x = $this->resize['size']['x'];
							$new_y = ( $this->current_size['image']['height'] * $new_x ) / $this->current_size['image']['width'];
						}
						else if ( $this->current_size['image']['width'] < $this->current_size['image']['height'] )
						{
							$new_y = $this->resize['size']['y'];
							$new_x = ( $this->current_size['image']['width'] * $new_y ) / $this->current_size['image']['height'];
						}
						else if ( $this->current_size['image']['width'] == $this->current_size['image']['height'] )
						{
							$new_x = $new_y = $this->resize['size']['x'];
						}
					}
					else
					{
						$new_x = $this->current_size['image']['width'];
						$new_y = $this->current_size['image']['height'];
					}

					$dest_image = $this->createDestImage($new_x, $new_y);

					if ( !imagecopyresampled($dest_image, $this->image, 0, 0, 0, 0, $new_x, $new_y, $this->current_size['image']['width'], $this->current_size['image']['height']) )
					{
						throw new Exception('Could not copy resampled image while resizing');
					}
					$this->current_size['image']['width'] = $new_x;
					$this->current_size['image']['height'] = $new_y;
				break;

				/*
				 * Intended for generating images which do not exceed the
				 * specified boundaries ($max_width and $max_height) under any circumstances,
				 * while maintaining the original aspect ratio.
				 */
				case 'resizemin':
					$max_width  = $this->resize['size']['x'];
					$max_height = $this->resize['size']['y'];
					$old_width  = $this->current_size['image']['width'];
					$old_height = $this->current_size['image']['height'];

					$ratio_resize = 1; // image will be left "as is", unless it is eligible for resizing

					$needs_resize = !(($old_width < $max_width) && ($old_height < $max_height)); // `true` when source image is smaller than both the requested boundaries

					if ($needs_resize) {
						$ratio_resize_x = $old_width / $max_width;
						$ratio_resize_y = $old_height / $max_height;

						// we need to choose one of the most convenient ratios (among these two) for our resize. the biggest one, it is.
						$ratio_resize = $ratio_resize_x > $ratio_resize_y ? $ratio_resize_x : $ratio_resize_y;
					}

					$new_width  = $old_width / $ratio_resize;
					$new_height = $old_height / $ratio_resize;

					$dest_image = $this->createDestImage($new_width, $new_height);

					$success =
					imagecopyresampled(
						$dest_image,
						$this->image,
						0, 0, 0, 0,
						$new_width, $new_height,
						$old_width, $old_height
					);

					if (!$success) { throw new Exception('Could not copy resampled image while resizing'); }

					$this->current_size['image']['width']  = $new_width;
					$this->current_size['image']['height'] = $new_height;
				break;

				/*
				 * only resize if the specified sizes are lower than the image (only resizes to small, never to big)
				 */
				case 'reduce':
					$new_x = $this->current_size['image']['width'];
					$new_y = $this->current_size['image']['height'];
					if ( $this->current_size['image']['width'] > $this->resize['size']['x'] || $this->current_size['image']['height'] > $this->resize['size']['y'] )
					{
						//Checking wich is our big limitation
						$ratio_x = $this->current_size['image']['width'] / $this->resize['size']['x'];
						$ratio_y = $this->current_size['image']['height'] / $this->resize['size']['y'];

						$ratio = $ratio_x > $ratio_y ? $ratio_x : $ratio_y;

						//Getting the new image size
						$new_x = (int)($this->current_size['image']['width'] / $ratio);
						$new_y = (int)($this->current_size['image']['height'] / $ratio);
					}

					$dest_image = $this->createDestImage($new_x, $new_y);

					if ( !imagecopyresampled($dest_image, $this->image, 0, 0, 0, 0, $new_x, $new_y, $this->current_size['image']['width'], $this->current_size['image']['height']) )
					{
						throw new Exception('Could not copy resampled image while resizing');
					}
					$this->current_size['image']['width'] = $new_x;
					$this->current_size['image']['height'] = $new_y;
				break;

				/*
				 * resize to max, then crop to center
				 */
				case 'resizecrop':
					$ratio_x = $this->resize['size']['x'] / $this->current_size['image']['width'];
					$ratio_y = $this->resize['size']['y'] / $this->current_size['image']['height'];

					if ( $ratio_x < $ratio_y )
					{
						$new_x = round(($this->current_size['image']['width'] - ($this->resize['size']['x'] / $ratio_y)) / 2);
						$new_y = 0;
						$this->current_size['image']['width'] = round($this->resize['size']['x'] / $ratio_y);
						$this->current_size['image']['height'] = $this->current_size['image']['height'];
					}
					else
					{
						$new_x = 0;
						$new_y = round(($this->current_size['image']['height'] - ($this->resize['size']['y'] / $ratio_x)) / 2);
						$this->current_size['image']['height'] = round($this->resize['size']['y'] / $ratio_x);
					}

					$dest_image = $this->createDestImage($this->resize['size']['x'], $this->resize['size']['y']);

					if ( !imagecopyresampled($dest_image, $this->image, 0, 0, $new_x, $new_y, $this->resize['size']['x'], $this->resize['size']['y'], $this->current_size['image']['width'], $this->current_size['image']['height']) )
					{
						throw new Exception('Could not copy resampled image while resizing');
					}
					$this->current_size['image']['width'] = $this->resize['size']['x'];
					$this->current_size['image']['height'] = $this->resize['size']['y'];
				break;

				/*
				 * a straight centered crop
				 */
				case 'crop':
					$start_y = ($this->current_size['image']['height'] - $this->resize['size']['y']) / 2;
					$start_x = ($this->current_size['image']['width'] - $this->resize['size']['x']) / 2;

					$dest_image = $this->createDestImage($this->resize['size']['x'], $this->resize['size']['y']);

					if ( !imagecopyresampled($dest_image, $this->image, 0, 0, $start_x, $start_y, $this->resize['size']['x'], $this->resize['size']['y'], $this->resize['size']['x'], $this->resize['size']['y']) )
					{
						throw new Exception('Could not copy resampled image while resizing');
					}
					$this->current_size['image']['width'] = $this->resize['size']['x'];
					$this->current_size['image']['height'] = $this->resize['size']['y'];
				break;

			}

			if ( isset($this->file['watermark']) )
				$this->getWatermarkPosition();

			if ( !imagedestroy($this->image) )
				throw new Exception('Could not destroy tempfile image');

			$this->image = $dest_image;
		}
		catch ( Exception $e )
		{
			$this->error($e);
			return false;
		}
		return true;
	}

	/**
	 * Crops an image based on specified coords and size
	 * @param mixed $options = array('x' => 23, 'y' => 23, 'width' => 230, 'height' => 230)
	 * @return bool success
	 */
	public function crop($options = array())
	{
		if (!empty($this->errors)) return false;

		try
		{
			if (!is_array($options) || empty($options))
				throw new Exception('You must specify options for cropping');
			else if (!isset($options['x']) || !isset($options['y']) || !isset($options['width']) || !isset($options['height']))
				throw new Exception('You left some options for croppping');

			$crop_image = $this->createDestImage($options['width'], $options['height']);

			if ( !imagecopyresampled($crop_image, $this->image, 0, 0, $options['x'], $options['y'], $options['width'], $options['height'], $options['width'], $options['height']) )
				throw new Exception('Could not copy resampled image while resizing');

			$this->image = $crop_image;

			$this->current_size['image']['width'] = $options['width'];
			$this->current_size['image']['height'] = $options['height'];
		}
		catch ( Exception $e )
		{
			$this->error($e);
			return false;
		}
		return true;
	}

	/**
	 *	Rotates an image
	 *	@param mixed $options = array('bgcolor' => 230, 'degrees' => -90); or $options = -90; // takes bgcolor = -1 by default
	 *	@return true on success; false on failure
	 */
	public function rotateImage($options = array())
	{
		if ( !empty($this->errors) ) return false;

		try
		{
			if ( empty($options) )
				throw new Exception('You must set options for rotate method');

			if ( !is_array($options) )
			{
				$this->rotate['degrees'] = $options;
				// Take transparent as default background color if it's not specified
				$this->rotate['bgcolor'] = -1;
			}
			else
			{
				$this->rotate['degrees'] = $options['degrees'];

				if ( !empty($options['bgcolor']) )
					$this->rotate['bgcolor'] = $options['bgcolor'];
				else
					$this->rotate['bgcolor'] = -1;
			}

			$this->image = $this->imgRotate($this->image, $this->rotate['degrees'], $this->rotate['bgcolor']);
			// Obtain new image dimensions
			$this->current_size['image']['width'] = imagesx($this->image);
			$this->current_size['image']['height'] = imagesy($this->image);
		}
		catch ( Exception $e )
		{
			$this->error($e);
			return false;
		}
		return true;
	}

	/**
	 *	rotateImage alias
	 */
	public function rotate($options = array())
	{
		return $this->rotateImage($options);
	}

	/**
	 *	Applies a watermark to the image. Needs to be initialized with $this->setWatermark()
	 *	@return true on success, otherwise false
	 */
	public function applyWatermark()
	{
		if ( !empty($this->errors) ) return false;

		try
		{
			$this->getWatermarkPosition();

			if ( !$this->watermark = $this->createImage($this->file['watermark']) )
				throw new Exception('Could not create watermark image');

			// transparency trick
			if ( !$dest_image = imagecreatetruecolor($this->current_size['image']['width'], $this->current_size['image']['height']) )
				throw new Exception('Could not create tempfile while resizing');

			$bgcolor = imagecolortransparent($dest_image, imagecolorallocatealpha($this->image, 255, 255, 255, 127));
			imagefill($dest_image, 0, 0, $bgcolor);
			imagesavealpha($dest_image, true);
			imagealphablending($dest_image, false);

			if ( !imagecopyresampled($dest_image, $this->image, 0, 0, 0, 0, $this->current_size['image']['width'], $this->current_size['image']['height'], $this->current_size['image']['width'], $this->current_size['image']['height']) )
				throw new Exception('Could not copy resampled image while resizing');

			imagealphablending($dest_image, true);
			imagesavealpha($dest_image, false);
			$this->image = $dest_image;
			// end transparency trick

			if ( !empty($this->size['watermark']) )
				$this->watermark = $this->resize_png_image($this->watermark, $this->current_size['watermark']['width'], $this->current_size['watermark']['width']);

			if ( !imagecopy($this->image, $this->watermark, $this->position['x'], $this->position['y'], 0, 0, $this->current_size['watermark']['width'], $this->current_size['watermark']['height']) )
				throw new Exception('Could not apply watermark to image');
		}
		catch ( Exception $e )
		{
			$this->error($e);
			return false;
		}
		return true;
	}

	/**
	 *	Flips an image.
	 *	@param string $type [optional] type of flip: horizontal / vertical / both
	 *	@return true on success. Otherwise false
	 */
	public function flip($type = 'horizontal')
	{
		try
		{
			$size_x = imagesx($this->image);
			$size_y = imagesy($this->image);
			$temp = imagecreatetruecolor($size_x, $size_y);

			if ( $this->extension['image'] == 'gif' || $this->extension['image'] == 'png' )
			{
				// preserve transparency
				imagealphablending($temp, false);
			}

			switch ($type)
			{
				case 'horizontal':
					$resampled = imagecopyresampled($temp, $this->image, 0, 0, ($size_x - 1), 0, $size_x, $size_y, 0 - $size_x, $size_y);
				break;

				case 'vertical':
					$resampled = imagecopyresampled($temp, $this->image, 0, 0, 0, ($size_y - 1), $size_x, $size_y, $size_x, 0 - $size_y);
				break;

				case 'both': // same as $this->rotate(180)
					$resampled = imagecopyresampled($temp, $this->image, 0, 0, ($size_x - 1), ($size_y - 1), $size_x, $size_y, 0- $size_x, 0 - $size_y);
				break;

				default:
					throw new Exception('Invalid flip type (horizontal|vertical|both)');
			}

			if ( !$resampled )
				throw new Exception('Image could not be flipped');
		}
		catch ( Exception $e )
		{
			$this->error($e);
			return false;
		}
		$this->image = $temp;
		return true;
	}

	/**
	 *	Generates the image file.
	 *	@param string $path [optional] if not specified image will be printed on screen
	 *	@param string $output [optional] mime type for output image (image/png, image/gif, image/jpeg)
	 *	@return true on success. Otherwise false
	 */
	public function generate($path = null, $output = null)
	{
		if ( !empty($this->errors) ) return false;

		try
		{
			if ( !empty($output) )
			{
				$this->output = $output;
			}
			else
			{
				if ( preg_match('/jpe?g/', $this->extension['image']) )
					$this->output = 'image/jpeg';
				elseif ( $this->extension['image'] == 'gif' )
					$this->output = 'image/gif';
				else
					$this->output = 'image/png';
			}

			// If there is no path specified.. we want to output the image
			if ( is_null($path) )
				header('Content-type: ' . $this->output);

			if ($this->extension['image'] == 'gif' || $this->extension['image'] == 'png')
				imagesavealpha($this->image, true);

			// Output / save image
			switch($this->output)
			{
				case 'image/png':
					$quality = round(abs(($this->quality - 100) / 11.111111));
					if ( !imagepng($this->image, $path, $quality) )
						throw new Exception('could not generate png output image');
				break;

				case 'image/jpeg':
					if ( !imagejpeg($this->image, $path, $this->quality) )
						throw new Exception('could not generate output jpeg image');
				break;

				case 'image/gif':
					if ( !imagegif($this->image, $path, $this->quality) )
						throw new Exception('could not generate output gif image');
				break;

				default:
					throw new Exception("Invalid output format ({$this->output})");
			}

			// Destroy image boundary
			if ( !imagedestroy($this->image) )
				throw new Exception('could not destroy image tempfile');

			// Destroy watermark boundary (if exists..)
			if ( isset($this->file['watermark']) )
				if ( !imagedestroy($this->watermark) )
					throw new Exception('could not destroy watermark tempfile');

			$this->file = array();
		}
		catch ( Exception $e )
		{
			$this->error($e);
			return false;
		}
		return true;
	}

	private function createDestImage($width, $height)
	{
		if ( !$dest_image = imagecreatetruecolor($width, $height) )
			throw new Exception('Could not create tempfile while resizing');

		if ( $this->extension['image'] == 'gif' || $this->extension['image'] == 'png' )
		{
			imagecolortransparent($dest_image, imagecolorallocatealpha($dest_image, 0, 0, 0, 127));
			imagealphablending($dest_image, false);
			imagesavealpha($dest_image, true);
		}
		return $dest_image;
	}

	/**
	 *	Creates an image from string
	 *	@return true on success. Otherwise throws an Exception
	 */
	private function createImage($file)
	{
		$ihandle = fopen($file, 'r');
		$image = fread($ihandle, filesize($file));
		fclose($ihandle);

		if ( false === ( $img = imagecreatefromstring($image) ) )
			throw new Exception("Image not valid");

		return $img;
	}

	/**
	 *	Applies some values to image for handling transparency
	 *	@throw Exception on error
	 */
	private function handleTransparentImage()
	{
		if ( preg_match('/gif|png/', $this->extension['image']) )
		{
			if ( !imagealphablending($this->image, true) )
				throw new Exception("Can't apply imagealphablending to image");

			if ( $this->current_size['image']['format'] == 3 )
			{
				// Save alpha for transparent png files
				if ( !imagealphablending($this->image, false) )
					throw new Exception("Can't apply imagealphablending to image");

				if ( !imagesavealpha($this->image, true) )
					throw new Exception("Can't apply imagesavealpha to image");
			}
		}
	}

	/**
	 *	Gets the file extension
	 *	@return string with extension. Throws an Exception on error
	 */
	private function getFileExtension( &$file )
	{
		$f = pathinfo($file);

		if ( empty($f['extension']) )
			throw new Exception("Can't get file extension of $file");

		return $f['extension'];
	}

	/**
	 * Obtains image sizes
	 * @return void
	 */
	private function getSizes()
	{

		if ( !empty($this->file['image']) )
		{
			list($width, $height, $format) = getimagesize($this->file['image']);
			$this->current_size['image'] = compact('width', 'height', 'format');
		}

		if ( !empty($this->file['watermark']) )
		{
			list($width, $height, $format) = getimagesize($this->file['watermark']);
			$this->current_size['watermark'] = compact('width', 'height', 'format');

			if ( !empty($this->size['watermark']) )
			{
				// Size in percentage
				if ( preg_match('/[0-9]{1,3}%/', $this->size['watermark']) )
				{
					$size = $this->size['watermark'] / 100;
					$this->current_size['watermark']['width'] = $this->current_size['watermark']['width'] * $size;
					$this->current_size['watermark']['height'] = $this->current_size['watermark']['height'] * $size;
				}
				elseif ( $this->size['watermark'] === 'full' )
				{
					$waterMarkWidth = $waterMarkDestWidth = $this->current_size['watermark']['width'];
					$waterMarkHeight = $waterMarkDestHeight = $this->current_size['watermark']['height'];
					$origHeight = $this->current_size['image']['height'];
					$origWidth = $this->current_size['image']['width'];

					if ( $waterMarkWidth > $origWidth * 1.05 && $waterMarkHeight > $origHeight * 1.05 )
					{
						// both are already larger than the original by at least 5%...
						// we need to make the watermark *smaller* for this one.
						// where is the largest difference?
						$wdiff = $waterMarkDestWidth - $origWidth;
						$hdiff = $waterMarkDestHeight - $origHeight;

						if($wdiff > $hdiff)
						{
							// the width has the largest difference - get percentage
							$sizer = ($wdiff / $waterMarkDestWidth) - 0.05;
						}
						else
						{
							$sizer=($hdiff / $waterMarkDestHeight) - 0.05;
						}
						$waterMarkDestWidth -= $waterMarkDestWidth * $sizer;
						$waterMarkDestHeight -= $waterMarkDestHeight * $sizer;
					}
					else
					{
						// the watermark will need to be enlarged for this one
						// where is the largest difference?
						$wdiff = $origWidth - $waterMarkDestWidth;
						$hdiff = $origHeight - $waterMarkDestHeight;

						if($wdiff > $hdiff)
						{
							// the width has the largest difference - get percentage
							$sizer = ($wdiff / $waterMarkDestWidth) + 0.05;
						}
						else
						{
							$sizer = ($hdiff / $waterMarkDestHeight) + 0.05;
						}
						$waterMarkDestWidth += $waterMarkDestWidth * $sizer;
						$waterMarkDestHeight += $waterMarkDestHeight * $sizer;
					}
					$this->current_size['watermark']['width'] = $waterMarkDestWidth;
					$this->current_size['watermark']['height'] = $waterMarkDestHeight;
				}
			}
		}
	}

	/**
	 * Calculates the position using the 'position' and 'margin' vars
	 * @return void
	 */
	private function getWatermarkPosition()
	{
		if ( is_array($this->position) )
			$position = $this->position['string'];
		else
			$position = $this->position;

		if ( $this->size['watermark'] == 'full' )
			$position = 'center center';

		// Horizontal
		if ( preg_match('/right/', $position) )
			$x = $this->current_size['image']['width'] - $this->current_size['watermark']['width'] + $this->margin['x'];
		elseif ( preg_match('/left/', $position) )
			$x = 0  + $this->margin['x'];
		elseif ( preg_match('/center/', $position) )
			$x = $this->current_size['image']['width'] / 2 - $this->current_size['watermark']['width'] / 2  + $this->margin['x'];

		// Vertical
		if ( preg_match('/bottom/', $position) )
			$y = $this->current_size['image']['height'] - $this->current_size['watermark']['height']  + $this->margin['y'];
		elseif ( preg_match('/top/', $position) )
			$y = 0  + $this->margin['y'];
		elseif ( preg_match('/center/', $position) )
			$y = $this->current_size['image']['height'] / 2 - $this->current_size['watermark']['height'] / 2  + $this->margin['y'];

		if ( !isset($x) || !isset($y) )
			throw new Exception('Watermark position has been set wrong');


		$this->position = array('x' => $x,'y' => $y,'string' => $position);
	}

	/**
	 *	Reseizes a png image preserving transparency
	 *	@return image resource
	 */
	private function resize_png_image($src_image, $width, $height)
	{
		// Get sizes
		if ( !$src_width = imagesx($src_image) )
			throw new Exception('Couldn\'t get image width');

		if ( !$src_height = imagesy($src_image) )
			throw new Exception('couldn\'t get image height');

		// Get percentage and destiny size
		$percentage = (double)$width / $src_width;
		$dest_height = round($src_height * $percentage) + 1;
		$dest_width = round($src_width * $percentage) + 1;

		if ( $dest_height > $height )
		{
		    // if the width produces a height bigger than we want, calculate based on height
		    $percentage = (double)$height / $src_height;
		    $dest_height = round($src_height * $percentage) + 1;
		    $dest_width = round($src_width * $percentage) + 1;
		}

		if ( !$dest_image = imagecreatetruecolor($dest_width - 1, $dest_height - 1) )
			throw new Exception('imagecreatetruecolor could not create the image');

		if ( !imagealphablending($dest_image, false) )
			throw new Exception('could not apply imagealphablending');

		if ( !imagesavealpha($dest_image, true) )
			throw new Exception('could not apply imagesavealpha');

		if ( !imagecopyresampled($dest_image, $src_image, 0, 0, 0, 0, $dest_width, $dest_height, $src_width, $src_height) )
			throw new Exception('could not copy resampled image');

		if ( !imagedestroy($src_image) )
			throw new Exception('could not destroy the image');

		return $dest_image;
	}

	private function imgRotate($src_image, $angle, $bgcolor, $ignore_transparent = 0)
	{
		if ( function_exists("imagerotate") )
			return imagerotate($src_image, $angle, $bgcolor, $ignore_transparent);
		else
			return $this->imagerotateEquivalent($src_image, $angle, $bgcolor, $ignore_transparent);
	}


	// extracted from http://php.net/manual/es/function.imagerotate.php comments
	private function imagerotateEquivalent($src_image, $angle, $bgcolor, $ignore_transparent = 0)
	{
		function rotateX($x, $y, $theta)
		{
			return $x * cos($theta) - $y * sin($theta);
		}

		function rotateY($x, $y, $theta)
		{
			return $x * sin($theta) + $y * cos($theta);
		}

		$srcw = imagesx($src_image);
		$srch = imagesy($src_image);

		//Normalize angle
		$angle %= 360;
		//Set rotate to clockwise
		$angle = -$angle;

		if ( $angle == 0 )
		{
			if ($ignore_transparent == 0)
				imagesavealpha($src_image, true);

			return $src_image;
		}

		// Convert the angle to radians
		$theta = deg2rad ($angle);

		//Standart case of rotate
		if ( (abs($angle) == 90) || (abs($angle) == 270) )
		{
			$width = $srch;
			$height = $srcw;
			if ( ($angle == 90) || ($angle == -270) )
			{
				$minX = 0;
				$maxX = $width;
				$minY = -$height+1;
				$maxY = 1;
			}
			else if ( ($angle == -90) || ($angle == 270) )
			{
				$minX = -$width+1;
				$maxX = 1;
				$minY = 0;
				$maxY = $height;
			}
		}
		else if ( abs($angle) === 180 )
		{
			$width = $srcw;
			$height = $srch;
			$minX = -$width+1;
			$maxX = 1;
			$minY = -$height+1;
			$maxY = 1;
		}
		else
		{
			// Calculate the width of the destination image.
			$temp = array (
				rotateX(0, 0, 0-$theta),
				rotateX($srcw, 0, 0-$theta),
				rotateX(0, $srch, 0-$theta),
				rotateX($srcw, $srch, 0-$theta)
			);
			$minX = floor(min($temp));
			$maxX = ceil(max($temp));
			$width = $maxX - $minX;

			// Calculate the height of the destination image.
			$temp = array (
				rotateY(0, 0, 0-$theta),
				rotateY($srcw, 0, 0-$theta),
				rotateY(0, $srch, 0-$theta),
				rotateY($srcw, $srch, 0-$theta)
			);
			$minY = floor(min($temp));
			$maxY = ceil(max($temp));
			$height = $maxY - $minY;
		}

		$destimg = imagecreatetruecolor($width, $height);
		if ( $ignore_transparent == 0 )
		{
			$bgcolor = imagecolorallocatealpha($destimg, 0, 0, 0, 127);
			imagefill($destimg, 0, 0, $bgcolor);
			imagesavealpha($destimg, true);
		}

		// sets all pixels in the new image
		for ( $x = $minX; $x < $maxX; $x++ )
		{
			for ( $y = $minY; $y < $maxY; $y++ )
			{
				// fetch corresponding pixel from the source image
				$srcX = round(rotateX($x, $y, $theta));
				$srcY = round(rotateY($x, $y, $theta));
				if($srcX >= 0 && $srcX < $srcw && $srcY >= 0 && $srcY < $srch)
				{
					$color = imagecolorat($src_image, $srcX, $srcY);
				}
				else
				{
					$color = $bgcolor;
				}
				imagesetpixel($destimg, $x-$minX, $y-$minY, $color);
			}
		}
		return $destimg;
	}

	private function error($exception)
	{
		if ( $this->debug == true )
		{
			$this->errors[] = array(
				'message' => $exception->getMessage(),
				'code' => $exception->getCode(),
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
				'trace' => array('array' => $exception->getTrace(), 'string' => $exception->getTraceAsString())
			);
		}
		else
		{
			$this->errors[] = $exception->getMessage();
		}
	}
}
