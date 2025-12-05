<?php

namespace App\Http\Controllers;

use App\Helpers\Partners;

class SpaController extends Controller
{
    public function index()
    {
        $meta = Partners::getSettings('meta');

        return view('app', [   // тот же шаблон app.blade.php
            'title'       => __($meta['title_key']),
            'description' => __($meta['description_key']),
            'ogTitle'     => __($meta['og_title_key']),
            'ogDesc'      => __($meta['og_description_key']),
            'myWebSite'      => __($meta['myWebSite']),
            'ogImage'     => $meta['og_image'],
            'appUrl'      => config('app.url'),
        ]);
    }
}