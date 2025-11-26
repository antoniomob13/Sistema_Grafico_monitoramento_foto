<?php
// backend/config/db.php
// Configuração de Conexão com o MongoDB
require_once dirname(__DIR__) . '/vendor/autoload.php';

use MongoDB\Client;
use MongoDB\BSON\ObjectId;

// ----------------------------------------------------
// !!! ATENÇÃO: ALTERE ESTES VALORES PARA SUA CONEXÃO !!!
if (!defined('MONGODB_URI')) {
    define('MONGODB_URI', 'mongodb://localhost:27017'); // Ex: mongodb://user:pass@host:port
}
if (!defined('MONGODB_DATABASE')) {
    define('MONGODB_DATABASE', 'Monitoramento_fotovoltaico');
}
// ----------------------------------------------------
if (!defined('MONGODB_COLLECTION_SISTEMAS')) {
    define('MONGODB_COLLECTION_SISTEMAS', 'Sistema');
}
if (!defined('MONGODB_COLLECTION_LEITURAS')) {
    define('MONGODB_COLLECTION_LEITURAS', 'Leitura');
}
if (!defined('MONGODB_COLLECTION_USUARIOS')) {
    define('MONGODB_COLLECTION_USUARIOS', 'Usuario'); // Para login/clientes
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

/**
 * Retorna a instância do banco de dados MongoDB.
 * @return \MongoDB\Database
 */
if (!function_exists('getDb')) {
    function getDb() {
        try {
            $client = new Client(MONGODB_URI);
            // Ping para verificar a conexão
            $client->selectDatabase(MONGODB_DATABASE)->command(['ping' => 1]);
            return $client->selectDatabase(MONGODB_DATABASE);
        } catch (Exception $e) {
            // Erro fatal em caso de falha de conexão (essencial para teste de ambiente)
            die("ERRO DE CONEXÃO COM O BANCO DE DADOS: Verifique o URI e a extensão PHP MongoDB. Mensagem: " . $e->getMessage());
        }
    }
}

/**
 * Converte um ID de string para um objeto ObjectId do MongoDB.
 * @param string $id
 * @return \MongoDB\BSON\ObjectId|null
 */
if (!function_exists('toObjectId')) {
    function toObjectId(string $id) {
        if (preg_match('/^[a-f0-9]{24}$/i', $id)) {
            return new ObjectId($id);
        }
        return null; 
    }
}

/**
 * Função utilitária para enviar resposta JSON e encerrar.
 * @param array $data
 * @param int $status
 */
if (!function_exists('sendJsonResponse')) {
    function sendJsonResponse(array $data, int $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * Simula a criação de dados iniciais para que o login funcione.
 * **Em ambiente real, popule o BD manualmente.**
 */
if (!function_exists('checkAndSeedUsers')) {
    function checkAndSeedUsers() {
        $db = getDb();
        $usersCollection = $db->selectCollection(MONGODB_COLLECTION_USUARIOS);
        $count = $usersCollection->countDocuments([]);

        if ($count === 0) {
            $usersCollection->insertMany([
                [
                    'nome' => 'Maria Ribeirinha', 'email' => 'cliente@ufopa.br', 'senha' => '123', 
                    'tipo' => 'cliente', 'endereco' => 'Comunidade A, Casa 2', 'tel' => '93991112233',
                    // Simulando que 'sys1' é um ObjectId válido para fins de teste
                    'sistema_id' => '656461746f6c616265727379' 
                ],
                [
                    'nome' => 'Dr. Carlos Pesquisador', 'email' => 'admin@ufopa.br', 'senha' => '123', 
                    'tipo' => 'admin', 'endereco' => 'Campus UFOPA', 'sistema_id' => null, 'tel' => '93994445566'
                ]
            ]);
            error_log("Dados iniciais de usuário (Seed) inseridos.");
        }
    }
}

// Chame a função para garantir que existam usuários de teste
checkAndSeedUsers();
