# laravel-ImageHandler-trait

This trait is dependence of Intervention Image

# in Controller

    $model = new Model();
    $model->image = $request;

# in Model
These are all available config

    protected $imageHandler = [
        'dir' => 'images/sliders', 
        'thumbFolder' => 'thumbs', 
        'useThumb' => false, 
        'dimension' => [
            'w' => 500, 
            'h' => 200
        ],
        'thumbDimension' => [
            'w' => 400, 
            'h' => 200
        ],
        'ratioVar' => [
            'x' => 'x',
            'y' => 'y',
            'w' => 'w',
            'h' => 'h',
        ],
        'defaultRatio' => [
            'x' => 0,
            'y' => 0,
            'w' => 500,
            'h' => 500,
        ],
        'name' => 'image'
    ]

#  Renaming setAttribute method
    use ImageHandler {
		setImageAttribute as setUniqueAttribute;
	}

#  Deleting Image

in Controller
    
    //delete both main and thumb (if set)
    $model->deleteImage();
    
    //delete only main
    $model->deleteMainImage();
    
    //delete only thumb
    $model->deleteThumbImage();

    //delete batch from this model
    $images = [
        'filename-1.jpg',
        'filename-2.jpg',
        'filename-3.jpg',
    ];
    $model->deleteBatchImage($images);
    
    
#  Next Update (not yet implemented)

Implementing single or multi delete on eloquent

    Model::whereNotNull('deleted_at')->deleteImages();
    
Implementing multiple thumb image

    [
        'useThumb' => [   ['w' => 5,
	                   'h' => 10],
			  ['w' => 10
	                   'h' => 20],
			  ['w' => 20
	                   'h' => 40],]
    ]
    
