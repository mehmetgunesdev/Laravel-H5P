<?php

namespace Alsay\LaravelH5p\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Alsay\LaravelH5p\Events\H5pEvent;
use H5PEditorEndpoints;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Alsay\LaravelH5p\Models\H5pLibrary;
use Alsay\LaravelH5p\Models\H5pResult;
use Alsay\LaravelH5p\Models\H5pContentsUserData;

class AjaxController extends Controller
{
    public function libraries(Request $request)
    {
        $machineName = $request->get('machineName');
        $major_version = $request->get('majorVersion');
        $minor_version = $request->get('minorVersion');

        $h5p = App::make('LaravelH5p');
        $core = $h5p::$core;
        $editor = $h5p::$h5peditor;

        //log($machineName);
        Log::debug('An informational message.' . $machineName . '=====' . $h5p->get_language());
        if ($machineName) {
            $defaultLanguag = $editor->getLibraryLanguage($machineName, $major_version, $minor_version, $h5p->get_language());
            Log::debug('An informational message.' . $machineName . '=====' . $h5p->get_language() . '=====' . $defaultLanguag);

            //   public function getLibraryData($machineName, $majorVersion, $minorVersion, $languageCode, $prefix = '', $fileDir = '', $defaultLanguage) {

            $editor->ajax->action(H5PEditorEndpoints::SINGLE_LIBRARY, $machineName, $major_version, $minor_version, $h5p->get_language(), '', $h5p->get_h5plibrary_url('', true), $defaultLanguag);  //$defaultLanguage
            // Log library load
            event(new H5pEvent('library', null, null, null, $machineName, $major_version . '.' . $minor_version));
        } else {
            // Otherwise retrieve all libraries
            $editor->ajax->action(H5PEditorEndpoints::LIBRARIES);
        }
    }

    public function singleLibrary(Request $request)
    {
        $h5p = App::make('LaravelH5p');
        $editor = $h5p::$h5peditor;
        $editor->ajax->action(H5PEditorEndpoints::SINGLE_LIBRARY, $request->get('_token'));
    }

    public function contentTypeCache(Request $request)
    {
        $h5p = App::make('LaravelH5p');
        $editor = $h5p::$h5peditor;

        $response = $editor->ajax->action(H5PEditorEndpoints::CONTENT_TYPE_CACHE, $request->get('_token'));

        $installedLibraries = H5pLibrary::all();

        if ($response) {
            $response['libraries'] = collect($response['libraries'])->map(function ($lib) use ($installedLibraries) {
                $lib['installed'] = $installedLibraries->contains('name', $lib['machineName']);

                if ($lib['installed']) {
                    $installedLibrary = $installedLibraries->filter(function ($installedLib) use ($lib) {
                        return $installedLib['name'] == $lib['machineName'];
                    })->first();

                    $lib['localMajorVersion'] = $installedLibrary->major_version;
                    $lib['localMinorVersion'] = $installedLibrary->minor_version;
                    $lib['localPatchVersion'] = $installedLibrary->patch_version;
                }

                return $lib;
            });
            return $response;
        }
    }

    public function libraryInstall(Request $request)
    {
        $h5p = App::make('LaravelH5p');
        $editor = $h5p::$h5peditor;
        $editor->ajax->action(H5PEditorEndpoints::LIBRARY_INSTALL, $request->get('_token'), $request->get('id'));
    }

    public function libraryUpload(Request $request)
    {
        $filePath = $request->file('h5p')->getPathName();
        $h5p = App::make('LaravelH5p');
        $editor = $h5p::$h5peditor;
        $editor->ajax->action(H5PEditorEndpoints::LIBRARY_UPLOAD, $request->get('_token'), $filePath, $request->get('contentId'));
    }

    public function ajax_filter(Request $request)
    {
        $token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
        $libraryParameters = filter_input(INPUT_POST, 'libraryParameters');
        $h5p = App::make('LaravelH5p');
        $editor = $h5p::$h5peditor;
        $editor->ajax->action(H5PEditorEndpoints::FILTER, $token, $libraryParameters);
    }

    public function files(Request $request)
    {
        $filePath = $request->file('file');
        $h5p = App::make('LaravelH5p');
        $editor = $h5p::$h5peditor;
        $editor->ajax->action(H5PEditorEndpoints::FILES, $request->get('_token'), $request->get('contentId'));
    }

    public function __invoke(Request $request): JsonResponse
    {
        return response()->json($request->all());
    }

    public function finish(Request $request): JsonResponse
    {
        $input = $request->all();

        $data = [
            'content_id' => $input['contentId'],
            'max_score' => $input['maxScore'],
            'score' => $input['score'],
            'opened' => $input['opened'],
            'finished' => $input['finished'],
            'time' => $input['finished'] - $input['opened'],
            'user_id' => Auth::user()->id,
        ];

        H5pResult::create($data);

        return response()->json([
            'success' => true,
        ]);
    }

    public function contentUserData(Request $request): JsonResponse
    {
        $input = $request->all();

        $contentId = basename($request->header('referer'));

        $userData = H5pContentsUserData::where([
            'content_id' => $contentId,
            'data_id' => 'state',
            'sub_content_id' => 0,
            'user_id' => Auth::user()->id,
        ])->first();

        $data = [
            'content_id' => $contentId,
            'data_id' => 'state',
            'sub_content_id' => 0,
            'user_id' => Auth::user()->id,
            'data' => $input['data'],
            'preload' => $input['preload'],
            'invalidate' => $input['invalidate'],
            'updated_at' => now(),
        ];

        if (empty($userData)) {
            H5pContentsUserData::create($data);
        } else {
            $userData->update($data);
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
