<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Intervention\Image\ImageManagerStatic as Image;

class UploadController extends Controller
{
    public function upload(Request $request,$id,$option){
        $fileName = substr(json_encode($request->all()), 2, -5);
//        error_log(json_encode($request->all()));
        $file = $request->file($fileName);
        $name = time().$file->getClientOriginalName();
////        if ($type=='shopUser'){
        $ruta=public_path('/images/'.$name);
        Image::make($file->getRealPath())
            ->resize(300,300
                ,function ($constraint){
                    $constraint->aspectRatio();
                }
            )
            ->save($ruta,72);
//        $driver = Driver::find($driver_id);
//        $driver->foto = $name;
//        $driver->save();
//        return $name;
//        }
        if ($option=='fileCreate'){
//            error_log($name);
//            $ruta=public_path('/files/');
//            $file->move($ruta,$name);
            return $name;
        }

    }
}
