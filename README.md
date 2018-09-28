# laravel-SingleImage-trait

This trait is dependence of Intervention Image

# in Controller

    $model = new Model();
    $model->image = $request;

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
            'strict' => true
    ]

#  Renaming setAttribute method
    use SingleImage {
        setImageAttribute as setUniqueAttribute;
    }

#  Deleting Image

in Controller
    
    //delete all image
    $model->deleteImage();
    
    //delete only small and medium image
    $model->deleteImage('small', 'medium);
    

# Implementing single or multi delete on eloquent

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
    
# Implementing image thumbs

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
            $model->prepareImageDir();
            $model->deleteImage();
            $model->saveImage();
        });

        static::deleting(function($model){
            $model->deleteImage();
        });
