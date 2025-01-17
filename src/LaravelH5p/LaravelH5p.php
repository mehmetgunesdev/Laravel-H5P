<?php

/*
 *
 * @Project        djoudi/laravel-h5p
 * @Copyright      Djoudi
 * @Created        2017-02-21
 * @Filename       LaravelH5p.php
 * @Description
 *
 */

namespace Alsay\LaravelH5p;

use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Facades\DB;
use Alsay\LaravelH5p\Repositories\EditorAjaxRepository;
use Alsay\LaravelH5p\Repositories\LaravelH5pRepository;
use Alsay\LaravelH5p\Storages\EditorStorage;
use Alsay\LaravelH5p\Storages\LaravelH5pStorage;
use H5PContentValidator;
use H5PCore;
use H5peditor;
use H5PExport;
use H5PStorage;
use H5PValidator;
use Illuminate\Support\Facades\Auth;

//H5P_Plugin
class LaravelH5p
{
    /**
     * Instance of H5P WordPress Framework Interface.
     *
     * @since 1.0.0
     *
     */
    public static ?H5PCore $core = null;
    public static ?H5peditor $h5editor = null;
    public static ?LaravelH5pRepository $interface = null;
    public static ?H5PValidator $validator = null;
    public static ?H5PStorage $storage = null;
    public static ?H5PContentValidator $contentValidator = null;
    public static ?H5PExport $export = null;

    public function __construct()
    {
        self::$interface = new LaravelH5pRepository();
        self::$core = new H5PCore(self::$interface, self::get_h5p_storage('', true), self::get_h5p_url(), config('laravel-h5p.language'), config('laravel-h5p.h5p_export'));
        self::$core->aggregateAssets = config('laravel-h5p.H5P_DISABLE_AGGREGATION');
        self::$validator = new H5PValidator(self::$interface, self::$core);
        self::$storage = new H5PStorage(self::$interface, self::$core);
        self::$contentValidator = new H5PContentValidator(self::$interface, self::$core);
        self::$export = new H5PExport(self::$interface, self::$core);
        self::$h5editor = new H5peditor(self::$core, new EditorStorage(), new EditorAjaxRepository());
        self::$h5editor = new H5peditor(self::$core, new EditorStorage(), new EditorAjaxRepository());
    }

    /**
     * Parse version string into smaller components.
     *
     * @param string $version
     *
     * @return object|bool
     * @since 1.7.9
     */
    public static function split_version(string $version): object|bool
    {
        $version_parts = explode('.', $version);
        if (count($version_parts) !== 3) {
            return false;
        }

        return (object)[
            'major' => (int)$version_parts[0],
            'minor' => (int)$version_parts[1],
            'patch' => (int)$version_parts[2],
        ];
    }

    /**
     * @param $path
     * @return string|UrlGenerator|Application
     */
    public static function get_url($path = ''): string|UrlGenerator|Application
    {
        return url('/assets/vendor' . $path, [], env('APP_ENV') == 'production');
    }

    /**
     * @param string $path
     * @param bool $absolute
     * @return LaravelH5pStorage|string|UrlGenerator|Application
     */
    public static function get_h5p_storage(string $path = '', bool $absolute = false): LaravelH5pStorage|string|UrlGenerator|Application
    {
        if ($absolute) {
            return new LaravelH5pStorage(storage_path('h5p' . $path));
//            return storage_path('h5p' . $path);
        } else {
            return self::get_url('/h5p' . $path);
        }
    }

    /**
     * @param string $path
     * @return string|UrlGenerator|Application
     */
    public static function get_laravel_h5p_url(string $path = ''): string|UrlGenerator|Application
    {
        return self::get_url('/laravel-h5p' . $path);
    }

    /**
     * @param string $path
     * @return string|UrlGenerator|Application
     */
    public static function get_h5p_url(string $path = ''): string|UrlGenerator|Application
    {
        return self::get_url('/h5p' . $path);
    }

    /**
     * @param string $path
     * @return string|UrlGenerator|Application
     */
    public static function get_h5p_core_url(string $path = ''): string|UrlGenerator|Application
    {
        return self::get_h5p_url('/h5p-core' . $path);
    }

    /**
     * @param string $path
     * @return string|UrlGenerator|Application
     */
    public static function get_h5p_editor_url(string $path = ''): string|UrlGenerator|Application
    {
        return self::get_h5p_url('/h5p-editor' . $path);
    }

    /**
     * @param string $path
     * @param bool $absolute
     * @return string|UrlGenerator|Application
     */
    public static function get_h5p_library_url(string $path = '', bool $absolute = false): string|UrlGenerator|Application
    {
        $return = self::get_url('/h5p' . $path);

        if ($absolute) {
            return storage_path('h5p/' . realpath($return));
//            return storage('/h5p/libraries' . $path);
        } else {
            return $return;
        }
    }

    /**
     * @param string $path
     * @return string
     */
    public static function get_service_url(string $path = ''): string
    {
        return route('h5p.index', [], false);
    }

    /**
     * @return mixed
     */
    public static function get_core(): mixed
    {
        return self::get_core_files(self::get_core_settings());
    }

    /**
     * @param null $content
     * @return mixed
     */
    public static function get_editor($content = null): mixed
    {
        return self::get_editor_assets(self::get_editor_settings($content), $content);
    }

    /**
     * Include settings and assets for the given content.
     *
     * @param array $content
     * @param $settings
     * @param bool $no_cache
     *
     * @return array|string Embed code
     * @since 1.0.0
     */
    public function get_embed(array $content, $settings, bool $no_cache = false): array|string
    {
        // Detemine embed type
        try {
            $embed = H5PCore::determineEmbedType($content['embedType'], $content['library']['embedTypes']);
            // Make sure content isn't added twice
            $cid = 'cid-' . $content['id'];
            if (!isset($settings['contents'][$cid])) {
                $settings['contents'][$cid] = self::get_content_settings($content);
                $core = self::$core;
                // Get assets for this content
                $preloaded_dependencies = $core->loadContentDependencies($content['id'], 'preloaded');
                $files = $core->getDependenciesFiles($preloaded_dependencies);
                self::alter_assets($files, $preloaded_dependencies, $embed);
                if ($embed === 'div') {
                    foreach ($files['scripts'] as $script) {
                        $url = $script->path . $script->version;
                        if (!in_array($url, $settings['loadedJs'])) {
                            $settings['loadedJs'][] = self::get_h5p_library_url($url);
                        }
                    }
                    foreach ($files['styles'] as $style) {
                        $url = $style->path . $style->version;
                        if (!in_array($url, $settings['loadedCss'])) {
                            $settings['loadedCss'][] = self::get_h5p_library_url($url);
                        }
                    }
                } elseif ($embed === 'iframe') {
                    $settings['contents'][$cid]['scripts'] = $core->getAssetsUrls($files['scripts']);
                    $settings['contents'][$cid]['styles'] = $core->getAssetsUrls($files['styles']);
                }
            }

            if ($embed === 'div') {
                return [
                    'settings' => $settings,
                    'embed' => '<div class="h5p-content" data-content-id="' . $content['id'] . '"></div>',
                ];
            } else {
                return [
                    'settings' => $settings,
                    'embed' => '<div class="h5p-iframe-wrapper"><iframe id="h5p-iframe-' . $content['id'] . '" class="h5p-iframe" data-content-id="' . $content['id'] . '" style="height:1px" src="about:blank" frameBorder="0" scrolling="no"></iframe></div>',
                ];
            }
        } catch (Exception $e) {
            return [
                'settings' => $settings,
                'embed' => '<div class="h5p-iframe-wrapper"><p>H5P content is not exits</p></div>',
            ];
        }
    }

    /**
     * The most basic settings.
     */
    private static function get_core_settings(): array
    {
        $settings = [
            'baseUrl' => config('laravel-h5p.domain'),
            'url' => self::get_h5p_storage(), // for uploaded
            'postUserStatistics' => (config('laravel-h5p.h5p_track_user', true) === true),
            'ajax' => [
                'setFinished' => route('h5p.ajax.finish'),
                'contentUserData' => route('h5p.ajax.content-user-data'),
                // 'contentUserData' => route('h5p.ajax.content-user-data', ['content_id' => ':contentId', 'data_type' => ':dataType', 'sub_content_id' => ':subContentId']),
            ],
            'saveFreq' => config('laravel-h5p.h5p_save_content_state', false) ? config('laravel-h5p.h5p_save_content_frequency', 30) : false,
            'siteUrl' => config('laravel-h5p.domain'),
            'l10n' => [
                'H5P' => trans('laravel-h5p.h5p'),
            ],
            'hubIsEnabled' => config('laravel-h5p.h5p_hub_is_enabled'),
        ];

        if (Auth::check()) {
            $settings['user'] = [
                'name' => Auth::user()->name,
                'mail' => Auth::user()->email,
            ];
        }

        return $settings;
    }

    private static function get_core_files($settings = [])
    {
        $settings['loadedJs'] = [];
        $settings['loadedCss'] = [];

        $settings['core'] = [
            'styles' => [],
            'scripts' => [],
        ];

        $settings['core']['styles'][] = self::get_laravel_h5p_url('/css/laravel-h5p.css');

        foreach (H5PCore::$styles as $style) {
            $settings['core']['styles'][] = self::get_h5p_core_url('/' . $style);
        }
        foreach (H5PCore::$scripts as $script) {
            $settings['core']['scripts'][] = self::get_h5p_core_url('/' . $script);
        }

        $settings['core']['scripts'][] = self::get_h5p_editor_url('/scripts/h5peditor-editor.js');

        $settings['core']['scripts'][] = self::get_laravel_h5p_url('/js/laravel-h5p.js?v=1.0.0');
        $settings['core']['scripts'][] = self::get_h5p_editor_url('/language/en.js');
        $settings['core']['scripts'][] = self::get_h5p_core_url('/js/h5p-display-options.js');

        return $settings;
    }

    /**
     * @param $content
     * @return array
     */
    private static function get_editor_settings($content = null): array
    {
        $settings = self::get_core_settings();

        $settings['editor'] = [
            'filesPath' => self::get_h5p_storage('/editor'),
            'fileIcon' => [
                'path' => self::get_h5p_editor_url('/images/binary-file.png'),
                'width' => 50,
                'height' => 50,
            ],
            'ajaxPath' => route('h5p.ajax') . '/',
            // for checkeditor,
            'libraryUrl' => self::get_h5p_editor_url() . '/',
            'copyrightSemantics' => self::$contentValidator->getCopyrightSemantics(),
            'metadataSemantics' => self::$contentValidator->getMetadataSemantics(),
            'assets' => [],
            'deleteMessage' => trans('laravel-h5p.content.destoryed'),
            'apiVersion' => H5PCore::$coreApi,
        ];

        // contents
        if ($content !== null) {
            $settings['editor']['nodeVersionId'] = $content['id'];
        }

        return $settings;
    }

    /**
     * @param array $settings
     * @param $content
     * @return mixed
     */
    private static function get_editor_assets(array $settings = [], $content = null): mixed
    {
        $settings = self::get_core_files($settings);

        // load core assets
        $settings['editor']['assets']['css'] = $settings['core']['styles'];
        $settings['editor']['assets']['js'] = $settings['core']['scripts'];

        $settings['editor']['assets']['js'][] = self::get_laravel_h5p_url('/js/laravel-h5p-editor.js');

        // add editor styles
        foreach (H5peditor::$styles as $style) {
            $settings['editor']['assets']['css'][] = self::get_h5p_editor_url('/' . $style);
        }
        // Add editor JavaScript
        foreach (H5peditor::$scripts as $script) {
            // We do not want the creator of the iframe inside the iframe
            if ($script !== 'scripts/h5peditor-editor.js') {
                $settings['editor']['assets']['js'][] = self::get_h5p_editor_url('/' . $script);
            }
        }

        $language_script = '/language/' . self::get_language() . '.js';
        $settings['editor']['assets']['js'][] = self::get_h5p_editor_url($language_script);

        if ($content) {
            $settings = self::get_content_files($settings, $content);
        }

        return $settings;
    }


    /**
     * @param $content
     * @return array
     */
    public static function get_content_settings($content): array
    {
        $safe_parameters = self::$core->filterParameters($content);
//        if (has_action('h5p_alter_filtered_parameters')) {
        //            // Parse the JSON parameters
        //            $decoded_parameters = json_decode($safe_parameters);
        //            /**
        //             * Allows you to alter the H5P content parameters after they have been
        //             * filtered. This hook only fires before view.
        //             *
        //             * @since 1.5.3
        //             *
        //             * @param object &$parameters
        //             * @param string $libraryName
        //             * @param int $libraryMajorVersion
        //             * @param int $libraryMinorVersion
        //             */
        //            // Stringify the JSON parameters
        //            $safe_parameters = json_encode($decoded_parameters);
        //        }
        // Getting author's user id
        $author_id = (int)(is_array($content) ? $content['user_id'] : $content->user_id);

        // Add JavaScript settings for this content
        $settings = [
            'library' => H5PCore::libraryToString($content['library']),
            'jsonContent' => $safe_parameters,
            'fullScreen' => $content['library']['fullscreen'],
            'exportUrl' => config('laravel-h5p.h5p_export') ? route('h5p.export', [$content['id']]) : '',
            'embedCode' => '<iframe src="' . route('h5p.embed', ['id' => $content['id']]) . '" width=":w" height=":h" frameborder="0" allowfullscreen="allowfullscreen"></iframe>',
            'resizeCode' => '<script src="' . self::get_h5p_core_url('/js/h5p-resizer.js') . '" charset="UTF-8"></script>',
            'url' => route('h5p.embed', ['id' => $content['id']]),
            'title' => $content['title'],
            'displayOptions' => self::$core->getDisplayOptionsForView($content['disable'], $author_id),
            'metadata' => $content['metadata'],
            'contentUserData' => [
                0 => [
                    'state' => '{}',
                ],
            ],
        ];

        // Get preloaded user data for the current user
        if (config('laravel-h5p.h5p_save_content_state') && Auth::check()) {
            $results = DB::select('
                SELECT
                hcud.sub_content_id,
                hcud.data_id,
                hcud.data
                FROM h5p_contents_user_data hcud
                WHERE user_id = ?
                AND content_id = ?
                AND preload = 1', [Auth::user()->id, $content['id']]
            );

            if ($results) {
                foreach ($results as $result) {
                    $settings['contentUserData'][$result->sub_content_id][$result->data_id] = $result->data;
                }
            }
        }

        return $settings;
    }


    /**
     * @param $settings
     * @param $content
     * @return array
     */
    public static function get_content_files($settings, $content): array
    {
        $embed = H5PCore::determineEmbedType($content['embedType'], $content['library']['embedTypes']);

        // Make sure content isn't added twice
        $cid = 'cid-' . $content['id'];
        if (!isset($settings['contents'][$cid])) {

            // Load File
            $settings['contents'][$cid] = self::get_content_settings($content);

            $core = self::$core;

            // Get assets for this content
            $preloaded_dependencies = $core->loadContentDependencies($content['id'], 'preloaded');

            $files = $core->getDependenciesFiles($preloaded_dependencies);

            self::alter_assets($files, $preloaded_dependencies, $embed);

            if ($embed === 'div') {
//                $this->enqueue_assets($files);
                foreach ($files['scripts'] as $script) {
                    $url = $script->path . $script->version;
                    if (!in_array($url, $settings['loadedJs'])) {
                        $settings['loadedJs'][] = self::get_h5p_library_url($url);
                    }
                }
                foreach ($files['styles'] as $style) {
                    $url = $style->path . $style->version;
                    if (!in_array($url, $settings['loadedCss'])) {
                        $settings['loadedCss'][] = self::get_h5p_library_url($url);
                    }
                }
            } elseif ($embed === 'iframe') {
                $settings['contents'][$cid]['scripts'] = $core->getAssetsUrls($files['scripts']);
                $settings['contents'][$cid]['styles'] = $core->getAssetsUrls($files['styles']);
            }
        }

        return $settings;
    }

    /**
     * @param $files
     * @param $dependencies
     * @param $embed
     * @return array
     */
    public static function alter_assets(&$files, &$dependencies, $embed): array
    {
        $libraries = [];
        foreach ($dependencies as $dependency) {
            $libraries[$dependency['machineName']] = [
                'majorVersion' => $dependency['majorVersion'],
                'minorVersion' => $dependency['minorVersion'],
            ];
        }

        return $libraries;
    }

    /**
     * Get content with given id.
     *
     * @param int|null $id
     *
     * @return array
     * @throws Exception
     *
     * @since 1.0.0
     *
     */
    public static function get_content(int $id = null): array
    {
        if ($id === false || $id === null) {
            return trans('h5p.content.missing_h5p_identifier');
        }

        // Try to find content with $id.
        $content = self::$core->loadContent($id);

        if (!$content) {
            return trans('h5p.content.can_not_find_content', ['id' => $id]);
        }
        $content['language'] = self::get_language();

        return $content;
    }

    public static function get_language()
    {
        return config('laravel-h5p.language');
    }
}
