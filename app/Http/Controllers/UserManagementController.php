<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserManagementController extends Controller
{
    /**
     * @OA\Get(
     *     path="/admin/users",
     *     summary="Listar usuários",
     *     description="Lista todos os usuários do sistema com paginação",
     *     operationId="listUsers",
     *     tags={"Admin", "Usuários"},
     *     security={{"bearerAuth":{}}, {"oauth2":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Termo de busca para filtrar usuários por nome ou email",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de usuários",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Usuário Teste"),
     *                     @OA\Property(property="email", type="string", example="teste@example.com"),
     *                     @OA\Property(property="role", type="string", example="admin"),
     *                     @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="total", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Proibido - usuário não tem permissão de admin"
     *     )
     * )
     */
    public function index(Request $request)
    {
        // Verifica se o usuário tem permissão de admin
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Acesso não autorizado'], 403);
        }

        // Lista usuários com paginação
        $users = User::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($users);
    }

    /**
     * @OA\Put(
     *     path="/admin/users/{user}/role",
     *     summary="Atualizar papel do usuário",
     *     description="Atualiza o papel (role) de um usuário específico",
     *     operationId="updateUserRole",
     *     tags={"Admin", "Usuários"},
     *     security={{"bearerAuth":{}}, {"oauth2":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="ID do usuário",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"role"},
     *             @OA\Property(property="role", type="string", example="manager", enum={"admin", "manager", "user"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Papel atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Papel atualizado com sucesso"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Usuário Teste"),
     *                 @OA\Property(property="email", type="string", example="teste@example.com"),
     *                 @OA\Property(property="role", type="string", example="manager")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Proibido - usuário não tem permissão de admin"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     )
     * )
     */
    public function updateRole(Request $request, User $user)
    {
        // Verifica se o usuário tem permissão de admin
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Acesso não autorizado'], 403);
        }

        $request->validate([
            'role' => 'required|in:admin,user,manager'
        ]);

        $user->update([
            'role' => $request->role
        ]);

        return response()->json([
            'message' => 'Papel atualizado com sucesso',
            'user' => $user
        ]);
    }

    /**
     * @OA\Put(
     *     path="/admin/users/{user}/permissions",
     *     summary="Atualizar permissões do usuário",
     *     description="Atualiza as permissões de um usuário específico",
     *     operationId="updateUserPermissions",
     *     tags={"Admin", "Usuários"},
     *     security={{"bearerAuth":{}}, {"oauth2":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="ID do usuário",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"permissions"},
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="string", enum={"create_content", "edit_content", "delete_content", "manage_users"})
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissões atualizadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Permissões atualizadas com sucesso"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Usuário Teste"),
     *                 @OA\Property(property="email", type="string", example="teste@example.com"),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Proibido - usuário não tem permissão de admin"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     )
     * )
     */
    public function updatePermissions(Request $request, User $user)
    {
        // Verifica se o usuário tem permissão de admin
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Acesso não autorizado'], 403);
        }

        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'in:create_content,edit_content,delete_content,manage_users'
        ]);

        $user->permissions = $request->permissions;
        $user->save();

        return response()->json([
            'message' => 'Permissões atualizadas com sucesso',
            'user' => $user
        ]);
    }
    
    /**
     * @OA\Post(
     *     path="/admin/users/{user}/promote-to-admin",
     *     summary="Promover usuário a administrador",
     *     description="Promove um usuário a administrador com todas as permissões",
     *     operationId="promoteUserToAdmin",
     *     tags={"Admin", "Usuários"},
     *     security={{"bearerAuth":{}}, {"oauth2":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="ID do usuário",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuário promovido a administrador com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Usuário promovido a administrador com sucesso"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Usuário Teste"),
     *                 @OA\Property(property="email", type="string", example="teste@example.com"),
     *                 @OA\Property(property="role", type="string", example="admin"),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Proibido - usuário não tem permissão de admin"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado"
     *     )
     * )
     */
    public function promoteToAdmin(User $user)
    {
        // Verifica se o usuário tem permissão de admin
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Acesso não autorizado'], 403);
        }

        // Promove o usuário a admin e concede todas as permissões
        $user->update([
            'role' => 'admin',
            'permissions' => ['create_content', 'edit_content', 'delete_content', 'manage_users']
        ]);

        return response()->json([
            'message' => 'Usuário promovido a administrador com sucesso',
            'user' => $user
        ]);
    }
} 