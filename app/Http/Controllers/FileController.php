<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileController extends Controller
{
    public function download(string $path)
    {
        $filename = basename($path);
        if (pathinfo($filename, PATHINFO_EXTENSION) === "") {
            $filename .= ".pdf";
        }

        header("Content-Disposition: attachment; filename=$filename");
        readfile($path);
    }
}
