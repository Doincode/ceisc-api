<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    /**
     * @OA\Get(
     *     path="/admin/plans",
     *     summary="Listar todos os planos",
     *     description="Lista todos os planos disponíveis e indisponíveis (apenas para administradores)",
     *     operationId="listAllPlans",
     *     tags={"Admin", "Planos"},
     *     @OA\Parameter(
     *         name="all",
     *         in="query",
     *         description="Retornar todos os planos (incluindo inativos). Requer autenticação como admin.",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de planos",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Básico"),
     *                 @OA\Property(property="description", type="string", example="Acesso a cursos básicos para a primeira fase da OAB"),
     *                 @OA\Property(property="price", type="number", format="float", example=29.90),
     *                 @OA\Property(property="billing_cycle", type="string", example="mensal"),
     *                 @OA\Property(property="discount_percentage", type="number", format="float", example=0),
     *                 @OA\Property(property="features", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="stripe_product_id", type="string", nullable=true),
     *                 @OA\Property(property="stripe_price_id", type="string", nullable=true)
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        // Se o usuário for admin e o parâmetro 'all' for true, mostra todos os planos
        if ($request->has('all') && $request->boolean('all') && auth()->user() && auth()->user()->hasRole('admin')) {
            $plans = Plan::all();
        } else {
            // Caso contrário, mostra apenas planos ativos
            $plans = Plan::where('is_active', true)->get();
        }
        
        return response()->json($plans);
    }

    /**
     * @OA\Get(
     *     path="/plans/{plan}",
     *     summary="Exibir plano",
     *     description="Retorna os detalhes de um plano específico",
     *     operationId="showPlan",
     *     tags={"Planos"},
     *     @OA\Parameter(
     *         name="plan",
     *         in="path",
     *         description="ID do plano",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes do plano",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Básico"),
     *             @OA\Property(property="description", type="string", example="Acesso a cursos básicos para a primeira fase da OAB"),
     *             @OA\Property(property="price", type="number", format="float", example=29.90),
     *             @OA\Property(property="billing_cycle", type="string", example="mensal"),
     *             @OA\Property(property="discount_percentage", type="number", format="float", example=0),
     *             @OA\Property(property="features", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="stripe_product_id", type="string", nullable=true),
     *             @OA\Property(property="stripe_price_id", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Plano não encontrado"
     *     )
     * )
     */
    public function show(Plan $plan)
    {
        return response()->json($plan);
    }

    /**
     * @OA\Post(
     *     path="/admin/plans",
     *     summary="Criar plano",
     *     description="Cria um novo plano de assinatura (apenas para administradores)",
     *     operationId="createPlan",
     *     tags={"Admin", "Planos"},
     *     security={{"bearerAuth":{}}, {"oauth2":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "price", "billing_cycle"},
     *             @OA\Property(property="name", type="string", example="Plano Premium OAB"),
     *             @OA\Property(property="description", type="string", example="Acesso a todos os cursos preparatórios para 1ª e 2ª fase da OAB"),
     *             @OA\Property(property="price", type="number", format="float", example=59.90),
     *             @OA\Property(property="billing_cycle", type="string", example="mensal", enum={"mensal", "trimestral", "anual"}),
     *             @OA\Property(property="discount_percentage", type="number", format="float", example=10),
     *             @OA\Property(property="features", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Plano criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=7),
     *             @OA\Property(property="name", type="string", example="Plano Premium OAB"),
     *             @OA\Property(property="description", type="string", example="Acesso a todos os cursos preparatórios para 1ª e 2ª fase da OAB"),
     *             @OA\Property(property="price", type="number", format="float", example=59.90),
     *             @OA\Property(property="billing_cycle", type="string", example="mensal"),
     *             @OA\Property(property="discount_percentage", type="number", format="float", example=10),
     *             @OA\Property(property="features", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="is_active", type="boolean", example=true),
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => ['required', Rule::in(['mensal', 'trimestral', 'anual'])],
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'features' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $plan = Plan::create($validated);
        return response()->json($plan, 201);
    }

    /**
     * @OA\Put(
     *     path="/admin/plans/{plan}",
     *     summary="Atualizar plano",
     *     description="Atualiza um plano de assinatura existente (apenas para administradores)",
     *     operationId="updatePlan",
     *     tags={"Admin", "Planos"},
     *     security={{"bearerAuth":{}}, {"oauth2":{}}},
     *     @OA\Parameter(
     *         name="plan",
     *         in="path",
     *         description="ID do plano",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Plano Premium OAB Atualizado"),
     *             @OA\Property(property="description", type="string", example="Acesso a todos os cursos preparatórios para OAB com materiais atualizados"),
     *             @OA\Property(property="price", type="number", format="float", example=69.90),
     *             @OA\Property(property="billing_cycle", type="string", example="mensal", enum={"mensal", "trimestral", "anual"}),
     *             @OA\Property(property="discount_percentage", type="number", format="float", example=15),
     *             @OA\Property(property="features", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Plano atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Plano Premium OAB Atualizado"),
     *             @OA\Property(property="description", type="string", example="Acesso a todos os cursos preparatórios para OAB com materiais atualizados"),
     *             @OA\Property(property="price", type="number", format="float", example=69.90),
     *             @OA\Property(property="billing_cycle", type="string", example="mensal"),
     *             @OA\Property(property="discount_percentage", type="number", format="float", example=15),
     *             @OA\Property(property="features", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Plano não encontrado"
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
    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'billing_cycle' => ['sometimes', Rule::in(['mensal', 'trimestral', 'anual'])],
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'features' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $plan->update($validated);
        return response()->json($plan);
    }

    /**
     * @OA\Delete(
     *     path="/admin/plans/{plan}",
     *     summary="Excluir plano",
     *     description="Remove um plano de assinatura (apenas para administradores)",
     *     operationId="deletePlan",
     *     tags={"Admin", "Planos"},
     *     security={{"bearerAuth":{}}, {"oauth2":{}}},
     *     @OA\Parameter(
     *         name="plan",
     *         in="path",
     *         description="ID do plano",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Plano desativado com sucesso"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Plano não encontrado"
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
    public function destroy(Plan $plan)
    {
        $plan->is_active = false;
        $plan->save();
        
        return response()->json(null, 204);
    }
} 