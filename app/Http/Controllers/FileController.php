<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FileController extends Controller
{
    public function download(string $path)
    {
        $filename = basename($path);
        if (pathinfo($filename, PATHINFO_EXTENSION) === "") {
            $filename .= ".pdf";
        } else if (Str::beforeLast($path, "?") != $path) {
            $filename = Str::afterLast(Str::beforeLast($path, "?"), "/");
        }

        header("Content-Disposition: attachment; filename=$filename");
        readfile($path);
    }
}
