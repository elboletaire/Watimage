<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
/**
 * 
 * @author Òscar Casajuana Alonso <elboletaire@underave.net>
 * @version 0.2 2011/04/16 
 *		Changes: now works with Exceptions. mime_content_type function has been removed. Added flip function & minor bugfixes)
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
 // Uncomment next comment to use as CakePHP Component
class Watermark//Component extends Object
{
	

	/**
	 * Sizes especified by user to enlarge or 
	 * reduce watermark and / or final image
	 * @var
	 */
	public $size;

	public $errors;
	
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
	private $quality = 80;
	private $file;
	private $extension;
	
	public function __construct($file = null, $watermark = null)
	{
		if ( !empty($file) ) 
			$this->setImage($file);
		
		if ( !empty($watermark) )
			$this->setWatermark($watermark);
	}
	
	/**
	 * Set image options
	 * @param array $options [optional]
	 */
	public function setImage($file)
	{
		try
		{
			if ( empty($file) )
				throw new Exception('Empty file');
			if ( file_exists($file) )
			{
				$this->file['image'] = $file;
			}
			else throw new Exception('File "' . $file . '" does not exist');
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
	
	/**
	 * Set watermark options
	 * @param mixed $options [optional] you can set the watermark without options 
	 *									or you can set an array of options like:
	 *									$options = array(
	 *										'file' => 'watermark.png',
	 *										'position' => 'bottom center',
	 *										'margin' => array('20', '10')
	 *									);
	 * @return true on success; false on failure
	 */
	public function setWatermark($options = array())
	{
		try {
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
					if ( !is_array($options['margin']) || count($options['margin']) == 1 )
					{
						$this->margin['x'] = $this->margin['y'] = $options['margin'];
					}
					else
					{
						foreach ($options['margin'] as $key => $margin)
						{
							if ($key === 0)
								$this->margin['x'] = $margin;
							elseif ($key === 1) 
								$this->margin['y'] = $margin;
							else
								$this->margin[$key] = $margin;
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
	 *	@param array $options = array('type' => 'resizemin|resizecrop|resize|crop', 'size' => array('x' => 2000, 'y' => 500))
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
				if ( !preg_match('/resize(min|crop)?|crop/', $this->resize['type']) )
				{
					throw new Exception('Specified resize type "'.$this->resize['type'].'" does not exist');
				}
			}
			else
				throw new Exception('You must specify the type of resize (resizecrop|crop|resize|resizemin)');
			
			if ( !empty($options['size']) )
			{
				if( is_array($options['size']) )
				{
					if ( array_key_exists('x', $options['size']) && array_key_exists('y', $options) )
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
				throw new Exception('You must specify the size to being resized');
			// Resize image!
			switch ( $this->resize['type'] )
			{
				/**
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
					
					if ( !$dest_image = imagecreatetruecolor($new_x, $new_y) )
					{
						throw new Exception('Could not create tempfile while resizing');
					}
					if ( !imagecopyresampled($dest_image, $this->image, 0, 0, 0, 0, $new_x, $new_y, $this->current_size['image']['width'], $this->current_size['image']['height']) )
					{
						throw new Exception('Could not copy resampled image while resizing');
					}
					$this->current_size['image']['width'] = $new_x;
					$this->current_size['image']['height'] = $new_y;
				break;
				/**
				 * Maintains aspect ratio but resizes the image so that once
				 * one side meets its max width or max height condition, it stays at that size
				 * (thus one side will be larger)
				 */	
				case 'resizemin':
					$ratioX = $this->resize['size']['x'] / $this->current_size['image']['width'];
					$ratioY = $this->resize['size']['y'] / $this->current_size['image']['height'];

					if ( ($this->current_size['image']['width'] == $this->resize['size']['x']) && ($this->current_size['image']['height'] == $this->resize['size']['y']) )
					{
						$new_x = $this->current_size['image']['width'];
						$new_y = $this->current_size['image']['height'];
					}
					else if ( ($ratioX * $this->current_size['image']['height']) > $this->resize['size']['y'] )
					{
						$new_x = $this->resize['size']['x'];
						$new_y = ceil($ratioX * $this->current_size['image']['height']);
					}
					else
					{
						$new_x = ceil($ratioY * $this->current_size['image']['width']);		
						$new_y = $this->resize['size']['y'];
					}

					if ( !$dest_image = imagecreatetruecolor($new_x,$new_y) )
					{
						throw new Exception('Could not create tempfile while resizing');
					}
					if ( !imagecopyresampled($dest_image, $this->image, 0, 0, 0, 0, $new_x, $new_y, $this->current_size['image']['width'], $this->current_size['image']['height']) )
					{
						throw new Exception('Could not copy resampled image while resizing');
					}
					$this->current_size['image']['width'] = $new_x;
					$this->current_size['image']['height'] = $new_y;
				break;
				/**
				 * resize to max, then crop to center
				 */
				case 'resizecrop':
					$ratioX = $this->resize['size']['x'] / $this->current_size['image']['width'];
					$ratioY = $this->resize['size']['y'] / $this->current_size['image']['height'];

					if ( $ratioX < $ratioY )
					{ 
						$new_x = round(($this->current_size['image']['width'] - ($this->resize['size']['x'] / $ratioY)) / 2);
						$new_y = 0;
						$this->current_size['image']['width'] = round($this->resize['size']['x'] / $ratioY);
						$this->current_size['image']['height'] = $this->current_size['image']['height'];
					}
					else
					{ 
						$new_x = 0;
						$new_y = round(($this->current_size['image']['height'] - ($this->resize['size']['y'] / $ratioX)) / 2);
						$this->current_size['image']['height'] = round($this->resize['size']['y'] / $ratioX);
					}
					
					if ( !$dest_image = imagecreatetruecolor($this->resize['size']['x'], $this->resize['size']['y']) )
					{
						throw new Exception('Could not create tempfile while resizing');
					}
					if ( !imagecopyresampled($dest_image, $this->image, 0, 0, $new_x, $new_y, $this->resize['size']['x'], $this->resize['size']['y'], $this->current_size['image']['width'], $this->current_size['image']['height']) )
					{
						throw new Exception('Could not copy resampled image while resizing');
					}
					$this->current_size['image']['width'] = $this->resize['size']['x'];
					$this->current_size['image']['height'] = $this->resize['size']['y'];
				break;
				
				/**
				 * a straight centered crop
				 */
				case 'crop':
					$startY = ($this->current_size['image']['height'] - $this->resize['size']['y'])/2;
					$startX = ($this->current_size['image']['width'] - $this->resize['size']['x'])/2;

					if ( !$dest_image = imagecreatetruecolor($this->resize['size']['x'], $this->resize['size']['y']) )
					{
						throw new Exception('Could not create tempfile while resizing');
					}
					if ( !imagecopyresampled($dest_image, $this->image, 0, 0, $startX, $startY, $this->resize['size']['x'], $this->resize['size']['y'], $this->resize['size']['x'], $this->resize['size']['y']) )
					{
						throw new Exception('Could not copy resampled image while resizing');
					}
					$this->current_size['image']['width'] = $this->resize['size']['x'];
					$this->current_size['image']['height'] = $this->resize['size']['y'];
				break;
			}
			
			if ( isset($this->file['watermark']) )
			{
				$this->getWatermarkPosition();
			}
			if ( !imagedestroy($this->image) )
			{
				throw new Exception('Could not destroy tempfile image');
			}
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
	 *	Rotates an image
	 *	@param mixed $options. You can specify directly the degrees or you can pass an array with degrees and bgcolor
	 */
	public function rotateImage($options = array())
	{
		if ( !empty($this->errors) ) return false;

		try {
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
				{
					$this->rotate['bgcolor'] = $options['bgcolor'];
				}
				else
				{
					$this->rotate['bgcolor'] = -1;
				}
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
	 *	Shortcut for rotateImage
	 */
	public function rotate($options = array())
	{
		return $this->rotateImage($options);
	}

	public function applyWatermark()
	{
		if ( !empty($this->errors) ) return false;

		try {
			$this->getWatermarkPosition();
			if ( !$this->watermark = $this->createImage($this->file['watermark']) )
			{
				throw new Exception('Could not create watermark image');
			}
			
			if ( $this->resize === false && $this->rotate === false )
			{
				//Little trick for saving transparency
				if ( !$dest_image = imagecreatetruecolor($this->current_size['image']['width'], $this->current_size['image']['height']) )
				{
					throw new Exception('Could not create tempfile while resizing');
				}
				if ( !imagecopyresampled($dest_image, $this->image, 0, 0, 0, 0, $this->current_size['image']['width'], $this->current_size['image']['height'], $this->current_size['image']['width'], $this->current_size['image']['height']) )
				{
					throw new Exception('Could not copy resampled image while resizing');
				}
				
				$this->image = $dest_image;
			}
		
			if ( !empty($this->size) )
			{
				$this->watermark = $this->resize_png_image($this->watermark, $this->current_size['watermark']['width'], $this->current_size['watermark']['width']);
			}
			if ( !imagecopy($this->image, $this->watermark, $this->position['x'], $this->position['y'], 0, 0, $this->current_size['watermark']['width'], $this->current_size['watermark']['height']) )
			{
				throw new Exception('Could not apply watermark to image');
			}
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
	 */
	public function flip($type = 'horizontal')
	{
		try 
		{
			$size_x = imagesx($this->image);
			$size_y = imagesy($this->image);
			$temp = imagecreatetruecolor($size_x, $size_y);
			if ( $this->extension['image'] == 'gif' || $this->extension['image'] == 'png' ) {
				// preserve transparency
				imagealphablending($temp, false);
			}
			if ( $type == 'horizontal' )
				$resampled = imagecopyresampled($temp, $this->image, 0, 0, ($size_x - 1), 0, $size_x, $size_y, 0 - $size_x, $size_y);

			elseif ( $type == 'vertical' )
				$resampled = imagecopyresampled($temp, $this->image, 0, 0, 0, ($size_y - 1), $size_x, $size_y, $size_x, 0 - $size_y);
			
			elseif ( $type == 'both') // same as $this->rotate(180)
				$resampled = imagecopyresampled($temp, $this->image, 0, 0, ($size_x - 1), ($size_y - 1), $size_x, $size_y, 0- $size_x, 0 - $size_y);
			
			else
				throw new Exception('Invalid flip type (horizontal|vertical|both)');
			
			if ( !$resampled ) {
				throw new Exception('Image could not be flipped');
			}
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
				if ( preg_match('/bmp|tiff|jpg|jpeg/', $this->extension['image']) )
					$this->output = 'image/jpeg';
				elseif ( $this->extension['image'] = 'gif' )
					$this->output = 'image/gif';
				else
					$this->output = 'image/png';
			}
			if ( is_null($path) )
			{
				header('Content-type: ' . $this->output);
			}
			
			// Output / save image
			switch($this->output)
			{
				case 'image/png':
					(int)$this->quality /= 10;
					if ( !imagepng($this->image, $path, $this->quality) )
					{
						throw new Exception('could not generate png output image');
					}
				break;
				case 'image/jpeg':
					if ( !imagejpeg($this->image, $path, $this->quality) )
					{
						throw new Exception('could not generate output jpeg image');
					}
				break;
				case 'image/gif':
					if ( !imagegif($this->image, $path, $this->quality) )
					{
						throw new Exception('could not generate output gif image');
					}
				break;
			}

			// Destroy image
			if ( !imagedestroy($this->image) )
			{
				throw new Exception('could not destroy image tempfile');
			}
			if ( isset($this->file['watermark']) )
			{
				if ( !imagedestroy($this->watermark) )
				{
					throw new Exception('could not destroy watermark tempfile');
				}
			}
			unset($this->file);
		}
		catch ( Exception $e )
		{
			$this->error($e);
			return false;
		}
		return true;
	}

	/**
	 *	Creates an image from string
	 */
	private function createImage($file)
	{
		$ihandle = fopen($file, 'r');
		$image = fread($ihandle, filesize($file));
		fclose($ihandle);

		if ( false === ( $img = imagecreatefromstring($image) ) )
		{
			throw new Exception("Image not valid");
		}
		return $img;
	}

	/**
	 *	Applies some values to image for handling transparency
	 */
	private function handleTransparentImage()
	{
		if ( preg_match('/gif|png/', $this->extension['image']) )
		{
			if ( !imagealphablending($this->image, true) ) {
				throw new Exception("Can't apply imagealphablending to image");
			}
			if ( $this->current_size['image']['format'] == 3 )
			{
				// Save alpha for transparent png files
				if ( !imagealphablending($this->image, false) ) {
					throw new Exception("Can't apply imagealphablending to image");
				}
				if ( !imagesavealpha($this->image, true) ) {
					throw new Exception("Can't apply imagesavealpha to image");
				}
			}
		}
	}

	private function getFileExtension( &$file )
	{
		$f = pathinfo($file);
		if ( empty($f['extension']) ) {
			throw new Exception("Can't get file extension for file $file");
		}
		return $f['extension'];
	}

	/**
	 * Obtains image sizes
	 * @return 
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
	 * @return 
	 */
	private function getWatermarkPosition()
	{
		if ( is_array($this->position) )
			$position = $this->position['string'];
		else 
			$position = $this->position;
		
		if( $this->size['watermark'] == 'full' )
		{
			$position = 'center center';
		}
		
		// Horizontal
		if ( preg_match('/right/', $position) )
		{
			$x = $this->current_size['image']['width'] - $this->current_size['watermark']['width'] + $this->margin['x'];
		}
		elseif ( preg_match('/left/', $position) )
		{
			$x = 0  + $this->margin['x'];
		}
		elseif ( preg_match('/center/', $position) )
		{
			$x = $this->current_size['image']['width'] / 2 - $this->current_size['watermark']['width'] / 2  + $this->margin['x'];
		}
		
		// Vertical
		if ( preg_match('/bottom/', $position) )
		{
			$y = $this->current_size['image']['height'] - $this->current_size['watermark']['height']  + $this->margin['y'];
		}
		elseif ( preg_match('/top/', $position) )
		{
			$y = 0  + $this->margin['y'];
		}
		elseif ( preg_match('/center/', $position) )
		{
			$y = $this->current_size['image']['height'] / 2 - $this->current_size['watermark']['height'] / 2  + $this->margin['y'];
		}
		if ( !isset($x) || !isset($y) )
			throw new Exception('Watermark position has been set wrong');

		$this->position = array('x' => $x,'y' => $y,'string' => $position);
	}
	
	/**
	 *	Reseizes a png image preserving transparency
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

	
	// from http://php.net/manual/es/function.imagerotate.php comments
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

		if ( $angle == 0 ) {
			if ($ignore_transparent == 0) {
				imagesavealpha($src_image, true);
			}
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
			$temp = imagecolorallocate($destimg, 255, 255, 255);
			imagefill($destimg, 0, 0, $temp);
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
		if ( $this->debug == true ) {
			$this->errors[] = array(
				'message' => $exception->getMessage(),
				'code' => $exception->getCode(),
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
				'trace' => array('array' => $exception->getTrace(), 'string' => $exception->getTraceAsString())
			);
		} else {
			$this->errors[] = $exception->getMessage();
		}
	}
}