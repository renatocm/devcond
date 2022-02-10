<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\User;
class UserController extends Controller
{
    public function getInfo($id) {
        $array = ['error' => ''];

        $user = User::find($id);
        if($user) {
            $userLog = auth()->user();

            if($userLog['id'] === $user['id']) {
                $name = $user['name'];
                $email = $user['email'];
                $cpf = $user['cpf'];

                $array['name'] = $name;
                $array['email'] = $email;
                $array['cpf'] = $cpf;
            } else {
                $array['error'] = 'Usúário não está logado.';
            return $array;
            }
        } else {
            $array['error'] = 'Usuário inexistente.';
            return $array;
        }
        return $array;
    }

    public function update($id, Request $request) {
        $array = ['error' => ''];

        $user = User::find($id);
        $userLog = auth()->user();

        if($user) {
            if($userLog['id'] === $user['id']) {
                $validator = Validator::make($request->all(), [
                    'name' => ['required'],
                    'email' => ['required','email',Rule::unique('users')->ignore($user->id)],
                    'cpf' => ['required','digits:11',Rule::unique('users')->ignore($user->id)]
                ]);
                if(!$validator->fails()) {
                    $name = $request->input('name');
                    $email = $request->input('email');
                    $cpf = $request->input('cpf');

                    $user->name = $name;
                    $user->email = $email;
                    $user->cpf = $cpf;
                    $user->save();

                    $array['user'] = $user;
                } else {
                    $array['error'] = $validator->errors()->first();
                    return $array;
                }
            } else {
                $array['error'] = 'Usuário não logado.';
                return $array;
            }
        } else {
            $array['error'] = 'Usuário inexistente.';
            return $array;
        }
        return $array;
    }

    public function newPassword($id, Request $request) {
        $array = ['error' => ''];

        $user = User::find($id);
        $userLog = auth()->user();

        if($user) {
            if($userLog['id'] === $user['id']) {
                $validator = Validator::make($request->all(), [
                    'password' => 'required',
                    'password_confirm' => 'required|same:password'
                ]);

                if(!$validator->fails()) {
                    $password = $request->input('password');
                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    $user->password = $hash;
                    $user->save();

                } else {
                    $array['error'] = $validator->errors()->first();
                    return $array;
                }
            } else {
                $array['error'] = 'Usuário não logado.';
                return $array;
            }
        } else {
            $array['error'] = 'Usuário inexistente.';
            return $array;
        }
        return $array;
    }
}
