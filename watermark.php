<?php
/**
 * @author Òscar Casajuana Alonso <elboletaire@underave.net>
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
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 */
class WatermarkComponent extends Object
{
	
	var $file;

	/**
	 * Sizes especified by user to enlarge or 
	 * reduce watermark and / or final image
	 * @var
	 */
	public $size;
	
	public $errors;
	
	private $image;
	
	private $watermark = FALSE;
	
	private $output;

	private $quality = 80;
	/**
	 * Sizes of image files 
	 * @private array $sizes
	 */
	private $sizes;
	
	/**
	 * Resize values
	 * @private array $resize
	 */
	private $resize = FALSE;
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
	
	private $rotate = FALSE;
	
	/**
	 * Apply Watermark after or before rotate? 
	 **/
	private $awaobr;
	private $riaobr;
	
	
	private $mime;
	
	/**
	 * Set image options
	 * @param array $options [optional]
	 */
	function setImage($file)
	{
		if (!empty($file))
		{
			if(file_exists($file))
			{
				$this->file['image'] = $file;
			}
			else return $this->error('Watermark::setImage: File "' . $file . '" does not exist');
			
			// Obtain MIME type
			$this->mime['image'] = $this->getMime($this->file['image']);
			// Obtain file sizes
			$this->getSizes();
			
			if (!$this->image = $this->createFrom($this->file['image']))
			{
				return $this->error('Watermark::setImage: could not create image from file ' . $this->file['image']);
			}
			if($this->mime['image'] == 'image/png' || $this->mime['image'] == 'image/gif')
			{	
				if (!imagealphablending($this->image, TRUE))
				{
					return $this->error('Watermark::setImage: failed applying imagealphablending to image');
				}
				// Save alpha for transparent png files
				if($this->sizes['image']['format'] == 3)
				{
					if (!imagealphablending($this->image, FALSE))
					{
						return $this->error('Watermark::setImage: failed applying imagealphablending to image');
					}
					if (!imagesavealpha($this->image, TRUE))
					{
						return $this->error('Watermark::setImage: failed applying imagesavealpha to image');
					}
				}
			}
		}
	}
	
	/**
	 * Set watermark options
	 * @param array $options [optional]
	 * @return 
	 */
	public function setWatermark($options = array())
	{
		if(isset($options) && !empty($options))
		{
			if (!is_array($options))
			{
				$this->file['watermark'] = $options;
			}
			else
			{
				// Image file
				if(isset($options['file']) && !empty($options['file']))
				{
					$this->file['watermark'] = $options['file'];
				}
				else
				{
					return $this->error('Watermark::setWatermark: parameter \'file\' needed');
				}

				// Position
				if(isset($options['position']) && !empty($options['position']))
				{
					$this->position = $options['position'];
				}

				// Watermark margins
				if(isset($options['margin']) && !empty($options['margin']))
				{
					if(!is_array($options['margin']) || count($options['margin']) == 1)
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
				if (isset($options['size']) && !empty($options['size']))
				{
					$this->size['watermark'] = $options['size'];
				}
				else
				{
					$this->size['watermark'] = '100%';
				}
			}
			
			if(!file_exists($this->file['watermark']))
			{
				return $this->error('Watermark::setWatermark: specified file does not exist');
			}
			$this->getSizes();
		}
	}
	
	public function resize($options = array())
	{
		if(isset($options['type']) && !empty($options['type']))
		{
			$this->resize['type'] = $options['type'];
			
			if(!preg_match('/resize(min|crop)?|crop/', $this->resize['type']))
			{
				return $this->error('Watermark::resize: specified resize type "'.$this->resize['type'].'" does not exist');
			}
			if(isset($options['size']) && !empty($options['size']))
			{
				if(is_array($options['size']))
				{
					if(array_key_exists('x', $options['size']) && array_key_exists('y', $options))
					{
						$this->resize['size'] = $options['size'];
					}
					else
					{
						if(count($options['size']) == 2)
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
				return $this->error('Watermark::resize: parameter \'size\' needed');
			}
		}
		else
		{
			return $this->error('Watermark::resize: you should specify the resize type');
		}
		// Resize image
		if($this->resize !== false)
		{
			switch ($this->resize['type'])
			{
			 	/**
			  	 * Maintains the aspect ratio of the image and makes sure that it fits
			  	 * within the max width and max height (thus some side will be smaller)
			  	 */
				case 'resize':
					if ($this->sizes['image']['width'] > $this->resize['size']['x'] || $this->sizes['image']['height'] > $this->resize['size']['x'])
					{
						if ($this->sizes['image']['width'] > $this->sizes['image']['height'])
						{
							$newX = $this->resize['size']['x'];
							$newY = ($this->sizes['image']['height']*$newX)/$this->sizes['image']['width'];
						}
						else if ($this->sizes['image']['width'] < $this->sizes['image']['height'])
						{
							$newY = $this->resize['size']['x'];
							$newX = ($newY*$this->sizes['image']['width'])/$this->sizes['image']['height'];
						}
						else if ($this->sizes['image']['width'] == $this->sizes['image']['height'])
						{
							$newX = $newY = $this->resize['size']['x'];
						}
					}
					else
					{
						$newX = $this->sizes['image']['width'];
						$newY = $this->sizes['image']['height'];
					}
					
					if (!$dstImg = imagecreatetruecolor($newX, $newY))
					{
						return $this->error('Watermark::generate: could not create tempfile image while in \'resize\'');
					}
					if (!imagecopyresampled($dstImg, $this->image, 0, 0, 0, 0, $newX, $newY, $this->sizes['image']['width'], $this->sizes['image']['height']))
					{
						return $this->error('Watermark::generate: could not copy resampled image while in \'resize\'');
					}
					$this->sizes['image']['width'] = $newX;
					$this->sizes['image']['height'] = $newY;
					break;
				/**
				 * Maintains aspect ratio but resizes the image so that once
				 * one side meets its max width or max height condition, it stays at that size
				 * (thus one side will be larger)
				 */	
				case 'resizemin':
					$ratioX = $this->resize['size']['x'] / $this->sizes['image']['width'];
					$ratioY = $this->resize['size']['y'] / $this->sizes['image']['height'];

					if (($this->sizes['image']['width'] == $this->resize['size']['x']) && ($this->sizes['image']['height'] == $this->resize['size']['y']))
					{
						$newX = $this->sizes['image']['width'];
						$newY = $this->sizes['image']['height'];
					}
					else if (($ratioX * $this->sizes['image']['height']) > $this->resize['size']['y'])
					{
						$newX = $this->resize['size']['x'];
						$newY = ceil($ratioX * $this->sizes['image']['height']);
					}
					else
					{
						$newX = ceil($ratioY * $this->sizes['image']['width']);		
						$newY = $this->resize['size']['y'];
					}

					if (!$dstImg = imagecreatetruecolor($newX,$newY))
					{
						return $this->error('Watermark::generate: could not create tempfile image while in \'resizemin\'');
					}
					if (!imagecopyresampled($dstImg, $this->image, 0, 0, 0, 0, $newX, $newY, $this->sizes['image']['width'], $this->sizes['image']['height']))
					{
						return $this->error('Watermark::generate: could not copy resampled image while in \'resizemin\'');
					}
					$this->sizes['image']['width'] = $newX;
					$this->sizes['image']['height'] = $newY;
					break;
				/**
				 * resize to max, then crop to center
				 */
				case 'resizecrop':
					$ratioX = $this->resize['size']['x'] / $this->sizes['image']['width'];
					$ratioY = $this->resize['size']['y'] / $this->sizes['image']['height'];

					if ($ratioX < $ratioY)
					{ 
						$newX = round(($this->sizes['image']['width'] - ($this->resize['size']['x'] / $ratioY))/2);
						$newY = 0;
						$this->sizes['image']['width'] = round($this->resize['size']['x'] / $ratioY);
						$this->sizes['image']['height'] = $this->sizes['image']['height'];
					}
					else
					{ 
						$newX = 0;
						$newY = round(($this->sizes['image']['height'] - ($this->resize['size']['y'] / $ratioX))/2);
						$this->sizes['image']['height'] = round($this->resize['size']['y'] / $ratioX);
					}
					
					if (!$dstImg = imagecreatetruecolor($this->resize['size']['x'], $this->resize['size']['y']))
					{
						return $this->error('Watermark::generate: could not create tempfile image while in \'resizecrop\'');	
					}
					if (!imagecopyresampled($dstImg, $this->image, 0, 0, $newX, $newY, $this->resize['size']['x'], $this->resize['size']['y'], $this->sizes['image']['width'], $this->sizes['image']['height']))
					{
						return $this->error('Watermark::generate: could not copy resampled image while in \'resizecrop\'');
					}
					$this->sizes['image']['width'] = $this->resize['size']['x'];
					$this->sizes['image']['height'] = $this->resize['size']['y'];
					break;
				
				/**
				 * a straight centered crop
				 */
				case 'crop':
					$startY = ($this->sizes['image']['height'] - $this->resize['size']['y'])/2;
					$startX = ($this->sizes['image']['width'] - $this->resize['size']['x'])/2;

					if (!$dstImg = imagecreatetruecolor($this->resize['size']['x'], $this->resize['size']['y']))
					{
						return $this->error('Watermark::generate: could not create tempfile image while in \'crop\'');
					}
					if (!imagecopyresampled($dstImg, $this->image, 0, 0, $startX, $startY, $this->resize['size']['x'], $this->resize['size']['y'], $this->resize['size']['x'], $this->resize['size']['y']))
					{
						return $this->error('Watermark::generate: could not copy resampled image while in \'crop\'');
					}
					$this->sizes['image']['width'] = $this->resize['size']['x'];
					$this->sizes['image']['height'] = $this->resize['size']['y'];
					break;
			}
			if($this->file['watermark'])
			{
				$this->getWatermarkPosition();
			}
			if (!imagedestroy($this->image))
			{
				return $this->error('Watermark::generate: could not destroy image tempfile');
			}
			$this->image = $dstImg;
		}
	}
	
	
	public function rotateImage($options = array())
	{
		if (isset($options) && !empty($options))
		{
			if (!is_array($options))
			{
				$this->rotate['degrees'] = $options;
				// Take transparent as default background color if it's not specified
				$this->rotate['bgcolor'] = 0;
			}
			else
			{
				$this->rotate['degrees'] = $options['degrees'];
				if (isset($options['bgcolor']) && !empty($options['bgcolor']))
				{
					$this->rotate['bgcolor'] = $options['bgcolor'];
				}
				else
				{
					$this->rotate['bgcolor'] = 0;
				}
			}
		}
		$this->image = $this->imgRotate($this->image, $this->rotate['degrees'], $this->rotate['bgcolor']);
		// Obtain new image dimensions
		$this->sizes['image']['width'] = imagesx($this->image);
		$this->sizes['image']['height'] = imagesy($this->image);
	}
	
	public function applyWatermark()
	{
		$this->getWatermarkPosition();
		if (!$this->watermark = $this->createFrom($this->file['watermark']))
		{
			return $this->error('Watermark::generate: could not create watermark image');
		}
		
		if(isset($this->size) && !empty($this->size))
		{
			if (!$this->watermark = $this->resize_png_image($this->watermark, $this->sizes['watermark']['width'], $this->sizes['watermark']['width']))
			{
				return $this->error('Watermark::generate: could not resize watermark');
			}
		}
		if (!imagecopy($this->image, $this->watermark, $this->position['x'], $this->position['y'], 0, 0, $this->sizes['watermark']['width'], $this->sizes['watermark']['height']))
		{
			return $this->error('Watermark::generate: could not apply watermark to image');
		}
	}

	public function generate($path = NULL, $output = NULL)
	{
		
		if (!empty($output))
		{
			$this->output = $output;
		}
		else
		{
			$this->output = $this->mime['image'];
		}
		if(is_null($path))
		{
			header('Content-type: ' . $this->output);
		}
		// Output / save image
		switch($this->output)
		{
			case'image/png':
				(int)$this->quality /= 10;
				if (!imagepng($this->image, $path, $this->quality))
				{
					return $this->error('Watermark::generate: could not generate png output image');
				}
				break;
			case 'image/jpeg':
				if (!imagejpeg($this->image, $path, $this->quality))
				{
					return $this->error('Watermark::generate: could not generate output jpeg image');
				}
				break;
			case 'image/gif':
				if (!imagegif($this->image, $path, $this->quality))
				{
					return $this->error('Watermark::generate: could not generate output gif image');
				}
				break;
		}

		// Destroy image
		if (!imagedestroy($this->image))
		{
			return $this->error('Watermark::generate: could not destroy image tempfile');
		}
		if(isset($this->file['watermark']))
		{
			if (!imagedestroy($this->watermark))
			{
				return $this->error('Watermark::generate: could not destroy watermark tempfile');
			}
		}
		return true;
	}

	private function createFrom($file)
	{
		$mimetype = $this->getMime($file);
		switch($mimetype)
		{
			case 'image/png':
				$create = imagecreatefrompng($file);
				break;
			case 'image/gif':
				$create = imagecreatefromgif($file);
				break;
			case 'image/jpeg':
				$create = imagecreatefromjpeg($file);
				break;
		}
		return $create;
	}
	
	private function getMime($file)
	{
		$mimetype = mime_content_type($file);
		if($mimetype == 'text/plain')
		{
			$f = escapeshellarg($file);
			$mimetype = trim(`file -bi $f`);
		}
		return $mimetype;
	}

	/**
	 * Obtains image sizes
	 * @return 
	 */
	private function getSizes()
	{
		if (!empty($this->file['image']))
		{
			list($width, $height, $format) = getimagesize($this->file['image']);
			$this->sizes['image'] = compact('width','height','format');
		}
		
		if (!empty($this->file['watermark']))
		{
			list($width, $height, $format) = getimagesize($this->file['watermark']);
			$this->sizes['watermark'] = compact('width','height','format');
			
			if(isset($this->size['watermark']) && !empty($this->size['watermark']))
			{
				// Size in percentage
				if (preg_match('/[0-9]{1,3}%/',$this->size['watermark']))
				{
					$size = $this->size['watermark'] / 100;
					$this->sizes['watermark']['width'] = $this->sizes['watermark']['width'] * $size;
					$this->sizes['watermark']['height'] = $this->sizes['watermark']['height'] * $size; 
				}
				elseif ($this->size['watermark'] === 'full')
				{
					$waterMarkWidth = $waterMarkDestWidth = $this->sizes['watermark']['width'];
					$waterMarkHeight = $waterMarkDestHeight = $this->sizes['watermark']['height'];
					$origHeight = $this->sizes['image']['height'];
					$origWidth = $this->sizes['image']['width'];
					
					if($waterMarkWidth > $origWidth*1.05 && $waterMarkHeight > $origHeight*1.05)
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
					$this->sizes['watermark']['width'] = $waterMarkDestWidth;
					$this->sizes['watermark']['height'] = $waterMarkDestHeight;
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
		if(is_array($this->position))
			$position = $this->position['string'];
		else $position = $this->position;
		if($this->size['watermark'] == 'full')
		{
			$position = 'center center';
		}
		
		// Horizontal
		if (preg_match('/right/', $position))
		{
			$x = $this->sizes['image']['width'] - $this->sizes['watermark']['width'] + $this->margin['x'];
		}
		elseif (preg_match('/left/', $position))
		{
			$x = 0  + $this->margin['x'];
		}
		elseif (preg_match('/center/', $position))
		{
			$x = $this->sizes['image']['width'] / 2 - $this->sizes['watermark']['width'] / 2  + $this->margin['x'];
		}
		
		// Vertical
		if (preg_match('/bottom/', $position))
		{
			$y = $this->sizes['image']['height'] - $this->sizes['watermark']['height']  + $this->margin['y'];
		}
		elseif (preg_match('/top/', $position))
		{
			$y = 0  + $this->margin['y'];
		}
		elseif (preg_match('/center/', $position))
		{
			$y = $this->sizes['image']['height'] / 2 - $this->sizes['watermark']['height'] / 2  + $this->margin['y'];
		}
		$this->position = array('x' => $x,'y' => $y,'string' => $position);
	}
	
	private function resize_png_image($srcImage, $width, $height){
		// Get sizes
		if (!$srcWidth = imagesx($srcImage))
		{
			return $this->error('Watermark::resize_png_image: could not get image width');
		}
		if (!$srcHeight = imagesy($srcImage))
		{
			return $this->error('Watermark::resize_png_image: could not get image height');
		}
		
		// Get percentage and destiny size
		$percentage = (double)$width / $srcWidth;
		$destHeight = round($srcHeight * $percentage) + 1;
		$destWidth = round($srcWidth * $percentage) + 1;
		
		if($destHeight > $height)
		{
		    // if the width produces a height bigger than we want, calculate based on height
		    $percentage = (double)$height / $srcHeight;
		    $destHeight = round($srcHeight * $percentage) + 1;
		    $destWidth = round($srcWidth * $percentage) + 1;
		}
		
		if (!$destImage = imagecreatetruecolor($destWidth-1, $destHeight-1))
		{
			return $this->error('Watermark::resize_png_image: imagecreatetruecolor could not create the image');
		}
		if(!imagealphablending($destImage, FALSE))
		{
			return $this->error('Watermark::resize_png_image: could not apply imagealphablending');
		}
		if(!imagesavealpha($destImage, TRUE))
		{
			return $this->error('Watermark::resize_png_image: could not apply imagesavealpha');
		}
		if(!imagecopyresampled($destImage, $srcImage, 0, 0, 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight))
		{
			return $this->error('Watermark::resize_png_image: could not copy resampled image');
		}
		if (!imagedestroy($srcImage))
		{
			return $this->error('Watermark::resize_png_image: could not destroy the image');
		}
		return $destImage;
	}
	
	private function error($type, $text)
	{
		if(!is_array($this->errors)) $this->errors = array();
		array_push($this->errors, $text);
		return false;
	}

	private function imgRotate($srcimg, $angle, $bgcolor, $ignore_transparent = 0)
	{
		if(function_exists("imagerotate"))
		{
			return imagerotate($srcimg, $angle, $bgcolor, $ignore_transparent);
		}
		else
		{
			return $this->imagerotateEquivalent($srcimg, $angle, $bgcolor, $ignore_transparent);
		}
	}

	private function rotateX($x, $y, $theta)
	{
		return $x * cos($theta) - $y * sin($theta);
	}
	private function rotateY($x, $y, $theta)
	{
		return $x * sin($theta) + $y * cos($theta);
	}

	private function imagerotateEquivalent($srcImg, $angle, $bgcolor, $ignore_transparent = 0)
	{
		$srcw = imagesx($srcImg);
		$srch = imagesy($srcImg);

		//Normalize angle
		$angle %= 360;
		//Set rotate to clockwise
		$angle = -$angle;

		if($angle == 0) {
			if ($ignore_transparent == 0) {
				imagesavealpha($srcImg, true);
			}
			return $srcImg;
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
		else if (abs($angle) === 180)
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
				$this->rotateX(0, 0, 0-$theta),
				$this->rotateX($srcw, 0, 0-$theta),
				$this->rotateX(0, $srch, 0-$theta),
				$this->rotateX($srcw, $srch, 0-$theta)
			);
			$minX = floor(min($temp));
			$maxX = ceil(max($temp));
			$width = $maxX - $minX;

			// Calculate the height of the destination image.
			$temp = array (
				$this->rotateY(0, 0, 0-$theta),
				$this->rotateY($srcw, 0, 0-$theta),
				$this->rotateY(0, $srch, 0-$theta),
				$this->rotateY($srcw, $srch, 0-$theta)
			);
			$minY = floor(min($temp));
			$maxY = ceil(max($temp));
			$height = $maxY - $minY;
		}

		$destimg = imagecreatetruecolor($width, $height);
		if ($ignore_transparent == 0)
		{
			$temp = imagecolorallocatealpha($destimg, 255,255, 255, 127);
			imagefill($destimg, 0, 0, $temp);
			//if set the default color or white or magic pink then use transparent color
			if ( ($bgcolor == 0) || ($bgcolor == 16777215) || ($bgcolor == 16711935) )
			{
				$bgcolor = $temp;
			}
			imagesavealpha($destimg, true);
		}

		// sets all pixels in the new image
		for($x=$minX; $x<$maxX; $x++)
		{
			for($y=$minY; $y<$maxY; $y++)
			{
				// fetch corresponding pixel from the source image
				$srcX = round($this->rotateX($x, $y, $theta));
				$srcY = round($this->rotateY($x, $y, $theta));
				if($srcX >= 0 && $srcX < $srcw && $srcY >= 0 && $srcY < $srch)
				{
					$color = imagecolorat($srcImg, $srcX, $srcY );
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
}
$wm = new Watermark();
$wm->setImage('imatge23.jpg');
$wm->setWatermark(array('file' => 'watermark.png', 'position' => 'bottom right', 'size' => '150%'));
$wm->rotateImage(array('degrees' => 45, 'bgcolor' => 0));
$wm->resize(array('type' => 'resizecrop', 'size' => array('300', '200')));
$wm->applyWatermark();
$wm->generate();