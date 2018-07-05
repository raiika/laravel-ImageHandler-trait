<?php

namespace App\Traits;

use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\File;

trait ImageHandler
{
	public function setImageAttribute($request)
	{
        $filename = $this->getFilename($request);

		$this->createDir();

		$ratio = $this->getRatio($request);
		
		try {
			if ($this->isUsingThumb()) {
				$thumbImage = $this->cropThumbImage($request);
				$this->saveThumbImage($thumbImage, $filename);
			}

			$image = $this->cropImage($request);
			$this->saveImage($image, $filename);
		} catch (Exception $e) {
			$this->deleteImage($filename);
			$this->deleteThumbImage($filename);
			throw new Exception('Error in ImageHandler line 48. Error: ' . $e);
		}

		$this->attributes['image'] = $filename;
	}

	public function getThisImage()
	{
		return $this->{($imageHandler['name'] ?? 'image')};
	}

	public function deleteImage($filename = null)
	{
		$this->deleteMainImage($filename);
		
		if ($this->isUsingThumb()) {
			$this->deleteThumbImage($filename);
		}
	}

	public function deleteMainImage($filename = null)
	{
		$filename = $filename ?? $this->getThisImage();

        $location = public_path($this->getDir() . "/{$filename}");

        if (File::exists($location)) {
        	File::delete($location);
        }
	}

	public function deleteThumbImage($filename = null)
	{
		$filename = $filename ?? $this->getThisImage();
		
        $location = public_path($this->getThumbDir() . "/{$filename}");

        if (File::exists($location)) {
        	File::delete($location);
        }
	}

    public function getFilename($request)
    {
    	if (!$request->image) {
    		throw new Exception('request image is null');
    	}

        $filename = str_random(40) . '.' . $request->image->getClientOriginalExtension();
        $found    = self::where('image', $filename)->count();

        if ($found !== 0) {
            return $this->getFilename();
        }

        return $filename;
    }

	public function saveImage($image, $filename)
	{
        $location = public_path($this->getDir() . "/{$filename}");
		return $image->save($location);
	}

	public function saveThumbImage($image, $filename)
	{
        $location = public_path($this->getThumbDir() . "/{$filename}");
		return $image->save($location);
	}

	public function makeImageObject($request)
	{
		$manager = new ImageManager();
        $image   = $manager->make($request->image);
        
        $ratio = $this->getRatio($request);

        if (isset($ratio['w']) && isset($ratio['h']) && isset($ratio['x']) && isset($ratio['y'])) {
        	$image->crop($ratio['w'], $ratio['h'], $ratio['x'], $ratio['y']);
    	}

        return $image;
	}

    public function cropImage($request)
    {
    	$image = $this->makeImageObject($request);

        $dimension = $this->getDimension();

        $image->fit($dimension['w'], $dimension['h'], function ($constraint) {
            $constraint->upsize();
        });

        return $image;
    }

    public function cropThumbImage($request)
    {
    	$image = $this->makeImageObject($request);
    	
        $thumbDimension = $this->getThumbDimension();

        $image->fit($this->thumbDimension[0], $this->thumbDimension[1], function ($constraint) {
            $constraint->upsize();
        });

        return $image;
    }

	public function getRatio($request)
	{
		return [
			'x' => $request->{$this->getVarX()} ?? $this->getX(),
			'y' => $request->{$this->getVarY()} ?? $this->getY(),
			'w' => $request->{$this->getVarW()} ?? $this->getW(),
			'h' => $request->{$this->getVarH()} ?? $this->getH(),
		];
	}

	public function getX()
	{
		return $this->imageHandler['defaultRatio']['x'] ?? null;
	}

	public function getY()
	{
		return $this->imageHandler['defaultRatio']['y'] ?? null;
	}

	public function getW()
	{
		return $this->imageHandler['defaultRatio']['w'] ?? null;
	}

	public function getH()
	{
		return $this->imageHandler['defaultRatio']['h'] ?? null;
	}

	public function getVarX()
	{
		return $this->imageHandler['ratioVar']['x'] ?? 'x';
	}

	public function getVarY()
	{
		return $this->imageHandler['ratioVar']['y'] ?? 'y';
	}

	public function getVarW()
	{
		return $this->imageHandler['ratioVar']['w'] ?? 'w';
	}

	public function getVarH()
	{
		return $this->imageHandler['ratioVar']['h'] ?? 'h';
	}

    public function createDir()
    {
        $dir  = $this->getDir();

        if (!is_dir(public_path($dir))) {
            mkdir(public_path($dir));
        }

        if ($this->isUsingThumb()) {
            $thumbDir = $this->getThumbDir();
            if (!is_dir(public_path($thumbDir))) {
                mkdir(public_path($thumbDir));
            }
        }
    }

    public function getDefaultDimension()
    {
    	return [
			'w' => 500, 
			'h' => 500
		];
    }

    public function getDimension()
    {
    	return $this->imageHandler['dimension'] ?? $this->getDefaultDimension();
    }

    public function getThumbDimension()
    {
    	return $this->imageHandler['thumbDimension'] ?? $this->getDefaultDimension();
    }

    public function getDir()
    {
    	$array = explode('\\', get_class($this));
    	return $this->imageHandler['dir'] ?? 'images/' . strtolower(end($array)) . 's';
    }

    public function getThumbDir()
    {
    	return $this->getDir() . '/' . $this->getThumbFolder();
    }

    public function getThumbFolder()
    {
    	return $this->imageHandler['thumbFolder'] ?? 'thumbs';
    }

    public function isUsingThumb()
    {
    	return $this->imageHandler['useThumb'] ?? false;
    }
}
