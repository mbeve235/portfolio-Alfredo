<?php
declare(strict_types=1);
ini_set('display_errors', '0');
session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off','samesite'=>'Lax']);
session_start();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function config(): array {
    if (!is_file(__DIR__.'/config.php')) { http_response_code(503); exit('Configuração pendente. Consulta o guia de instalação.'); }
    return require __DIR__.'/config.php';
}
function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $c=config();
        try { $pdo=new PDO('mysql:host='.$c['host'].';dbname='.$c['database'].';charset=utf8mb4',$c['username'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]); }
        catch (PDOException $ex) { error_log('Portfolio database connection failed'); http_response_code(503); exit('Não foi possível ligar à base de dados. Verifica a configuração no servidor.'); }
    }
    return $pdo;
}
function token(): string { return $_SESSION['csrf'] ?? ($_SESSION['csrf']=bin2hex(random_bytes(32))); }
function checkToken(): void { if (!hash_equals(token(),(string)($_POST['csrf']??''))) { http_response_code(403); exit('Sessão expirada. Volta a abrir a página.'); } }
function loadContent(): array {
    try { $row=db()->query('SELECT content FROM portfolio_content WHERE id=1')->fetchColumn(); }
    catch (PDOException $ex) { http_response_code(503); exit('Instalação pendente. Consulta o guia de instalação.'); }
    if (!$row) { http_response_code(503); exit('Conteúdo ainda não instalado.'); }
    return json_decode($row,true,512,JSON_THROW_ON_ERROR);
}
function safeUrl(string $url): string { return filter_var($url,FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($url,PHP_URL_SCHEME)??''),['https','http'],true) ? $url : ''; }
function uploadImage(string $field): ?string {
    $f=$_FILES[$field]??null;
    if (!$f || $f['error']===UPLOAD_ERR_NO_FILE) return null;
    if ($f['error']!==UPLOAD_ERR_OK || $f['size']>2*1024*1024) throw new RuntimeException('A imagem deve ter no máximo 2 MB.');
    $info=@getimagesize($f['tmp_name']);
    $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!$info || !isset($extensions[$info['mime']]) || $info[0]>6000 || $info[1]>6000) throw new RuntimeException('Usa uma imagem JPG, PNG ou WebP até 6000 × 6000.');
    $name='uploads/'.bin2hex(random_bytes(16)).'.'.$extensions[$info['mime']];
    if (!move_uploaded_file($f['tmp_name'],__DIR__.'/'.$name)) throw new RuntimeException('Não foi possível guardar a imagem.');
    return $name;
}
