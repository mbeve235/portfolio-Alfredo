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
?><!doctype html><html lang="pt"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Administração · Portefólio</title><link rel="stylesheet" href="admin.css"></head><body><main><div class="row"><h1>O meu portefólio</h1><a href="index.php">Ver site ↗</a></div><p role="alert"><?=e($error)?></p>
<?php if (!$logged): ?><form method="post"><?php hiddenFields('login'); ?><label>Utilizador<input name="username" required autocomplete="username"></label><label>Palavra-passe<input name="password" type="password" required autocomplete="current-password"></label><button>Entrar</button></form>
<?php else: ?><?php if(isset($_GET['saved'])): ?><p role="status">Alterações guardadas.</p><?php endif; ?><form method="post"><?php hiddenFields('logout'); ?><button>Sair</button></form>
<h2>Apresentação e contactos</h2><form method="post" enctype="multipart/form-data"><?php hiddenFields('profile'); ?>
<?php foreach (['name'=>'Nome','title'=>'Título profissional','headline'=>'Título principal','intro'=>'Apresentação inicial','about'=>'Sobre mim (separa parágrafos com uma linha em branco)','education'=>'Formação','institution'=>'Instituição e estado','contact_text'=>'Texto de contacto','email'=>'E-mail','github'=>'GitHub (URL)','linkedin'=>'LinkedIn (URL)','whatsapp'=>'WhatsApp (https://wa.me/...)'] as $key=>$label): ?><label><?=e($label)?><?php if(in_array($key,['intro','about','contact_text'])): ?><textarea name="<?=e($key)?>"><?=e($content['profile'][$key]??'')?></textarea><?php else: ?><input name="<?=e($key)?>" value="<?=e($content['profile'][$key]??'')?>"><?php endif; ?></label><?php endforeach; ?>
<?php foreach (['languages'=>'Linguagens','frameworks'=>'Frameworks e bases de dados','tools'=>'Ferramentas','specialties'=>'Especialidades'] as $key=>$label): ?><label><?=e($label)?> (separadas por vírgulas)<input name="<?=e($key)?>" value="<?=e(implode(', ',$content['skills'][$key]))?>"></label><?php endforeach; ?>
<label>Fotografia (JPG, PNG, WebP; até 2 MB)<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label><?php if(!empty($content['profile']['photo'])): ?><img src="<?=e($content['profile']['photo'])?>" alt="Fotografia actual"><label><input type="checkbox" name="remove_photo">Remover fotografia</label><?php endif; ?><button>Guardar apresentação</button></form>
<h2>Projectos</h2><p>Abre um projecto para editar, ou adiciona um novo. As imagens substituem as ilustrações.</p>
<?php foreach (array_merge($content['projects'],[['id'=>'','title'=>'Novo projecto','tags'=>[],'links'=>[]]]) as $p): ?><details><summary><?=e($p['title'])?></summary><article><form method="post" enctype="multipart/form-data"><?php hiddenFields('project'); ?><input type="hidden" name="id" value="<?=e($p['id'])?>"><label>Título<input name="title" value="<?=e($p['id']?$p['title']:'')?>" required></label><label>Subtítulo<input name="type" value="<?=e($p['type']??'')?>"></label><label>Descrição<textarea name="description"><?=e($p['description']??'')?></textarea></label><label>Categoria<select name="category"><?php foreach(['web'=>'Web','desktop'=>'Desktop','auto'=>'Automação'] as $v=>$label): ?><option value="<?=$v?>" <?=($p['category']??'web')===$v?'selected':''?>><?=$label?></option><?php endforeach; ?></select></label><label>Tecnologias (vírgulas)<input name="tags" value="<?=e(implode(', ',$p['tags']))?>"></label><label>Repositório (URL)<input name="repository" type="url" value="<?=e($p['links'][0][1]??'')?>"></label><label>Demonstração / segundo link (URL)<input name="demo" type="url" value="<?=e($p['links'][1][1]??'')?>"></label><label><input type="checkbox" name="planned" <?=!empty($p['planned'])?'checked':''?>>Projecto planeado</label><label>Imagem (até 2 MB)<input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label><?php if(!empty($p['image'])): ?><img src="<?=e($p['image'])?>" alt="Captura actual"><label><input type="checkbox" name="remove_image">Remover imagem</label><?php endif; ?><button>Guardar projecto</button></form><?php if($p['id']): ?><form method="post" onsubmit="return confirm('Eliminar este projecto?')"><?php hiddenFields('delete'); ?><input type="hidden" name="id" value="<?=e($p['id'])?>"><p><button class="danger">Eliminar projecto</button></p></form><?php endif; ?></article></details><?php endforeach; ?>
<h2>Alterar palavra-passe</h2><form method="post"><?php hiddenFields('password'); ?><label>Palavra-passe actual<input type="password" name="current_password" required autocomplete="current-password"></label><label>Nova palavra-passe<input type="password" name="new_password" minlength="12" required autocomplete="new-password"></label><button>Alterar palavra-passe</button></form>
<?php endif; ?></main></body></html>
