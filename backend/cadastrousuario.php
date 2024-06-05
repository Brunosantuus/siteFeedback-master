<?php
$servername = "localhost";
$username = "admin";
$password = "admin";
$dbname = "site_feedback";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['error' => "Conexão falhou: " . $conn->connect_error]);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];

    if ($action == 'register') {
        $nome = $_POST['nome'];
        $cpf = $_POST['cpf'];
        $email = $_POST['email'];
        $celular = $_POST['celular'];
        $senha = $_POST['senha'];
        $sexo = $_POST['sexo'];

        $sql = "SELECT id FROM usuario WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo json_encode(['error' => 'E-mail já registrado.']);
            $stmt->close();
            $conn->close();
            exit();
        } else {
            $sql = "INSERT INTO usuario (nome, email, celular, cpf, senha, sexo) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $nome, $email, $celular, $cpf, $senha, $sexo);

            if ($stmt->execute()) {
                echo json_encode(['success' => 'Dados gravados com sucesso!']);
            } else {
                echo json_encode(['error' => 'Erro ao registrar usuário: ' . $stmt->error]);
            }

            $stmt->close();
            $conn->close();
            exit();
        }
    } elseif ($action == 'login') {
        $email = $_POST['email'];
        $senha = $_POST['senha'];

        $sql = "SELECT id FROM usuario WHERE email = ? AND senha = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $senha);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $user_id = $row['id'];
            echo json_encode(['user_id' => $user_id]);
        } else {
            echo json_encode(['error' => 'E-mail ou senha incorretos.']);
        }

        $stmt->close();
        $conn->close();
        exit();
    } elseif ($action == 'getuserdata') {
        $user_id = $_POST['user_id']; // Corrigido para recuperar o user_id do POST

        $sql = "SELECT nome, email, celular FROM usuario WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode(['nome' => $row['nome'], 'email' => $row['email'], 'celular' => $row['celular']]);
        } else {
            echo json_encode(['error' => 'Usuário não encontrado.']);
        }

        $stmt->close();
        $conn->close();
        exit();
    }
    
    elseif ($action == 'updateuserdata') {
        $user_id = $_POST['user_id'];
        $email = $_POST['email'];
        $celular = $_POST['celular'];
        $newPassword = $_POST['newPassword'];
        // Certifique-se de que a senha não esteja vazia antes de atualizá-la
        if (!empty($newPassword)) {
            $sql = "UPDATE usuario SET email = ?, celular = ?, senha = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $email, $celular, $newPassword, $user_id);
        } else {
            $sql = "UPDATE usuario SET email = ?, celular = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $email, $celular, $user_id);
        }
    
        if ($stmt->execute()) {
            echo json_encode(['success' => 'Dados atualizados com sucesso!']);
        } else {
            echo json_encode(['error' => 'Erro ao atualizar dados do usuário: ' . $stmt->error]);
        }
    
        $stmt->close();
        $conn->close();
         
        exit();
    } else {
        echo json_encode(['error' => 'Ação não reconhecida.']);
        exit();
    }
} else {
    echo json_encode(['error' => 'Método de requisição inválido.']);
    exit();
}
?>
