<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ContentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/contents",
     *     summary="Listar cursos",
     *     description="Retorna a lista de todos os cursos preparatórios disponíveis",
     *     operationId="listContents",
     *     tags={"Cursos"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de cursos",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Direito Civil para OAB"),
     *                 @OA\Property(property="description", type="string", example="Curso completo de Direito Civil para o Exame da OAB"),
     *                 @OA\Property(property="type", type="string", example="video"),
     *                 @OA\Property(property="url", type="string", example="https://example.com/curso-direito-civil.mp4"),
     *                 @OA\Property(property="thumbnail", type="string", example="https://example.com/thumb-direito-civil.jpg"),
     *                 @OA\Property(property="featured", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        // Verificar se o usuário está autenticado
        $user = $request->user();
        $isAdmin = $user && $user->hasRole('admin');
        $hasActiveSubscription = $user && $user->hasActiveSubscription();
        
        // Usando cache para melhorar performance, mas com chaves diferentes com base no acesso
        $cacheKey = 'contents.all';
        if ($isAdmin) {
            $cacheKey = 'contents.all.admin';
        } elseif ($hasActiveSubscription) {
            $cacheKey = 'contents.all.subscriber';
        } else {
            $cacheKey = 'contents.all.public';
        }
        
        return Cache::remember($cacheKey, 60, function () use ($isAdmin, $hasActiveSubscription) {
            // Se for admin ou tiver assinatura ativa, mostrar todos os conteúdos
            if ($isAdmin || $hasActiveSubscription) {
                return Content::all();
            }
            
            // Caso contrário, filtrar conteúdos premium
            return Content::where('is_premium', false)->get();
        });
    }

    /**
     * @OA\Post(
     *     path="/admin/contents",
     *     summary="Criar conteúdo",
     *     description="Cria um novo conteúdo no sistema (apenas para administradores)",
     *     operationId="storeContent",
     *     tags={"Admin", "Conteúdos"},
     *     security={{"bearerAuth":{}}, {"oauth2":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "description", "type", "url"},
     *             @OA\Property(property="title", type="string", example="Direito Constitucional para OAB"),
     *             @OA\Property(property="description", type="string", example="Curso completo de Direito Constitucional para aprovação no Exame da OAB"),
     *             @OA\Property(property="type", type="string", example="video", enum={"video", "audio", "pdf", "text"}),
     *             @OA\Property(property="url", type="string", example="https://example.com/curso-direito-constitucional.mp4"),
     *             @OA\Property(property="thumbnail", type="string", example="https://example.com/thumb-direito-constitucional.jpg"),
     *             @OA\Property(property="featured", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Curso criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Direito Constitucional para OAB"),
     *             @OA\Property(property="description", type="string", example="Curso completo de Direito Constitucional para aprovação no Exame da OAB"),
     *             @OA\Property(property="type", type="string", example="video"),
     *             @OA\Property(property="url", type="string", example="https://example.com/curso-direito-constitucional.mp4"),
     *             @OA\Property(property="thumbnail", type="string", example="https://example.com/thumb-direito-constitucional.jpg"),
     *             @OA\Property(property="featured", type="boolean", example=false),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Proibido - sem permissão"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|string|in:video,audio,pdf,text',
            'url' => 'required|url',
            'thumbnail' => 'nullable|url',
            'featured' => 'boolean',
            'is_premium' => 'boolean',
        ]);

        $content = Content::create($request->all());
        
        // Limpar todos os caches relevantes
        $this->clearContentCaches($content);
        
        return response()->json($content, 201);
    }

    /**
     * @OA\Get(
     *     path="/contents/{content}",
     *     summary="Exibir curso",
     *     description="Retorna os detalhes de um curso específico",
     *     operationId="showContent",
     *     tags={"Cursos"},
     *     @OA\Parameter(
     *         name="content",
     *         in="path",
     *         description="ID do curso",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes do curso",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Direito Civil para OAB"),
     *             @OA\Property(property="description", type="string", example="Curso completo de Direito Civil para o exame da OAB"),
     *             @OA\Property(property="type", type="string", example="video"),
     *             @OA\Property(property="url", type="string", example="https://example.com/curso-direito-civil.mp4"),
     *             @OA\Property(property="thumbnail", type="string", example="https://example.com/thumb-direito-civil.jpg"),
     *             @OA\Property(property="featured", type="boolean", example=false),
     *             @OA\Property(property="is_premium", type="boolean", example=false),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Curso não encontrado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Proibido - Assinatura ativa necessária para conteúdo premium"
     *     )
     * )
     */
    public function show(Content $content, Request $request)
    {
        // Se o conteúdo for premium, verificar se o usuário está autenticado e tem assinatura ativa ou é admin
        if ($content->is_premium) {
            if (!$request->user()) {
                return response()->json([
                    'message' => 'É necessário estar autenticado para acessar conteúdo premium.'
                ], 401);
            }
            
            // Verificar se o usuário é admin ou tem assinatura ativa
            $user = $request->user();
            $isAdmin = $user->hasRole('admin');
            $hasActiveSubscription = $user->hasActiveSubscription();
            
            // Permitir acesso apenas para admins ou usuários com assinatura ativa
            if (!$isAdmin && !$hasActiveSubscription) {
                return response()->json([
                    'message' => 'É necessário ter uma assinatura ativa para acessar este conteúdo premium.',
                    'subscriptions_url' => url('/api/plans') // URL para que o usuário possa ver os planos disponíveis
                ], 403);
            }
        }
        
        // Usando cache para melhorar performance
        $userId = $request->user() ? $request->user()->id : 'guest';
        $cacheKey = "content.{$content->id}.{$userId}";
        
        return Cache::remember($cacheKey, 30, function () use ($content) {
            return $content;
        });
    }

    /**
     * @OA\Put(
     *     path="/admin/contents/{content}",
     *     summary="Atualizar conteúdo",
     *     description="Atualiza um conteúdo existente (apenas para administradores)",
     *     operationId="updateContent",
     *     tags={"Admin", "Conteúdos"},
     *     security={{"bearerAuth":{}}, {"oauth2":{}}},
     *     @OA\Parameter(
     *         name="content",
     *         in="path",
     *         description="ID do curso",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Direito Civil Atualizado para OAB"),
     *             @OA\Property(property="description", type="string", example="Curso atualizado de Direito Civil com as últimas alterações legislativas"),
     *             @OA\Property(property="type", type="string", example="video", enum={"video", "audio", "pdf", "text"}),
     *             @OA\Property(property="url", type="string", example="https://example.com/curso-direito-civil-atualizado.mp4"),
     *             @OA\Property(property="thumbnail", type="string", example="https://example.com/thumb-direito-civil-atualizado.jpg"),
     *             @OA\Property(property="featured", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Curso atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Direito Civil Atualizado para OAB"),
     *             @OA\Property(property="description", type="string", example="Curso atualizado de Direito Civil com as últimas alterações legislativas"),
     *             @OA\Property(property="type", type="string", example="video"),
     *             @OA\Property(property="url", type="string", example="https://example.com/curso-direito-civil-atualizado.mp4"),
     *             @OA\Property(property="thumbnail", type="string", example="https://example.com/thumb-direito-civil-atualizado.jpg"),
     *             @OA\Property(property="featured", type="boolean", example=true),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Curso não encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Proibido - sem permissão"
     *     )
     * )
     */
    public function update(Request $request, Content $content)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'type' => 'sometimes|string|in:video,audio,pdf,text',
            'url' => 'sometimes|url',
            'thumbnail' => 'nullable|url',
            'featured' => 'sometimes|boolean',
            'is_premium' => 'sometimes|boolean',
        ]);

        $oldType = $content->type;
        $content->update($request->all());
        
        // Limpar todos os caches relevantes
        $this->clearContentCaches($content);
        
        // Se o tipo mudou, limpar também o cache do tipo antigo
        if ($oldType !== $content->type) {
            Cache::forget("contents.type.{$oldType}");
            Cache::forget("contents.type.{$oldType}.admin");
            Cache::forget("contents.type.{$oldType}.subscriber");
            Cache::forget("contents.type.{$oldType}.public");
        }
        
        return response()->json($content);
    }

    /**
     * @OA\Delete(
     *     path="/admin/contents/{content}",
     *     summary="Excluir conteúdo",
     *     description="Remove um conteúdo do sistema (apenas para administradores)",
     *     operationId="deleteContent",
     *     tags={"Admin", "Conteúdos"},
     *     security={{"bearerAuth":{}}, {"oauth2":{}}},
     *     @OA\Parameter(
     *         name="content",
     *         in="path",
     *         description="ID do curso",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Curso excluído com sucesso"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Curso não encontrado"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Proibido - sem permissão"
     *     )
     * )
     */
    public function destroy(Content $content)
    {
        $type = $content->type;
        $this->clearContentCaches($content);
        $content->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * @OA\Get(
     *     path="/contents/featured",
     *     summary="Listar cursos em destaque",
     *     description="Retorna a lista de cursos marcados como destaque",
     *     operationId="listFeaturedContents",
     *     tags={"Cursos"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de cursos em destaque",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Direito Penal para OAB"),
     *                 @OA\Property(property="description", type="string", example="Curso completo de Direito Penal para o Exame da OAB"),
     *                 @OA\Property(property="type", type="string", example="video"),
     *                 @OA\Property(property="url", type="string", example="https://example.com/curso-direito-penal.mp4"),
     *                 @OA\Property(property="thumbnail", type="string", example="https://example.com/thumb-direito-penal.jpg"),
     *                 @OA\Property(property="featured", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function featured(Request $request)
    {
        // Verificar se o usuário está autenticado
        $user = $request->user();
        $isAdmin = $user && $user->hasRole('admin');
        $hasActiveSubscription = $user && $user->hasActiveSubscription();
        
        // Usando cache para melhorar performance, mas com chaves diferentes com base no acesso
        $cacheKey = 'contents.featured';
        if ($isAdmin) {
            $cacheKey = 'contents.featured.admin';
        } elseif ($hasActiveSubscription) {
            $cacheKey = 'contents.featured.subscriber';
        } else {
            $cacheKey = 'contents.featured.public';
        }
        
        return Cache::remember($cacheKey, 60, function () use ($isAdmin, $hasActiveSubscription) {
            // Query base: filtrar pelos destaques
            $query = Content::where('featured', true);
            
            // Se NÃO for admin ou assinante ativo, filtrar conteúdos premium
            if (!$isAdmin && !$hasActiveSubscription) {
                $query->where('is_premium', false);
            }
            
            return $query->get();
        });
    }
    
    /**
     * @OA\Get(
     *     path="/contents/type/{type}",
     *     summary="Listar cursos por tipo",
     *     description="Retorna a lista de cursos de um tipo específico",
     *     operationId="listContentsByType",
     *     tags={"Cursos"},
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         description="Tipo de curso (video, audio, pdf, text)",
     *         required=true,
     *         @OA\Schema(type="string", enum={"video", "audio", "pdf", "text"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de cursos do tipo especificado",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Direito Civil para OAB"),
     *                 @OA\Property(property="description", type="string", example="Curso completo de Direito Civil para o Exame da OAB"),
     *                 @OA\Property(property="type", type="string", example="video"),
     *                 @OA\Property(property="url", type="string", example="https://example.com/curso-direito-civil.mp4"),
     *                 @OA\Property(property="thumbnail", type="string", example="https://example.com/thumb-direito-civil.jpg"),
     *                 @OA\Property(property="featured", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Tipo de curso inválido"
     *     )
     * )
     */
    public function byType($type, Request $request)
    {
        // Verificar se o usuário está autenticado
        $user = $request->user();
        $isAdmin = $user && $user->hasRole('admin');
        $hasActiveSubscription = $user && $user->hasActiveSubscription();
        
        // Usando cache para melhorar performance, mas com chaves diferentes com base no acesso
        $cacheKey = "contents.type.{$type}";
        if ($isAdmin) {
            $cacheKey = "contents.type.{$type}.admin";
        } elseif ($hasActiveSubscription) {
            $cacheKey = "contents.type.{$type}.subscriber";
        } else {
            $cacheKey = "contents.type.{$type}.public";
        }
        
        return Cache::remember($cacheKey, 60, function () use ($type, $isAdmin, $hasActiveSubscription) {
            // Query base: filtrar pelo tipo
            $query = Content::where('type', $type);
            
            // Se NÃO for admin ou assinante ativo, filtrar conteúdos premium
            if (!$isAdmin && !$hasActiveSubscription) {
                $query->where('is_premium', false);
            }
            
            return $query->get();
        });
    }

    /**
     * @OA\Get(
     *     path="/premium-content",
     *     summary="Obter conteúdos premium",
     *     description="Retorna conteúdos disponíveis apenas para assinantes premium",
     *     operationId="getPremiumContent",
     *     tags={"Cursos"},
     *     security={{"bearerAuth":{}}, {"oauth2":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de conteúdos premium",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Direito Civil para OAB - Premium"),
     *                 @OA\Property(property="description", type="string", example="Curso exclusivo premium de Direito Civil para o Exame da OAB"),
     *                 @OA\Property(property="type", type="string", example="video"),
     *                 @OA\Property(property="url", type="string", example="https://example.com/premium/curso-direito-civil.mp4"),
     *                 @OA\Property(property="thumbnail", type="string", example="https://example.com/premium/thumb-direito-civil.jpg"),
     *                 @OA\Property(property="is_premium", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Proibido - assinatura inativa"
     *     )
     * )
     */
    public function premium(Request $request)
    {
        // Esta rota já está protegida pelo middleware subscription.or.admin
        // então sabemos que o usuário tem uma assinatura ativa ou é administrador
        
        // Usando cache para melhorar performance
        return Cache::remember('contents.premium', 60, function () {
            return Content::where('is_premium', true)->get();
        });
    }

    /**
     * Limpa todos os caches relacionados a conteúdos
     *
     * @param Content|null $content Conteúdo específico, se aplicável
     * @return void
     */
    private function clearContentCaches(?Content $content = null)
    {
        // Limpar caches gerais
        Cache::forget('contents.all');
        Cache::forget('contents.all.admin');
        Cache::forget('contents.all.subscriber');
        Cache::forget('contents.all.public');
        
        Cache::forget('contents.featured');
        Cache::forget('contents.featured.admin');
        Cache::forget('contents.featured.subscriber');
        Cache::forget('contents.featured.public');
        
        Cache::forget('contents.premium');
        
        // Se tiver um conteúdo específico, limpar caches relacionados a ele
        if ($content) {
            // Limpar cache do tipo do conteúdo
            Cache::forget("contents.type.{$content->type}");
            Cache::forget("contents.type.{$content->type}.admin");
            Cache::forget("contents.type.{$content->type}.subscriber");
            Cache::forget("contents.type.{$content->type}.public");
            
            // Limpar caches individuais do conteúdo (para todos os usuários)
            // Isso é uma simplificação, em produção você poderia querer ser mais seletivo
            Cache::flush();
        }
    }
} 