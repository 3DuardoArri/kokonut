<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;

class UserController extends Controller
{
    public function pruebas(Request $request)
    {
        return "Pruebas usuario";
    }

    public function register(Request $request)
    {
        $user_data = $request->input('json', null);
        $user_params_obj = json_decode($user_data);
        $user_params_array = json_decode($user_data, true);

        if (!empty($user_params_obj) && !empty($user_params_array)) {

            $user_params_array = array_map('trim', $user_params_array);

            $validate = \Validator::make($user_params_array, [
                'nombre' => 'required|regex:/^[a-zA-Z0-9\s]+$/',
                'alias' => 'required|alpha',
                'email' => 'required|email|unique:users',
                'password' => 'required'
            ]);

            if ($validate->fails()) {
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'El usuario no se ha creado',
                    'errors' => $validate->errors()
                );
            } else {

                $pwd = hash('sha256', $user_params_array['password']);

                $user = new User();
                $user->nombre = $user_params_array['nombre'];
                $user->alias = $user_params_array['alias'];
                $user->role_id = 1;
                $user->email = $user_params_array['email'];
                $user->password = $pwd;

                $user->save();

                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'El usuario se creo correctamente',
                    'data' => $user
                );
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'Los datos enviados no son correctos'
            );
        }

        return response()->json($data, $data['code']);
    }

    public function login(Request $request)
    {
        $jwtAuth = new \JwtAuth();

        $login_data = $request->input('json', null);
        $login_params_array = json_decode($login_data, true);

        $validate = \Validator::make($login_params_array, [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validate->fails()) {
            $signup = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'El usuario no se ha creado',
                'errors' => $validate->errors()
            );
        } else {
            $pwd = hash('sha256', $login_params_array['password']);
            $signup = $jwtAuth->signup($login_params_array['email'], $pwd);

            if (!empty($user_params_array['getToken'])) {
                $signup = $jwtAuth->signup($login_params_array['email'], $pwd, true);
            }
        }

        return response()->json($signup, 200);
    }

    public function update(Request $request)
    {
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        $update_data = $request->input('json', null);
        $update_params_array = json_decode($update_data, true);

        if ($checkToken && !empty($update_params_array)) {
            $user = $jwtAuth->checkToken($token, true);

            $validate = \Validator::make($update_params_array, [
                'nombre' => 'required|regex:/^[a-zA-Z0-9\s]+$/',
                'alias' => 'required|alpha',
                'email' => 'required|email|unique:users' . $user->sub
            ]);

            unset($update_params_array['id']);
            unset($update_params_array['role_id']);
            unset($update_params_array['password']);
            unset($update_params_array['created_at']);
            unset($update_params_array['remember_token']);

            $user_updated = User::where('id', $user->sub)->update($update_params_array);

            $data = array(
                'code' => 200,
                'status' => 'success',
                'message' => 'El usuario se actualizó',
                'changes' => $update_params_array
            );
        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'El usuario no esta identificado'
            );
        }

        return response()->json($data, $data['code']);
    }

    public function uploadAvatar(Request $request)
    {
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $user = $jwtAuth->checkToken($token, true);
        $user_id = $user->sub;

        $image = $request->file('file0');
        $validate = \Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,png'
        ]);

        if (!$image || $validate->fails()) {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir la imagen'
            );
        } else {
            $image_name = time() . $image->getClientOriginalName();
            \Storage::disk('users')->put($image_name, \File::get($image));

            $base_64img = base64_encode($image);

            $user_avatar = array('avatar' => $base_64img);

            User::where('id', $user_id)->update($user_avatar);

            $data = array(
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            );
        }

        return response()->json($data, $data['code']);
    }

    public function deleteAccount(Request $request)
    {
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        if (!$checkToken || empty($checkToken)) {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Usuario no identificado'
            );
        } else {
            $user = $jwtAuth->checkToken($token, true);
            $user_id = $user->sub;

            if (empty($user) || empty($user_id)) {
                $data = array(
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'No se pudo eliminar la cuenta'
                );
            } else {
                $db_user = User::where('id', $user_id)->delete();

                $data = array(
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'La cuenta fue eliminada'
                );
            }
        }

        return response()->json($data, $data['code']);
    }
}
