<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{
    /**
     * Listar todos os usuários
     */
    public function index()
    {
        return User::all();
    }

    /**
     * @OA\Post(
     *     path="/register",
     *     summary="Registrar um novo usuário",
     *     description="Cria um novo usuário no sistema. Após o registro, o usuário precisará fazer login separadamente para obter um token de acesso. Por padrão, novos usuários recebem o papel 'user'.",
     *     operationId="registerUser",
     *     tags={"Autenticação"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="name", type="string", example="João Silva"),
     *             @OA\Property(property="email", type="string", format="email", example="joao@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="senha123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="senha123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuário criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="João Silva"),
     *             @OA\Property(property="email", type="string", format="email", example="joao@example.com"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-14T22:24:22.000000Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-14T22:24:22.000000Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
     *                     type="array",
     *                     @OA\Items(type="string", example="O email já está sendo utilizado.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Atribuir papel de usuário comum
        $user->assignRole('user');

        return response()->json($user, 201);
    }

    /**
     * Exibir um usuário específico
     */
    public function show(User $user)
    {
        return $user;
    }

    /**
     * Atualizar um usuário
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8',
        ]);

        if ($request->has('name')) {
            $user->name = $request->name;
        }
        
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }
        
        $user->save();
        
        return response()->json($user);
    }

    /**
     * Excluir um usuário
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }

    /**
     * Autenticar usuário e gerar token JWT
     * 
     * @OA\Post(
     *     path="/api/login",
     *     summary="Autenticar usuário",
     *     description="Autentica um usuário com e-mail e senha e retorna um token JWT",
     *     operationId="loginUser",
     *     tags={"Autenticação"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="usuario@exemplo.com"),
     *             @OA\Property(property="password", type="string", format="password", example="senha123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login bem-sucedido",
     *         @OA\JsonContent(
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=1296000),
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1Ni..."),
     *             @OA\Property(property="refresh_token", type="string", example="def5020076acb1ed580f4..."),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Usuário Teste"),
     *                 @OA\Property(property="email", type="string", example="usuario@exemplo.com"),
     *                 @OA\Property(property="role", type="string", example="user")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Credenciais inválidas",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Credenciais inválidas")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'error' => 'Credenciais inválidas'
            ], 401);
        }
        
        $user = $request->user();
        
        // Gerar token via OAuth2 com Passport
        $tokenRequest = Request::create('/oauth/token', 'POST', [
            'grant_type' => 'password',
            'client_id' => 1, // O cliente criado para password grant
            'client_secret' => 'jCjgGb46hKS4HrdjxL8ATuedNRteuV9O7zNLOPEl', // O secret do cliente
            'username' => $request->email,
            'password' => $request->password,
            'scope' => '',
        ]);
        
        $tokenResponse = app()->handle($tokenRequest);
        $contentToken = json_decode($tokenResponse->getContent(), true);
        
        // Adicionar informações do usuário
        $contentToken['user'] = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role
        ];
        
        return response()->json($contentToken, 200);
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     summary="Encerrar sessão",
     *     description="Encerra a sessão do usuário e invalida o token de acesso OAuth",
     *     operationId="logoutUser",
     *     tags={"Autenticação"},
     *     security={{"bearerAuth":{}}, {"oauth2":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout bem-sucedido",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Todos os tokens foram revogados"),
     *             @OA\Property(property="success", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        // Revogar todos os tokens do usuário
        foreach ($request->user()->tokens as $token) {
            $token->revoke();
        }

        return response()->json([
            'message' => 'Todos os tokens foram revogados',
            'success' => true
        ]);
    }

    /**
     * @OA\Get(
     *     path="/user/role",
     *     summary="Obter papel e permissões do usuário",
     *     description="Retorna o papel e as permissões do usuário autenticado",
     *     operationId="getUserRole",
     *     tags={"Usuários"},
     *     security={{"bearerAuth":{}}, {"oauth2":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Papel e permissões do usuário",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="João Silva"),
     *                 @OA\Property(property="email", type="string", format="email", example="joao@example.com")
     *             ),
     *             @OA\Property(
     *                 property="roles",
     *                 type="array",
     *                 @OA\Items(type="string", example="user")
     *             ),
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="string", example="view contents")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function role(Request $request)
    {
        $user = $request->user();
        
        try {
            // Primeiro tenta obter os roles usando o pacote Spatie
            $roles = $user->getRoleNames()->toArray();
        } catch (\Throwable $e) {
            // Se falhar, verifica o campo 'role' diretamente
            $roles = [];
            if (!empty($user->role)) {
                $roles[] = $user->role;
            }
        }
        
        try {
            // Tenta obter todas as permissões
            $permissionsCollection = $user->getAllPermissions();
            $permissions = $permissionsCollection->pluck('name')->toArray();
        } catch (\Throwable $e) {
            // Se falhar, verifica campo permissions ou permissões baseadas na role
            try {
                $permissions = [];
                
                // Verifica se tem a permissão diretamente na propriedade
                if (isset($user->permissions) && is_array($user->permissions)) {
                    $permissions = $user->permissions;
                } else {
                    // Obtém permissões baseadas nos roles
                    if (in_array('admin', $roles)) {
                        $permissions = [
                            'create content', 'edit content', 'delete content', 'view content',
                            'view users', 'create users', 'edit users', 'delete users',
                            'view plans', 'create plans', 'edit plans', 'delete plans'
                        ];
                    } elseif (in_array('manager', $roles)) {
                        $permissions = [
                            'create content', 'edit content', 'view content', 'view users'
                        ];
                    } elseif (in_array('user', $roles)) {
                        $permissions = ['view content'];
                    }
                }
            } catch (\Throwable $e) {
                // Último recurso: array vazio
                $permissions = [];
            }
        }
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'roles' => $roles,
            'permissions' => $permissions,
            'success' => true,
            'message' => 'Operação realizada com sucesso.'
        ]);
    }
} 