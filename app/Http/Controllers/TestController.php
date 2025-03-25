<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TestController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'API está funcionando!',
            'timestamp' => now(),
            'environment' => app()->environment(),
            'version' => app()->version()
        ]);
    }

    public function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            return response()->json([
                'status' => 'success',
                'message' => 'Conexão com o banco de dados estabelecida com sucesso!',
                'database' => config('database.connections.mysql.database')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao conectar com o banco de dados: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkRedis()
    {
        try {
            $testKey = 'redis_test_' . time();
            // Usar o cache padrão em vez de especificar 'redis'
            Cache::put($testKey, 'ok', 10);
            $result = Cache::get($testKey);
            
            return $result === 'ok';
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * @OA\Get(
     *     path="/status",
     *     summary="Verificar o status da API",
     *     description="Retorna o status atual da API e suas conexões",
     *     operationId="getStatus",
     *     tags={"Status"},
     *     @OA\Response(
     *         response=200,
     *         description="Status da API",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="API está funcionando!"),
     *             @OA\Property(property="timestamp", type="string", format="date-time", example="2025-03-14T22:30:51.860448Z"),
     *             @OA\Property(property="environment", type="string", example="local"),
     *             @OA\Property(property="version", type="string", example="12.2.0"),
     *             @OA\Property(property="database", type="string", example="connected"),
     *             @OA\Property(property="redis", type="string", example="connected")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao verificar o status",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Erro ao verificar serviços")
     *         )
     *     )
     * )
     */
    public function status()
    {
        $status = [
            'status' => 'success',
            'message' => 'API está funcionando!',
            'timestamp' => now(),
            'environment' => app()->environment(),
            'version' => '12.2.0',
        ];

        try {
            // Verificar o banco de dados
            $dbStatus = $this->checkDatabase();
            $status['database'] = $dbStatus ? 'connected' : 'disconnected';

            // Verificar o Redis
            $redisStatus = $this->checkRedis();
            $status['redis'] = $redisStatus ? 'connected' : 'disconnected';
        } catch (\Exception $e) {
            $status['status'] = 'error';
            $status['message'] = 'Erro ao verificar serviços: ' . $e->getMessage();
        }

        return response()->json($status);
    }
} 