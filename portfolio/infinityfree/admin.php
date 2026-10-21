<?php
require __DIR__.'/lib.php';
header('Cache-Control: no-store');
$pdo=db();
try { $admin=$pdo->query('SELECT * FROM portfolio_admin WHERE id=1')->fetch(PDO::FETCH_ASSOC); }
catch (PDOException $ex) { header('Location: install.php'); exit; }
if (!$admin) { header('Location: install.php'); exit; }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    checkToken(); $action=$_POST['action']??'';
    if ($action==='logout') { $_SESSION=[]; session_destroy(); header('Location: admin.php'); exit; }
    if ($action==='login') {
        if (!empty($admin['locked_until']) && strtotime($admin['locked_until'])>time()) $error='Demasiadas tentativas. Aguarda 15 minutos.';
        elseif (hash_equals($admin['username'],(string)($_POST['username']??'')) && password_verify($_POST['password']??'',$admin['password_hash'])) {
            session_regenerate_id(true); $_SESSION['admin']=true; $_SESSION['last_activity']=time();
            $pdo->exec('UPDATE portfolio_admin SET failures=0,locked_until=NULL WHERE id=1'); header('Location: admin.php'); exit;
        } else {
            $failures=(int)$admin['failures']+1;
            $pdo->prepare('UPDATE portfolio_admin SET failures=?,locked_until=? WHERE id=1')->execute([$failures>=5?0:$failures,$failures>=5?date('Y-m-d H:i:s',time()+900):null]);
            $error='Dados de acesso inválidos.';
        }
    }
}
if (isset($_SESSION['last_activity']) && time()-$_SESSION['last_activity']>3600) unset($_SESSION['admin']);
$logged=!empty($_SESSION['admin']);
if ($logged) $_SESSION['last_activity']=time();
$content=$logged?loadContent():[];
if ($logged && $_SERVER['REQUEST_METHOD']==='POST' && !in_array($action,['login','logout'],true)) {
    try {
        if ($action==='profile') {
            foreach (['name','title','headline','intro','about','education','institution','contact_text','email'] as $key) $content['profile'][$key]=mb_substr(trim((string)($_POST[$key]??'')),0,10000);
            foreach (['github','linkedin','whatsapp'] as $key) { $value=trim($_POST[$key]??''); if ($value!=='' && !safeUrl($value)) throw new RuntimeException('Introduz links completos com https://.'); $content['profile'][$key]=$value; }
            if ($content['profile']['email']!=='' && !filter_var($content['profile']['email'],FILTER_VALIDATE_EMAIL)) throw new RuntimeException('E-mail inválido.');
            foreach (['languages','frameworks','tools','specialties'] as $key) $content['skills'][$key]=array_values(array_filter(array_map('trim',explode(',',mb_substr($_POST[$key]??'',0,3000)))));
            if ($image=uploadImage('photo')) $content['profile']['photo']=$image;
            if (isset($_POST['remove_photo'])) $content['profile']['photo']='';
        } elseif ($action==='project') {
            $id=(string)($_POST['id']??''); $index=null;
            foreach ($content['projects'] as $i=>$p) if ($p['id']===$id) $index=$i;
            $p=$index===null?['id'=>bin2hex(random_bytes(8)),'bg'=>'#e9effe','accent'=>'#2563eb','visual'=>'dashboard','image'=>'']:$content['projects'][$index];
            foreach (['title','type','description'] as $key) $p[$key]=mb_substr(trim($_POST[$key]??''),0,5000);
            if ($p['title']==='') throw new RuntimeException('O projecto precisa de um título.');
            $p['category']=in_array($_POST['category']??'',['web','desktop','auto'],true)?$_POST['category']:'web';
            $p['tags']=array_values(array_filter(array_map('trim',explode(',',mb_substr($_POST['tags']??'',0,1000)))));
            $p['planned']=isset($_POST['planned']); $p['label']=$p['title']; $p['links']=[];
            foreach (['repository'=>'Repositório','demo'=>'Demonstração'] as $key=>$label) { $url=trim($_POST[$key]??''); if ($url && !safeUrl($url)) throw new RuntimeException('Link inválido. Usa https://.'); if ($url) $p['links'][]=[$label,$url]; }
            if ($image=uploadImage('image')) $p['image']=$image;
            if (isset($_POST['remove_image'])) $p['image']='';
            if ($index===null) $content['projects'][]=$p; else $content['projects'][$index]=$p;
        } elseif ($action==='delete') {
            $content['projects']=array_values(array_filter($content['projects'],fn($p)=>$p['id']!==($_POST['id']??'')));
        } elseif ($action==='password') {
            if (!password_verify($_POST['current_password']??'',$admin['password_hash']) || strlen($_POST['new_password']??'')<12) throw new RuntimeException('Confirma a palavra-passe actual e usa pelo menos 12 caracteres na nova.');
            $pdo->prepare('UPDATE portfolio_admin SET password_hash=? WHERE id=1')->execute([password_hash($_POST['new_password'],PASSWORD_DEFAULT)]);
        } else throw new RuntimeException('Operação inválida.');
        $pdo->prepare('UPDATE portfolio_content SET content=? WHERE id=1')->execute([json_encode($content,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
        header('Location: admin.php?saved=1'); exit;
    } catch (RuntimeException $ex) { $error=$ex->getMessage(); } catch (Throwable $ex) { $error='Não foi possível guardar. Tenta novamente.'; }
}
function hiddenFields(string $action): void { echo '<input type="hidden" name="csrf" value="'.e(token()).'"><input type="hidden" name="action" value="'.e($action).'">'; }
require __DIR__.'/admin-view.php';
