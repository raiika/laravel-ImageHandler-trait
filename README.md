# laravel-ImageHandler-trait


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
