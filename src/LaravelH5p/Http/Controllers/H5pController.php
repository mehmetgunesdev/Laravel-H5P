<?php

namespace Alsay\LaravelH5p\Http\Controllers;

use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Alsay\LaravelH5p\Models\H5pContent;
use Alsay\LaravelH5p\Events\H5pEvent;
use H5pCore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Alsay\LaravelH5p\Exceptions\H5PException;
use function session;


class H5pController extends Controller
{
    public function index(Request $request): Factory|View|Application
    {
        $course = null;
        if ($request->get('course')) {
            $course = $request->get('course');
            session()->put('course', $request->get('course'));
        }

        $h5p = App::make('LaravelH5p');
        $where = H5pContent::orderBy('h5p_contents.created_at', 'asc');

        if ($request->query('sf') && $request->query('s')) {
            if ($request->query('sf') == 'title') {
                $where->where('h5p_contents.title', $request->query('s'));
            }
            if ($request->query('sf') == 'creator') {
                $where->leftJoin('users', 'users.id', 'h5p_contents.user_id')->where('users.name', 'like', '%' . $request->query('s') . '%');
            }
        }

        //Get by course id
        if ($request->get('course') || session()->get('course')) {
            $course_id = $request->get('course') ? $request->get('course') : session()->get('course');
            $where->where('h5p_contents.course_id', $course_id);
        }

        $search_fields = [
            'title' => trans('laravel-h5p.content.title'),
            'creator' => trans('laravel-h5p.content.creator'),
        ];
        $entrys = $where->paginate(10);
        $entrys->appends(['sf' => $request->query('sf'), 's' => $request->query('s'), 'course' => $request->query('course')]);

        // view Get the file and settings to print from
        $settings = $h5p::get_editor();

        return view('h5p.content.index', compact('entrys', 'request', 'search_fields', 'settings', 'course'));
    }

    public function create(Request $request): Factory|View|Application
    {
        $h5p = App::make('LaravelH5p');
        $core = $h5p::$core;

        $course = null;
        if ($request->get('course')) {
            $course = $request->get('course');
        }

        // Prepare form
        $library = 0;
        $parameters = '{}';

        $display_options = $core->getDisplayOptionsForEdit(null);

        // view Get the file and settings to print from
        $settings = $h5p::get_editor();

        // create event dispatch
        event(new H5pEvent('content', 'new'));

        $user = Auth::user();

        return view('h5p.content.create', compact('settings', 'user', 'library', 'parameters', 'display_options', 'course'));
    }

    public function store(Request $request): RedirectResponse
    {
        $h5p = App::make('LaravelH5p');
        $core = $h5p::$core;
        $editor = $h5p::$h5peditor;

        $course = null;
        if ($request->get('course')) {
            $course = $request->get('course');
        }

        $this->validate($request, [
            'action' => 'required',
        ], [], [
            'action' => trans('laravel-h5p.content.action'),
        ]);

        $oldLibrary = null;
        $oldParams = null;
        $event_type = 'create';
        $content = [
            'disable' => H5PCore::DISABLE_NONE,
            'user_id' => Auth::id(),
            'embed_type' => 'div',
            'filtered' => '',
            'slug' => config('laravel-h5p.slug'),
        ];

        $content['filtered'] = '';
        $content['course'] = $course;
        try {
            if ($request->get('action') === 'create') {
                $content['library'] = $core->libraryFromString($request->get('library'));
                if (!$content['library']) {
                    throw new H5PException('Invalid library.');
                }

                // Check if library exists.
                $content['library']['libraryId'] = $core->h5pF->getLibraryId($content['library']['machineName'], $content['library']['majorVersion'], $content['library']['minorVersion']);
                if (!$content['library']['libraryId']) {
                    throw new H5PException('No such library');
                }
                //old
                // $content['params'] = $request->get('parameters');
                // $params = json_decode($content['params']);

                //new
                $params = json_decode($request->get('parameters'));
                if (!empty($params->metadata) && !empty($params->metadata->title)) {
                    $content['title'] = $params->metadata->title;
                } else {
                    $content['title'] = '';
                }
                $content['params'] = json_encode($params->params);
                if ($params === null) {
                    throw new H5PException('Invalid parameters');
                }

                // Set disabled features
                $this->get_disabled_content_features($core, $content);

                // Save new content
                $content['id'] = $core->saveContent($content);

                // Move images and find all content dependencies
                $editor->processParameters($content['id'], $content['library'], $params->params, $oldLibrary, $oldParams);

                event(new H5pEvent('content', $event_type, $content['id'], $content['title'], $content['library']['machineName'], $content['library']['majorVersion'], $content['library']['minorVersion']));

                $return_id = $content['id'];
            } elseif ($request->get('action') === 'upload') {
                $content['uploaded'] = true;

                $this->get_disabled_content_features($core, $content);

                // Handle file upload
                $return_id = $this->handle_upload($content);
            }

            if ($return_id) {
                return redirect()
                    ->route('h5p.index', ['course' => $course])
                    ->with('success', trans('laravel-h5p.content.created'));
            } else {
                return redirect()
                    ->route('h5p.create', ['course' => $course])
                    ->with('fail', trans('laravel-h5p.content.can_not_created'));
            }
        } catch (H5PException $ex) {
            return redirect()
                ->route('h5p.create', ['course' => $course])
                ->with('fail', trans('laravel-h5p.content.can_not_created'));
        }
    }

    public function edit(Request $request, $id): Factory|View|Application
    {
        $h5p = App::make('LaravelH5p');
        $core = $h5p::$core;
        $editor = $h5p::$h5peditor;

        $course = null;
        if ($request->get('course')) {
            $course = $request->get('course');
        }

        $settings = $h5p::get_core();
        $content = $h5p->get_content($id);
        $embed = $h5p->get_embed($content, $settings);
        $embed_code = $embed['embed'];
        $settings = $embed['settings'];

        // Prepare form
        $library = $content['library'] ? H5PCore::libraryToString($content['library']) : 0;

        $parameters = json_encode([
            'params' => json_decode($content['params']),
            'metadata' => [
                'title' => $content['title'],
            ],
        ]);

        $display_options = $core->getDisplayOptionsForEdit($content['disable']);

        // view Get the file and settings to print from
        $settings = $h5p::get_editor($content);

        // create event dispatch
        event(new H5pEvent('content', 'edit', $content['id'], $content['title'], $content['library']['name'], $content['library']['majorVersion'] . '.' . $content['library']['minorVersion']));

        $user = Auth::user();

        return view('h5p.content.edit', compact('settings', 'user', 'id', 'content', 'library', 'parameters', 'display_options', 'course'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $h5p = App::make('LaravelH5p');
        $core = $h5p::$core;
        $editor = $h5p::$h5peditor;

        $course = null;
        if ($request->get('course')) {
            $course = $request->get('course');
        }

        $this->validate($request, [
            'action' => 'required',
        ], [], [
            'action' => trans('laravel-h5p.content.action'),
        ]);

        $event_type = 'update';
        $content = $h5p::get_content($id);
        $content['embed_type'] = 'div';
        $content['user_id'] = Auth::id();
        $content['disable'] = $request->get('disable') ? $request->get('disable') : false;
        $content['filtered'] = '';

        $oldLibrary = $content['library'];
        $oldParams = json_decode($content['params']);

        try {
            if ($request->get('action') === 'create') {
                $content['library'] = $core->libraryFromString($request->get('library'));
                if (!$content['library']) {
                    throw new H5PException('Invalid library.');
                }

                // Check if library exists.
                $content['library']['libraryId'] = $core->h5pF->getLibraryId($content['library']['machineName'], $content['library']['majorVersion'], $content['library']['minorVersion']);
                if (!$content['library']['libraryId']) {
                    throw new H5PException('No such library');
                }

                //                $content['parameters'] = $request->get('parameters');
                //old
                //$content['params'] = $request->get('parameters');
                //$params = json_decode($content['params']);

                //new
                $params = json_decode($request->get('parameters'));
                if (!empty($params->metadata) && !empty($params->metadata->title)) {
                    $content['title'] = $params->metadata->title;
                } else {
                    $content['title'] = '';
                }
                $content['params'] = json_encode($params->params);
                if ($params === null) {
                    throw new H5PException('Invalid parameters');
                }

                // Set disabled features
                $this->get_disabled_content_features($core, $content);

                // Save new content
                $core->saveContent($content);

                // Move images and find all content dependencies
                $editor->processParameters($content['id'], $content['library'], $params->params, $oldLibrary, $oldParams);

                event(new H5pEvent('content', $event_type, $content['id'], $content['title'], $content['library']['machineName'], $content['library']['majorVersion'], $content['library']['minorVersion']));

                $return_id = $content['id'];
            } elseif ($request->get('action') === 'upload') {
                $content['uploaded'] = true;

                $this->get_disabled_content_features($core, $content);

                // Handle file upload
                $return_id = $this->handle_upload($content);
            }

            if ($return_id) {
                return redirect()
                    ->route('h5p.edit', ['h5p' => $return_id, $course => 'course'])
                    ->with('success', trans('laravel-h5p.content.updated'));
            } else {
                return redirect()
                    ->back()
                    ->with('fail', trans('laravel-h5p.content.can_not_updated'));
            }
        } catch (H5PException $ex) {
            return redirect()
                ->back()
                ->with('fail', trans('laravel-h5p.content.can_not_updated'));
        }
    }

    public function show(Request $request, $id): View|Factory|string|Application
    {
        try {
            $h5p = App::make('LaravelH5p');
            $core = $h5p::$core;
            $settings = $h5p::get_editor();
            $content = $h5p->get_content($id);
            $embed = $h5p->get_embed($content, $settings);
            $embed_code = $embed['embed'];
            $settings = $embed['settings'];
            $user = Auth::user();

            // create event dispatch
            event(new H5pEvent('content', null, $content['id'], $content['title'], $content['library']['name'], $content['library']['majorVersion'], $content['library']['minorVersion']));

            //     return view('h5p.content.edit', compact("settings", 'user', 'id', 'content', 'library', 'parameters', 'display_options'));
            return view('h5p.content.show', compact('settings', 'user', 'embed_code'));
        } catch (Exception $e) {
            return 'H5P content is not exits';
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $content = H5pContent::findOrFail($id);
            $content->delete();
        } catch (Exception $ex) {
            return trans('laravel-h5p.content.can_not_delete');
        }
    }

    private function get_disabled_content_features($core, &$content)
    {
        $set = [
            H5PCore::DISPLAY_OPTION_FRAME => filter_input(INPUT_POST, 'frame', FILTER_VALIDATE_BOOLEAN),
            H5PCore::DISPLAY_OPTION_DOWNLOAD => filter_input(INPUT_POST, 'download', FILTER_VALIDATE_BOOLEAN),
            H5PCore::DISPLAY_OPTION_EMBED => filter_input(INPUT_POST, 'embed', FILTER_VALIDATE_BOOLEAN),
            H5PCore::DISPLAY_OPTION_COPYRIGHT => filter_input(INPUT_POST, 'copyright', FILTER_VALIDATE_BOOLEAN),
        ];
        $content['disable'] = $core->getStorableDisplayOptions($set, $content['disable']);
    }

    private function handle_upload($content = null, $only_upgrade = null, $disable_h5p_security = false)
    {
        $h5p = App::make('LaravelH5p');
        $core = $h5p::$core;
        $validator = $h5p::$validator;
        $interface = $h5p::$interface;
        $storage = $h5p::$storage;

        if ($disable_h5p_security) {
            // Make it possible to disable file extension check
            $core->disableFileCheck = (filter_input(INPUT_POST, 'h5p_disable_file_check', FILTER_VALIDATE_BOOLEAN) ? true : false);
        }

        // Move so core can validate the file extension.
        rename($_FILES['h5p_file']['tmp_name'], $interface->getUploadedH5pPath());

        $skipContent = ($content === null);

        if ($validator->isValidPackage($skipContent, $only_upgrade) && ($skipContent || $content['title'] !== null)) {
            if (function_exists('check_upload_size')) {
                // Check file sizes before continuing!
                $tmpDir = $interface->getUploadedH5pFolderPath();
                $error = self::check_upload_sizes($tmpDir);
                if ($error !== null) {
                    // Didn't meet space requirements, cleanup tmp dir.
                    $interface->setErrorMessage($error);
                    H5PCore::deleteFileTree($tmpDir);

                    return false;
                }
            }
            // No file size check errors
            if (isset($content['id'])) {
                $interface->deleteLibraryUsage($content['id']);
            }

            $storage->savePackage($content, null, $skipContent);

            // Clear cached value for dirsize.
            return $storage->contentId;
        }
        // The uploaded file was not a valid H5P package
        @unlink($interface->getUploadedH5pPath());

        return false;
    }
}
