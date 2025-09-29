<?php
// Arquivo de teste para verificar funcionalidades de autenticação
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

echo "<h1>Teste de Autenticação NicheNest</h1>";

// Teste 1: Verificar conexão com banco
echo "<h2>1. Teste de Conexão com Banco</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✅ Conexão OK - {$result['count']} usuários no banco<br>";
} catch (Exception $e) {
    echo "❌ Erro na conexão: " . $e->getMessage() . "<br>";
}

// Teste 2: Listar usuários existentes
echo "<h2>2. Usuários Existentes</h2>";
try {
    $stmt = $pdo->query("SELECT id, username, email FROM users");
    $users = $stmt->fetchAll();
    foreach ($users as $user) {
        echo "ID: {$user['id']} - {$user['username']} ({$user['email']})<br>";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
}

// Teste 3: Verificar se usuário está logado
echo "<h2>3. Status de Login</h2>";
if (isLoggedIn()) {
    $user = getCurrentUser();
    echo "✅ Usuário logado: " . $user['username'] . "<br>";
} else {
    echo "ℹ️ Nenhum usuário logado<br>";
}

// Teste 4: Testar criação de usuário
echo "<h2>4. Teste de Criação de Usuário</h2>";
$test_username = "usuario_teste_" . rand(1000, 9999);
$test_email = "teste_" . rand(1000, 9999) . "@example.com";
$test_password = "123456";

try {
    // Verificar se username/email já existem
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$test_username, $test_email]);
    if ($stmt->fetch()) {
        echo "ℹ️ Usuário já existe<br>";
    } else {
        // Criar usuário
        $hashedPassword = hashPassword($test_password);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, display_name, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$test_username, $test_email, $hashedPassword, $test_username]);
        echo "✅ Usuário criado: {$test_username} ({$test_email})<br>";
        
        // Testar login
        echo "<h2>5. Teste de Login</h2>";
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE email = ?");
        $stmt->execute([$test_email]);
        $user = $stmt->fetch();
        
        if ($user && verifyPassword($test_password, $user['password'])) {
            echo "✅ Login funcionando: Senha verificada corretamente<br>";
            loginUser($user['id']);
            echo "✅ Usuário logado na sessão<br>";
            
            // Verificar se está logado
            if (isLoggedIn()) {
                $current_user = getCurrentUser();
                echo "✅ Usuário atual: " . $current_user['username'] . "<br>";
            }
        } else {
            echo "❌ Erro no login: Senha não confere<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Erro na criação: " . $e->getMessage() . "<br>";
}

echo "<h2>Conclusão</h2>";
echo "Teste completo finalizado!<br>";
echo "<a href='/pages/register.php'>Testar Registro</a> | ";
echo "<a href='/pages/login.php'>Testar Login</a> | ";
echo "<a href='/pages/profile.php'>Ver Perfil</a>";
?>