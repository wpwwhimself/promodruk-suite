<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileController extends Controller
{
    public function download(string $path)
    {
        $filename = basename($path);
        header("Content-Disposition: attachment; filename=$filename");
        readfile($path);
    }
}
