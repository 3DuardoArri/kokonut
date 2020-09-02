<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;
use App\Fotos;

class FotosController extends Controller
{
    public function uploadFoto(Request $request)
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
            \Storage::disk('fotos')->put($image_name, \File::get($image));
            $base_64img = base64_encode($image);

            $foto = new Fotos();
            $foto->user_id = $user_id;
            $foto->nombre = $image_name;
            $foto->base64 = $base_64img;
            $foto->status = 1;

            $foto->save();

            $data = array(
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            );
        }

        return response()->json($data, $data['code']);
    }

    public function viewFotos(Request $request)
    {
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $user = $jwtAuth->checkToken($token, true);

        if ($user->role_id == 1) {

            $fotos = Fotos::where('user_id', $user->sub)->get();

            $data = array(
                'code' => 200,
                'status' => 'success',
                'data' => $fotos
            );
        } elseif ($user->role_id == 2) {

            $fotos = Fotos::all();

            $data = array(
                'code' => 200,
                'status' => 'success',
                'data' => $fotos
            );
        } else {

            $data = array(
                'code' => 400,
                'status' => 'success',
                'message' => 'No hay contenido disponible'
            );
        }
        return response()->json($data, $data['code']);
    }

    public function deleteFoto(Request $request, $foto_id)
    {
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        if (!$checkToken) {

            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Usuario no identificado'
            );
        } else {

            $foto_resp = Fotos::where('id', $foto_id)->delete();

            if (!$foto_resp) {

                $data = array(
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'No se pudo eliminar la foto'
                );
            } else {

                $data = array(
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'Foto eliminada'
                );
            }
        }

        return response()->json($data, $data['code']);
    }
}
