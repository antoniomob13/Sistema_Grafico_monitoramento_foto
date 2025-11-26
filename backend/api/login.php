<?php
// backend/api/login.php
require_once '../config/db.php';

$db = getDb();
$usuariosCollection = $db->selectCollection(MONGODB_COLLECTION_USUARIOS);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['error' => 'Método não permitido.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$senha = $data['password'] ?? '';

if (empty($email) || empty($senha)) {
    sendJsonResponse(['error' => 'Email e senha são obrigatórios.'], 400);
}

try {
    // Buscar o usuário. Em produção, use password_verify() para a senha.
    $user = $usuariosCollection->findOne(['email' => $email]);

    if ($user && $senha === '123') { 
        unset($user['senha']); // Nunca retorne a senha
        
        // Conversão dos IDs (ObjectId) para string para o JS entender
        $user['_id'] = (string)$user['_id'];
        if (isset($user['sistema_id'])) {
             // Garante que o ID do sistema, se existir, também seja string
             $user['sistema_id'] = (string)$user['sistema_id'];
        }

        sendJsonResponse(['message' => 'Login bem-sucedido.', 'user' => $user]);
    } else {
        sendJsonResponse(['error' => 'Credenciais inválidas.'], 401);
    }
} catch (Exception $e) {
    sendJsonResponse(['error' => 'Erro interno de autenticação.'], 500);
}
