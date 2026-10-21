<?php
require __DIR__.'/lib.php';
$pdo=db();
$pdo->exec('CREATE TABLE IF NOT EXISTS portfolio_admin (id INT PRIMARY KEY, username VARCHAR(100) NOT NULL, password_hash VARCHAR(255) NOT NULL, failures INT NOT NULL DEFAULT 0, locked_until DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$pdo->exec('CREATE TABLE IF NOT EXISTS portfolio_content (id INT PRIMARY KEY, content LONGTEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
if ($pdo->query('SELECT COUNT(*) FROM portfolio_admin')->fetchColumn()) { http_response_code(403); exit('A instalação já foi concluída. Entra em admin.php.'); }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    checkToken(); $c=config();
    if (strlen($c['setup_key'])<32 || str_contains($c['setup_key'],'SUBSTITUIR') || !hash_equals($c['setup_key'],(string)($_POST['setup_key']??''))) $error='Chave de instalação inválida.';
    elseif (strlen($_POST['password']??'')<12 || !preg_match('/^[a-zA-Z0-9_.-]{3,100}$/',$_POST['username']??'')) $error='Usa um utilizador de 3 a 100 caracteres e uma palavra-passe com pelo menos 12 caracteres.';
    else {
        try {
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO portfolio_admin (id,username,password_hash) VALUES (1,?,?)')->execute([$_POST['username'],password_hash($_POST['password'],PASSWORD_DEFAULT)]);
            $pdo->prepare('INSERT INTO portfolio_content (id,content) VALUES (1,?)')->execute([file_get_contents(__DIR__.'/seed.json')]);
            $pdo->commit(); header('Location: admin.php'); exit;
        } catch (Throwable $ex) { if ($pdo->inTransaction()) $pdo->rollBack(); $error='Não foi possível concluir a instalação. Verifica se já foi instalada.'; }
    }
}
?><!doctype html><html lang="pt"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalar portefólio</title><link rel="stylesheet" href="admin.css"><main><h1>Instalar portefólio</h1><p>Configura o teu acesso ao painel. Esta operação só pode ser concluída uma vez.</p><p role="alert"><?=e($error)?></p><form method="post"><input type="hidden" name="csrf" value="<?=e(token())?>"><label>Chave de instalação<input name="setup_key" type="password" required></label><label>Utilizador<input name="username" required pattern="[a-zA-Z0-9_.-]{3,100}" autocomplete="username"></label><label>Palavra-passe<input name="password" type="password" minlength="12" required autocomplete="new-password"></label><button>Instalar</button></form></main></html>
