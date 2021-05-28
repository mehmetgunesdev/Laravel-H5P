<?php

namespace InHub\LaravelH5p\Http\Controllers;

use App\Http\Controllers\Controller;
use App\User;
use InHub\LaravelH5p\Events\H5pEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class EmbedController extends Controller
{
    public function __invoke(Request $request, $id)
    {
        try {

            $count_user = User::where('email', $request->email)->count();
            $email = $request->email;
            $pass = 'passh5p';
            if($count_user == 0){
                $data['email'] = $request->email;
                $data['password'] = bcrypt('passh5p');
                $data['name'] = $request->user_name ? $request->user_name : explode('@',$data['email'])[0];
                User::query()->create($data);
            }
            if(auth()->check()){
                dd(auth()->user());
            }else{
                $credentials = ['email'=> $email, 'password'=> $pass];
                $login = auth()->attempt($credentials, true);
            }

            $h5p = App::make('LaravelH5p');
            $core = $h5p::$core;
            $settings = $h5p::get_editor();
            $content = $h5p->get_content($id);
            $embed = $h5p->get_embed($content, $settings);
            $embed_code = $embed['embed'];
            $settings = $embed['settings'];
            $user = \Auth::user();

            event(new H5pEvent('content', null, $content['id'], $content['title'], $content['library']['name'], $content['library']['majorVersion'], $content['library']['minorVersion']));

            return view('h5p.content.embed', compact('settings', 'user', 'embed_code'));
        }catch (\Exception $e){
            return 'H5P content is not exits';
        }
    }
}
