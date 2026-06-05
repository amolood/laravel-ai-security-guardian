<?php

namespace Abdalmolood\AiSecurityGuardian\Http\Controllers;

use Illuminate\Routing\Controller;

class AssetController extends Controller
{
    public function css()
    {
        $path = __DIR__.'/../../../resources/css/app.css';
        
        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => 'text/css',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    public function js()
    {
        $path = __DIR__.'/../../../resources/js/app.js';

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => 'application/javascript',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
