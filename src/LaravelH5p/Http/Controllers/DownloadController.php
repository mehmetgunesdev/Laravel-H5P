<?php

namespace Alsay\LaravelH5p\Http\Controllers;

use Illuminate\Routing\Controller;
use Alsay\LaravelH5p\Models\H5pContent;
use Alsay\LaravelH5p\Events\H5pEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class DownloadController extends Controller
{
    public function __invoke(Request $request, $id): BinaryFileResponse
    {
        $h5p = App::make('LaravelH5p');
        $core = $h5p::$core;
        $interface = $h5p::$interface;

        $content = $core->loadContent($id);
        $content['filtered'] = '';
        $params = $core->filterParameters($content);

        return response()
            ->download($interface->_download_file, '', [
                'Content-Type' => 'application/zip',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
            ]);

        event(new H5pEvent('download', null, $content['id'], $content['title'], $content['library']['name'], $content['library']['majorVersion'], $content['library']['minorVersion']));
    }

    public function exportAll(Request $request, $id)
    {
        $course = null;
        if ($id) {
            $course = $id;
        }
        $filePath = storage_path('h5p/exports/');
        $archive_file_name = 'export_h5p2.zip';
        $zip = new ZipArchive();
        //create the file and throw the error if unsuccessful
        if ($zip->open($filePath . $archive_file_name, ZipArchive::CREATE) !== TRUE) {
            exit("cannot open <$archive_file_name>\n");
        }

        $listH5p = H5pContent::select('id')->where('h5p_contents.course_id', $course)->get();
        foreach ($listH5p as $value) {
            $h5p = App::make('LaravelH5p');
            $core = $h5p::$core;
            $interface = $h5p::$interface;
//            $filename = $content['slug'] . '-' . $content['id'] . '.h5p';
            $content = $core->loadContent($value->id);
            $content['filtered'] = '';
            $params = $core->filterParameters($content);
            $filename = $interface->_download_file;
            $zip->addFile($filename, 'h5p/h5p' . $value->id . '.h5p');
        }
        $zip->close();
        //then send the headers to force download the zip file
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . $archive_file_name . "\"");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . filesize($filePath . $archive_file_name));
        ob_clean();
        flush();
        readfile($filePath . $archive_file_name);
        unlink($filePath . $archive_file_name);
    }
}
