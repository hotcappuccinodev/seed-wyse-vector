<?php

View::composer('docs::template', function($view)
{
	Asset::add('stylesheet', 'css/style.css');
	Asset::add('modernizr', 'js/modernizr-2.5.3.min.js');
	Asset::container('footer')->add('prettify', 'js/prettify.js');
});


Route::get('(:bundle)', function()
{
	return View::make('docs::home');
});

