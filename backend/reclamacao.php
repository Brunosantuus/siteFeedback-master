<?php
// reclamacao.php

// Definir o cabeçalho do conteúdo como JSON
header('Content-Type: application/json');

// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Configurações do banco de dados
    $servername = "localhost";
    $username = "admin";
    $password = "admin";
    $dbname = "site_feedback";

    // Criar conexão
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Verificar conexão
    if ($conn->connect_error) {
        throw new Exception("Falha na conexão com o banco de dados: " . $conn->connect_error);
    }

    // Verificar o método HTTP
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Inserir uma nova reclamação
        if (isset($_POST['usuario_id']) && isset($_POST['nome_empresa']) && isset($_POST['descricao'])) {
            // Recuperar os dados do formulário
            $usuario_id = $_POST['usuario_id'];
            $nome_empresa = $_POST['nome_empresa'];
            $descricao = $_POST['descricao'];

            // Recuperar os dados do usuário do banco de dados
            $sql_user = "SELECT nome, email, celular FROM usuario WHERE id = ?";
            $stmt_user = $conn->prepare($sql_user);
            if ($stmt_user === false) {
                throw new Exception("Erro na preparação da query: " . $conn->error);
            }

            $stmt_user->bind_param("i", $usuario_id);
            $stmt_user->execute();
            $result = $stmt_user->get_result();

            if ($result->num_rows > 0) {
                $user_data = $result->fetch_assoc();
                $usuario_nome = $user_data['nome'];
                $usuario_email = $user_data['email'];
                $usuario_celular = $user_data['celular'];

                // Preparar e executar a query para inserir os dados na tabela reclamacao
                $sql = "INSERT INTO reclamacao (usuario_id, usuario_nome, usuario_email, usuario_celular, empresa, descricao) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    throw new Exception("Erro na preparação da query: " . $conn->error);
                }

                $stmt->bind_param("isssss", $usuario_id, $usuario_nome, $usuario_email, $usuario_celular, $nome_empresa, $descricao);

                if ($stmt->execute()) {
                    // Se a inserção foi bem-sucedida, retornar uma mensagem de sucesso
                    echo json_encode(['success' => 'Reclamação enviada com sucesso!']);
                } else {
                    // Se houve algum erro na inserção, retornar uma mensagem de erro
                    throw new Exception("Erro ao enviar a reclamação: " . $stmt->error);
                }

                $stmt->close();
            } else {
                throw new Exception("Usuário não encontrado.");
            }

            $stmt_user->close();
        } else {
            // Se algum campo estiver faltando, retornar uma mensagem de erro
            throw new Exception("Todos os campos são obrigatórios.");
        }
    } elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
        // Verificar se o parâmetro "usuario_id" está presente na URL
        if (isset($_GET['usuario_id'])) {
            // Obter o ID do usuário da URL
            $usuario_id = intval($_GET['usuario_id']);

            // Preparar a consulta SQL para buscar as reclamações do usuário logado
            $sql = "SELECT id, usuario_nome, usuario_email, usuario_celular, empresa, descricao FROM reclamacao WHERE usuario_id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Erro na preparação da query: " . $conn->error);
            }

            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $reclamacoes = array();

            // Verificar se há resultados
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $reclamacoes[] = $row;
                }
            }

            // Retornar os resultados em formato JSON
            echo json_encode($reclamacoes);
        } elseif (isset($_GET['empresa'])) {
            // Pesquisar reclamações por nome de empresa
            $empresa = $_GET['empresa'];

            // Preparar a consulta SQL para buscar as reclamações por nome de empresa
            $sql = "SELECT id, usuario_nome, usuario_email, usuario_celular, empresa, descricao FROM reclamacao WHERE empresa LIKE ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Erro na preparação da query: " . $conn->error);
            }

            $empresa_param = "%" . $empresa . "%";
            $stmt->bind_param("s", $empresa_param);
            $stmt->execute();
            $result = $stmt->get_result();

            $reclamacoes = array();

            // Verificar se há resultados
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $reclamacoes[] = $row;
                }
            }

            // Retornar os resultados em formato JSON
            echo json_encode($reclamacoes);
        } else {
            // Se o parâmetro "usuario_id" não estiver presente na URL e nem o parâmetro "empresa", listar todas as reclamações
            $sql = "SELECT id, usuario_nome, usuario_email, usuario_celular, empresa, descricao FROM reclamacao";
            $result = $conn->query($sql);

            $reclamacoes = array();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $reclamacoes[] = $row;
                }
            }

            // Retornar os dados em formato JSON
            echo json_encode($reclamacoes);
        }
    } elseif ($_SERVER["REQUEST_METHOD"] == "DELETE") {
        if (isset($_REQUEST['reclamacao_id'])) {
            // Pegar o ID da reclamação
            $reclamacaoId = $_REQUEST['reclamacao_id'];

            // Preparar a declaração SQL para excluir a reclamação
            $sql_delete = "DELETE FROM reclamacao WHERE id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            if ($stmt_delete === false) {
                throw new Exception("Erro na preparação da query: " . $conn->error);
            }

            // Vincular o parâmetro e executar a declaração SQL
            $stmt_delete->bind_param("i", $reclamacaoId);
            if ($stmt_delete->execute()) {
                // Se a exclusão for bem-sucedida, enviar uma resposta JSON indicando sucesso
                echo json_encode(['success' => true]);
            } else {
                // Se houver um erro durante a exclusão, retornar uma mensagem de erro
                throw new Exception("Erro ao excluir a reclamação do banco de dados: " . $stmt_delete->error);
            }

            // Fechar a declaração
            $stmt_delete->close();
        } else {
            // Se o ID da reclamação não estiver presente na solicitação, retornar uma mensagem de erro
            http_response_code(400); // Bad request
            echo json_encode(['error' => 'ID da reclamação não especificado']);
        }
    } else {
        // Se o método da requisição não for DELETE, enviar uma resposta de erro
        http_response_code(405); // Method not allowed
        echo json_encode(['error' => 'Método não permitido']);
    }
} catch (Exception $e) {
    error_log($e->getMessage());  // Log the error
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>
