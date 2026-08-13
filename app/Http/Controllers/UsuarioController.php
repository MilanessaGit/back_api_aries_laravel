<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles:id,nombre');

        if ($request->buscado) {
            $buscado = $request->buscado;

            $query->where(function ($q) use ($buscado) {
                $q->where('name', 'like', '%' . $buscado . '%')
                    ->orWhere('email', 'like', '%' . $buscado . '%');
            });
        }

        $usuarios = $query->orderByDesc('id')->get();

        return response()->json($usuarios);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|exists:roles,nombre',
        ]);

        $usuario = DB::transaction(function () use ($request) {
            $usuario = new User();
            $usuario->name = $request->name;
            $usuario->email = $request->email;
            $usuario->password = bcrypt($request->password);
            $usuario->save();

            $rol = Role::where('nombre', $request->role)->firstOrFail();

            // El sistema trabaja con un único rol principal por usuario.
            $usuario->roles()->sync([$rol->id]);

            return $usuario;
        });

        return response()->json([
            'message' => 'Usuario registrado correctamente',
            'data' => $usuario->load('roles:id,nombre'),
        ], 201);
    }

    public function show($id)
    {
        $usuario = User::with('roles:id,nombre')->find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        return response()->json($usuario);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'required|min:6',
            'role' => 'required|exists:roles,nombre',
        ]);

        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        DB::transaction(function () use ($request, $usuario) {
            $usuario->name = $request->name;
            $usuario->email = $request->email;
            $usuario->password = bcrypt($request->password);
            $usuario->save();

            $rol = Role::where('nombre', $request->role)->firstOrFail();

            // Reemplaza cualquier rol anterior por el rol seleccionado.
            $usuario->roles()->sync([$rol->id]);
        });

        return response()->json([
            'message' => 'Usuario modificado correctamente',
            'data' => $usuario->fresh()->load('roles:id,nombre'),
        ]);
    }

    public function destroy($id)
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        $usuario->delete();

        return response()->json([
            'message' => 'Usuario eliminado',
        ]);
    }
}
