<?php
// Demonstração: Como funciona a criptografia de senhas
echo "<h1>🔐 Como Funciona a Criptografia de Senhas</h1>";

// Vamos testar com senhas de exemplo
$senhas_teste = [
    "123456",
    "password", 
    "minhasenha123",
    "SuperSenh@2024"
];

echo "<h2>📝 PROCESSO DE CRIPTOGRAFIA</h2>";

foreach ($senhas_teste as $senha_original) {
    echo "<div style='border: 1px solid #ccc; padding: 15px; margin: 10px; background: #f9f9f9;'>";
    echo "<h3>🔑 Senha Original: <span style='color: red;'>$senha_original</span></h3>";
    
    // PASSO 1: Gerar o hash
    $hash = password_hash($senha_original, PASSWORD_DEFAULT);
    echo "<h4>🔒 Hash Gerado:</h4>";
    echo "<code style='background: #ffffcc; padding: 5px; word-break: break-all;'>$hash</code><br><br>";
    
    // PASSO 2: Mostrar informações do hash
    echo "<h4>📊 Informações do Hash:</h4>";
    echo "• <strong>Tamanho:</strong> " . strlen($hash) . " caracteres<br>";
    echo "• <strong>Algoritmo:</strong> bcrypt (mais seguro)<br>";
    echo "• <strong>Custo:</strong> 10 (nível de dificuldade)<br>";
    
    // PASSO 3: Testar verificação
    echo "<h4>✅ Teste de Verificação:</h4>";
    $resultado_correto = password_verify($senha_original, $hash);
    $resultado_errado = password_verify("senhaerrada", $hash);
    
    echo "• Senha correta ('$senha_original'): " . ($resultado_correto ? "✅ ACEITA" : "❌ REJEITADA") . "<br>";
    echo "• Senha errada ('senhaerrada'): " . ($resultado_errado ? "✅ ACEITA" : "❌ REJEITADA") . "<br>";
    
    echo "</div>";
}

echo "<h2>🤔 CURIOSIDADES IMPORTANTES</h2>";
echo "<div style='background: #e8f5e8; padding: 15px; border-left: 4px solid green;'>";
echo "<h3>🔄 O Hash é SEMPRE Diferente!</h3>";
echo "Mesmo com a mesma senha, o hash muda sempre:<br><br>";

$senha = "123456";
for ($i = 1; $i <= 3; $i++) {
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    echo "<strong>Tentativa $i:</strong> <code style='font-size: 10px;'>$hash</code><br>";
}

echo "<br><strong>💡 Por quê?</strong> O PHP adiciona um 'sal' (salt) aleatório para tornar cada hash único!";
echo "</div>";

echo "<h2>🛡️ POR QUE É SEGURO?</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid orange;'>";
echo "<ul>";
echo "<li><strong>Irreversível:</strong> Não tem como 'descriptografar' o hash para descobrir a senha</li>";
echo "<li><strong>Salt automático:</strong> Cada hash tem um 'tempero' único que impede ataques</li>";
echo "<li><strong>Slow by design:</strong> O algoritmo é propositalmente lento para dificultar ataques</li>";
echo "<li><strong>Sempre diferente:</strong> Mesma senha = hash diferente sempre</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🔍 COMO FUNCIONA NO SEU SISTEMA</h2>";
echo "<div style='background: #d1ecf1; padding: 15px; border-left: 4px solid blue;'>";
echo "<h3>📝 No Cadastro:</h3>";
echo "<code>
1. Usuário digita: 'minhasenha123'<br>
2. PHP executa: hashPassword('minhasenha123')<br>
3. Gera hash: \$2y\$10\$abc123...xyz789<br>
4. Salva no banco: apenas o hash, nunca a senha real
</code>";

echo "<h3>🔑 No Login:</h3>";
echo "<code>
1. Usuário digita: 'minhasenha123'<br>
2. Busca o hash salvo no banco<br>
3. PHP executa: password_verify('minhasenha123', hash_do_banco)<br>
4. Retorna: true se confere, false se não confere
</code>";
echo "</div>";

echo "<h2>👀 VAMOS VER NO SEU BANCO DE DADOS</h2>";
echo "<a href='#' onclick='mostrarBanco()' style='background: #007bff; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>Ver Senhas no Banco</a>";

echo "<div id='banco' style='display:none; margin-top: 20px; background: #f8f9fa; padding: 15px; border: 1px solid #ddd;'>";

require_once __DIR__ . '/includes/config.php';

try {
    $stmt = $pdo->query("SELECT id, username, email, password FROM users LIMIT 3");
    $users = $stmt->fetchAll();
    
    echo "<h3>👥 Usuários no Banco:</h3>";
    echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #343a40; color: white;'>";
    echo "<th style='padding: 10px;'>ID</th>";
    echo "<th style='padding: 10px;'>Username</th>";
    echo "<th style='padding: 10px;'>Email</th>";
    echo "<th style='padding: 10px;'>Hash da Senha (Criptografado)</th>";
    echo "</tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td style='padding: 8px; text-align: center;'>{$user['id']}</td>";
        echo "<td style='padding: 8px;'>{$user['username']}</td>";
        echo "<td style='padding: 8px;'>{$user['email']}</td>";
        echo "<td style='padding: 8px; font-family: monospace; font-size: 10px; word-break: break-all;'>{$user['password']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color: red; font-weight: bold;'>🔒 Viu? No banco só tem o hash! A senha real NUNCA é salva!</p>";
    
} catch (Exception $e) {
    echo "Erro ao buscar dados: " . $e->getMessage();
}

echo "</div>";

echo "<script>
function mostrarBanco() {
    document.getElementById('banco').style.display = 'block';
}
</script>";

echo "<h2>🎯 RESUMO</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid green;'>";
echo "<p><strong>Quando você se cadastra em um site:</strong></p>";
echo "<ul>";
echo "<li>Sua senha vira um 'código secreto' impossível de decodificar</li>";
echo "<li>O site nunca sabe sua senha real</li>";
echo "<li>Na hora do login, ele compara os 'códigos' para ver se bate</li>";
echo "<li>Mesmo se hackearem o banco, não conseguem sua senha!</li>";
echo "</ul>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h1, h2, h3 { color: #333; }
code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
</style>