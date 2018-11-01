<?php

namespace App\Traits;

use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use App\Libraries\SingleImageSeeker;
use Exception;

/*

in Controller

$model = new Model();
$model->image = $request;

or if no need to crop

$model->image = $request->image;

*/

trait SingleImage
{
    protected $singleImageStorage;

    public static function bootSingleImage()
    {
        static::saving(function($model){
            if (isset($model->singleImageStorage['image'])) {
                $model->deleteImage();
                $model->saveImage();
            }
        });

        static::deleting(function($model){
            $model->deleteImage();
        });
    }

    public function defaultSingleImageOptions()
    {
        return [
            'dir' => $this->singleImageDir(), 
            'dimensions' => [
                'default' => [
                    'w' => 500, 
                    'h' => 500,
                    'upsize' => true,
                    'aspectRatio' => false,
                ],
                // 'medium' => [
                //     'w' => 500, 
                //     'h' => null,
                //     'upsize' => true,
                //     'aspectRatio' => false,
                // ],
                // 'anything' => [
                //     'w' => 500, 
                //     'h' => 500,
                //     'upsize' => true,
                //     'aspectRatio' => false,
                // ]
            ],
            // 'dimension' => [
            //     'w' => 500, 
            //     'h' => 500,
            //     'upsize' => true,
            //     'aspectRatio' => false,
            // ],
            'column' => 'image',
            'strict' => false,
            'disablePlaceholder' => false
        ];
    }

    public function setImageAttribute($image)
    {
        if (!is_file($image)) {
            if ($this->singleImageOptions()->get('strict')) {
                throw new Exception('Only file must be inputted here. Or turn off the strict.');
            } else {
                if (is_string($image)) {
                    $this->attributes[$this->singleImageOptions()->get('column')] = $image;
                }

                $this->singleImageStorage['image'] = null;

                return;
            }
        }

        $this->singleImageStorage['image']     = $image;
        $this->singleImageStorage['imagename'] = $this->generateUniqueImageName($image);

        return $this;
    }

    public function setCropperAttribute($crop)
    {
        if (Arr::has($crop, 'x') && Arr::has($crop, 'y') && Arr::has($crop, 'w') && Arr::has($crop, 'h')) {
            $this->singleImageStorage['crop'] = $crop;  

            return $this;          
        }

        $crop = collect($crop)->flatten();

        $this->singleImageStorage['crop'] = [
            'x' => $crop[0],
            'y' => $crop[1],
            'w' => $crop[2],
            'h' => $crop[3],
        ];

        return $this;
    }

    public function saveImage()
    {
        if (!Arr::has($this->singleImageStorage, 'image')) {
            return;
        }

        $imageFile = Arr::get($this->singleImageStorage, 'image');

        $manager = new ImageManager();
        $image   = $manager->make($imageFile);

        if (Arr::has($this->singleImageStorage, 'crop')) {
            $crop = Arr::get($this->singleImageStorage, 'crop');
            $image->crop($crop['w'], $crop['h'], $crop['x'], $crop['y']);
        }

        $this->attributes[$this->singleImageOptions()->get('column')] = $this->singleImageStorage['imagename'];

        if (count($dimension = $this->singleImageOptions()->get('dimensions')) <= 1) {
            $this->doSaveImage($image, $dimension);

            return;
        }

        foreach ($this->singleImageOptions()->get('dimensions') as $folder => $dimension) {
            $this->doSaveImage($image, $dimension, $folder);
        }
    }

    public function doSaveImage($image, $dimension, $folder = null)
    {
        $this->prepareImageDir();

        $image = clone $image;

        if (!$folder) {
            $folder = 'default';
        }

        $dimension = isset($dimension[$folder]) ? $dimension[$folder] : $dimension;

        if (Arr::get($dimension, 'aspectRatio')) {
            $image->resize(Arr::get($dimension, 'w'), Arr::get($dimension, 'h'), function ($constraint) use ($dimension) {
                $constraint->aspectRatio();
                if (Arr::get($dimension, 'upsize')) {
                    $constraint->upsize();
                }
            });
        } else {
            $image->fit(Arr::get($dimension, 'w'), Arr::get($dimension, 'h'), function ($constraint) use ($dimension) {
                if (Arr::get($dimension, 'upsize')) {
                    $constraint->upsize();
                }
            });
        }

        $filename = $this->singleImageStorage['imagename'];

        if (count($this->singleImageOptions()->get('dimensions')) <= 1) {
            $location = public_path($this->singleImageOptions()->get('dir') . "/{$filename}");
        } else {
            $location = public_path($this->singleImageOptions()->get('dir') . '/' . $folder . "/{$filename}");
        }
        
        $image->save($location);
    }

    public function generateUniqueImageName($image)
    {
        $extension = $this->guessImageExtension($image);

        $filename = str_random(40) . $extension;

        $found    = self::query()->where($this->singleImageOptions()->get('column'), $filename)->exists();

        if ($found) {
            return $this->generateUniqueImageName($image);
        }

        return $filename;
    }

    public function guessImageExtension($image)
    {
        switch ($image->getClientOriginalExtension()) {
          case 'gif':
            return '.gif';
            break;

          case 'png':
            return '.png';
            break;

          default:
            return '.jpg';
            break;
        }
    }

    public function prepareImageDir()
    {
        $dir = $this->singleImageOptions()->get('dir');
        
        if (!is_dir(public_path($dir))) {
            $recursive = explode('/', $dir);

            $dir = '';

            foreach ($recursive as $folder) {
                $dir .= $folder;

                if (!is_dir($folder = public_path($dir))) {
                    mkdir($folder);
                }   

                $dir .= '/';
            }
        }

        if (count($this->singleImageOptions()->get('dimensions')) > 1) {
            foreach ($this->singleImageOptions()->get('dimensions') as $folder => $dimension) {
                if (!is_dir($folder = public_path($dir . '/' . $folder))) {
                    mkdir($folder);
                }
            }
        }
    }

    public function singleImageDir()
    {
        $array = explode('\\', get_class($this));

        return 'storage/' . strtolower(end($array)) . 's';
    }

    public function scopeDeleteImages($query)
    {
        return $query->get()->each(function ($model) {
            $model->deleteImage();
        });
    }

    public function deleteImage($folder = null)
    {
        if (!isset($this->attributes[$this->singleImageOptions()->get('column')])) {
            return ;
        }
        
        $filename = $this->attributes[$this->singleImageOptions()->get('column')];
        
        if (is_string($folder)) {
            $folders = func_get_args();

            foreach ($folders as $folder) {
                $location = public_path($this->singleImageOptions()->get('dir') . '/' . $folder . "/{$filename}");
                
                if (File::exists($location)) {
                    File::delete($location);
                }
            }

            return ;
        }

        if (count($this->singleImageOptions()->get('dimensions')) > 1) {
            foreach ($this->singleImageOptions()->get('dimensions') as $folder => $dimension) {
                $location = public_path($this->singleImageOptions()->get('dir') . '/' . $folder . "/{$filename}");
                
                if (File::exists($location)) {
                    File::delete($location);
                }
            }

            return ;
        }

        $location = public_path($this->singleImageOptions()->get('dir') . "/{$filename}");
        
        if (File::exists($location)) {
            File::delete($location);
        }
    }

    public function getImageAttribute()
    {
        return new SingleImageSeeker($this);
    }

    public function singleImageOptions()
    {
        if (!$this->singleImage instanceof Collection) {
            $default = collect($this->defaultSingleImageOptions());

            $options = collect($this->singleImage);

            if ($options->get('dimension') !== null) {
                $dimensions = collect($options->get('dimensions'));
                $dimensions->put('default', $options->get('dimension'));
                $options->put('dimensions', $dimensions);
                $options->forget('dimension');
            }
            
            $this->singleImage = $default->merge($options);
        }

        return $this->singleImage;
    }

    public function getImage($folder = null, $string = 'default')
    {
        if (!$folder) {
            $folder = 'default';
        }

        if (isset($this->attributes[$this->singleImageOptions()->get('column')])) {
            $filename = $this->attributes[$this->singleImageOptions()->get('column')];

            if (count($this->singleImageOptions()->get('dimensions')) > 1) {
                $location = $this->singleImageOptions()->get('dir') . '/' . $folder . "/{$filename}";
                
                if (File::exists(public_path($location))) {
                    return asset($location);
                }
            } else {
                $location = $this->singleImageOptions()->get('dir') . "/{$filename}";
                
                if (File::exists(public_path($location))) {
                    return asset($location);
                }
            }
        }

        if ($this->singleImageOptions()->get('disablePlaceholder')) {
            return $string;
        }

        $dimension = $this->singleImageOptions()->get('dimensions')[$folder];

        if (!isset($dimension['w'])) {
            $dimension['w'] = $dimension['h'];
        } elseif (!isset($dimension['h'])) {
            $dimension['h'] = $dimension['w'];
        }
        
        $textDimension = implode('x', Arr::only($dimension, ['w', 'h']));

        $result = "https://via.placeholder.com/{$textDimension}?text=";

        if ($string === 'default') {
            return $result .= $textDimension;
        } elseif ($string !== null) {
            return $result .= $string;
        }

        return null;
    }
}
