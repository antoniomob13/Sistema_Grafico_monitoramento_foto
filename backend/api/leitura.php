<?php
// backend/api/leitura.php
require_once '../config/db.php';

$db = getDb();
$leiturasCollection = $db->selectCollection(MONGODB_COLLECTION_LEITURAS);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Ação: Inserir Nova Leitura (Simulação do Raspberry Pi)
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id_subsistema']) || empty($data['geracao']) || empty($data['bateria'])) {
            sendJsonResponse(['error' => 'Dados de leitura incompletos. id_subsistema, geracao e bateria são obrigatórios.'], 400);
        }

        try {
            // Estrutura de dados fiel ao modelo do MongoDB (com timestamp real)
            $documento = [
                // Para performance, MongoDB é rápido em indexar e buscar por UTCDateTime
                'timestamp' => new MongoDB\BSON\UTCDateTime(time() * 1000), 
                'status' => $data['status'] ?? 'Gerando',
                'frequencia_Hz' => (float)($data['frequencia_Hz'] ?? 60.0),
                'temperatura' => (float)($data['temperatura'] ?? 0),
                'geracao' => $data['geracao'], // Documento embutido (Potência, Tensão, Corrente)
                'bateria' => $data['bateria'], // Documento embutido (SOC, Tensão, Corrente)
                'painel' => $data['painel'], // Documento embutido (Tensão, Corrente, Potência)
                'id_subsistema' => $data['id_subsistema'] // Chave de busca crucial
            ];

            $result = $leiturasCollection->insertOne($documento);

            // A resposta de sucesso é leve e rápida
            sendJsonResponse([
                'message' => 'Leitura inserida com sucesso (simulação RPi).',
                'leitura_id' => (string)$result->getInsertedId()
            ], 201);

        } catch (Exception $e) {
            sendJsonResponse(['error' => 'Falha na inserção da leitura: ' . $e->getMessage()], 500);
        }
        break;

    case 'GET':
        // Ação: Consultar Leituras (Para o Dashboard/Gráficos)
        $action = $_GET['action'] ?? 'current';
        $subsistemaId = $_GET['id_subsistema'] ?? null;
        
        if (empty($subsistemaId)) {
            sendJsonResponse(['error' => 'ID do subsistema é obrigatório para consulta.'], 400);
        }

        if ($action === 'current') {
            // Consulta CRÍTICA de Desempenho: Última Leitura (Dashboard)
            try {
                // Filtra pelo subsistema e ordena DESC por timestamp, limitando a 1.
                // Requer índice em { id_subsistema: 1, timestamp: -1 } para ser rápido!
                $leituraAtual = $leiturasCollection->findOne(
                    ['id_subsistema' => $subsistemaId], 
                    ['sort' => ['timestamp' => -1]]
                );

                if ($leituraAtual) {
                    $leituraAtual['_id'] = (string)$leituraAtual['_id'];
                    $leituraAtual['timestamp'] = $leituraAtual['timestamp']->toDateTime()->format(DATE_ATOM);
                    sendJsonResponse($leituraAtual);
                } else {
                    // Resposta padronizada para o frontend lidar com sistema sem dados
                    sendJsonResponse(['message' => 'Nenhuma leitura recente encontrada.'], 404);
                }

            } catch (Exception $e) {
                sendJsonResponse(['error' => 'Erro ao buscar leitura atual: ' . $e->getMessage()], 500);
            }

        } else {
            sendJsonResponse(['error' => 'Ação de leitura inválida ou não implementada (use action=current).'], 400);
        }
        break;

    default:
        sendJsonResponse(['error' => 'Método não suportado.'], 405);
        break;
}
