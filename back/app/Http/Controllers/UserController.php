<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
class UserController extends Controller
{
    public function index(Request $request){
        $users= User::with('agencia')->get();
//        $usersMessage=[];
//        foreach ($users as $user){
//            $chat=Chat::whereRaw("(userEnviado_id=".$request->user()->id." AND userRecibido_id=".$user->id.")OR(userEnviado_id=".$user->id." AND userRecibido_id=".$request->user()->id.")")->orderBy('fecha','desc')->first();
//            if($chat){
//                $user->message=$chat->message;
//                $user->fecha=$chat->fecha;
//            }else{
//                $user->message="";
//                $user->fecha="";
//            }
//            $usersMessage[]=$user;
//        }
        return $users;
    }
    public function show($id,Request $request)
    {
        $user_id=$request->user()->id;
        $chats= Chat::whereRaw("(userEnviado_id=$user_id AND userRecibido_id=$id)OR(userEnviado_id=$id AND userRecibido_id=$user_id)")->get();
        return $chats;
    }
    public function login(Request $request){
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->with('agencia')->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas'],
            ]);
        }
        return response()->json([
            'token'=>$user->createToken('web')->plainTextToken,
            'user'=>$user,
            'env'=>[
                'nit'=>env('NIT'),
                'razon'=>$user->agencia->nombre,
                'direccion'=>$user->agencia->direccion,
                'telefono'=>$user->agencia->telefono,
                'url'=>env("URL_SIAT"),
                'url2'=>env("URL_SIAT2")
            ]
        ],200);
    }
    public function register(Request $request){
        $request->validate([
            'email'=>'required|unique:users|email',
            'name'=>'required|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);
        $user=new User();
        $user->name= ( $request->name);
        $user->email=$request->email;
        $user->password=Hash::make( $request->password);
//        $user->fechaLimite=date('Y-m-d', strtotime(now(). ' + 360 days'));
//        $user->fechaConexion=now();
        $user->save();
        $token=$user->createToken('web')->plainTextToken;
        return response()->json([
            'token'=>$token,
            'user'=>User::find($user->id)
        ],200);
    }
    public function store(Request $request){
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|unique:users',
            'password' => 'required',
//            'password_confirmation' => 'required|same:password',
//            'tipo' => 'required',
//            'fechaLimite' => 'required',
        ]);
        $user=new User();
        $user->name=$request->name;
        $user->email=$request->email;
//        $user->tipo=$request->tipo;
        $user->password= Hash::make($request->password) ;
        $user->agencia_id=$request->agencia_id;
//        $user->fechaLimite=$request->fechaLimite;
        $user->save();
    }
    public function update(Request $request,User $user){
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|unique:users,email,'.$user->id,
//            'tipo' => 'required',
//            'fechaLimite' => 'required',
        ]);
        $user->update($request->all());
        return $user;
    }

    public function updatePassword(Request $request,User $user){
//        $this->validate($request, [
//            'password' => 'required|confirmed',
//            'password_confirmation' => 'required|same:password',
//        ]);
        $user->update([
            'password'=>Hash::make($request->password)
        ]);
        return $user;
    }
    public function destroy(User $user){
        $user->delete();
    }
    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json(['res'=>'salido exitosamente'],200);
    }
    public function me(Request $request){
        $user=User::where('id',$request->user()->id)->with('agencia')->firstOrFail();
//        protected $fillable = ['nombre','direccion','telefono','atencion','horario','facebook','whatsapp','gps','latitud','longitud','status'];
        $agencia = $user->agencia;
        return response()->json([
            'user'=>$user,
            'env'=>[
                'nit'=>env('NIT'),
                'razon'=>$agencia->nombre,
                'direccion'=>$agencia->direccion,
                'telefono'=>$agencia->telefono,
                'url'=>env("URL_SIAT"),
                'url2'=>env("URL_SIAT2")
            ]
        ],200);
    }
}
