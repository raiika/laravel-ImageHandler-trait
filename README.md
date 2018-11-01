# laravel-SingleImage-trait

This trait is dependence of Intervention Image

The goal is the same as the name, only support single image on a model

If there is a multiple image, it should be one to many, thus implement this trait on the many image Model

# in Controller

Basic use is

    $model = new Model();
    $model->image   = $request->image;
    
    if ($youNeedCropping) {
        $model->cropper = $request->only('x', 'y', 'w', 'h');
        $model->cropper = [0,0,500,500]; // [x,y,w,h]
    }
    
    if ($manualSave) $model->saveImage();
    
    $model->save(); // trigger auto saving
    
    $model->delete(); // trigger auto deleting

# in Model
These are all available config

    protected $singleImage = [
            'dir' => $this->defaultDir(), 
            'dimensions' => [
                'default' => [
                    'w' => 500, 
                    'h' => 500,
                    'upsize' => true,
                    'aspectRatio' => false,
                ],
                'medium' => [
                    'w' => 500, 
                    'h' => null,
                    'upsize' => true,
                    'aspectRatio' => false,
                ]
                'anything' => [
                    'w' => 500, 
                    'h' => 500,
                    'upsize' => true,
                    'aspectRatio' => false,
                ]
            ],
            'dimension' => [
                'w' => 500, 
                'h' => 500,
                'upsize' => true,
                'aspectRatio' => false,
            ],
            'column' => 'image',
            'strict' => false,
            'disablePlaceholder' => false
    ]

#  Renaming default getAttribute method
    use SingleImage {
        getImageAttribute as getUniqueAttribute;
    }
    
    public function getImageAttribute()
    {
        return $this->getUniqueAttribute()->type('big')->or(null);
    }

#  Deleting Image

in Controller
    
    //by default, the trait will delete the image on model delete
    $model->delete();
    
    //if you wants to delete manually all image
    $model->deleteImage();
    
    //delete only small and medium image
    $model->deleteImage('small', 'medium');

# Implementing single or multi delete on query builder

    Model::whereNotNull('deleted_at')->deleteImages();

# Implementing single image

    protected $singleImage = [
            'dimension' => [
                    'w' => 500, 
                    'h' => 500,
                    'upsize' => true,
                    'aspectRatio' => false,
            ],
    ];
    
# Implementing many image dimension (for thumb)

default is a must (it is called when you dont specify what folder u want to use)
or you could set on key ['dimension'] like the single image above and it automatically moved to ['dimensions']['default']

    protected $singleImage = [
            'dimensions' => [
                'default' => [
                    'w' => 500, 
                    'h' => 500,
                    'upsize' => true,
                    'aspectRatio' => false,
                ],
                'medium' => [
                    'w' => 500, 
                    'h' => null,
                    'upsize' => true,
                    'aspectRatio' => false,
                ],
                'small' => [
                    'w' => 500, 
                    'h' => null,
                    'upsize' => true,
                    'aspectRatio' => false,
                ],
                'up-to-you' => [
                    'w' => 500, 
                    'h' => null,
                    'upsize' => true,
                    'aspectRatio' => false,
                ],
            ],
    ];
    
# boot

this is the boot of the trait, it makes the image delete the old one and saved the new one on model save, 
and also delete the old image when the model is deleted
    
        static::saving(function($model){
            $model->deleteImage();
            $model->saveImage();
        });

        static::deleting(function($model){
            $model->deleteImage();
        });
