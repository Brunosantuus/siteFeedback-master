<?php

$servername = "localhost";
$username = "admin";
$password = "admin";
$dbname = "site_feedback";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    $response = ['error' => "Erro de conexão com o banco de dados: " . $conn->connect_error];
    http_response_code(500);
    echo json_encode($response);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];

    if ($action == 'register_empresa') {
        $nome_empresa = $_POST['nome_empresa'];
        $cnpj = $_POST['cnpj'];
        $email = $_POST['email'];
        $senha = $_POST['senha'];

        $sql = "SELECT id FROM empresa WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $response = ['error' => 'E-mail já registrado.'];
            http_response_code(400);
            echo json_encode($response);
            exit();
        } else {
            $sql = "INSERT INTO empresa (nome_empresa, cnpj, email, senha) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $nome_empresa, $cnpj, $email, $senha);

            if ($stmt->execute()) {
                $response = ['success' => 'Dados gravados com sucesso!'];
                http_response_code(200);
                echo json_encode($response);
                exit();
            } else {
                $response = ['error' => 'Erro ao registrar usuário.'];
                http_response_code(500);
                echo json_encode($response);
                exit();
            }
        }
    } elseif ($action == 'login_empresa') {
        $email = $_POST['email'];
        $senha = $_POST['senha'];

        $sql = "SELECT id, senha, nome_empresa FROM empresa WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $senha_armazenada = $row['senha'];

            if ($senha === $senha_armazenada) {
                // Senha correta, login bem-sucedido
                session_start();
                $_SESSION['id'] = $row['id'];
                $_SESSION['nome_empresa'] = $row['nome_empresa']; 
                $response = ['success' => true];
                http_response_code(200);
                echo json_encode($response);
                exit();
            } else {
                // Senha incorreta
                $response = ['error' => 'Senha incorreta.'];
                http_response_code(401);
                echo json_encode($response);
                exit();
            }
        } else {
            // E-mail não encontrado
            $response = ['error' => 'E-mail não encontrado.'];
            http_response_code(404);
            echo json_encode($response);
            exit();
        }
    } else {
        $response = ['error' => 'Ação não reconhecida.'];
        http_response_code(400);
        echo json_encode($response);
        exit();
    }
} else {
    $response = ['error' => 'Método não permitido.'];
    http_response_code(405);
    echo json_encode($response);
    exit();
}

?>
