# Publicar no InfinityFree

Esta é a versão PHP + MySQL. A pasta `../dist` continua a ser a versão estática anterior.

1. No painel InfinityFree, cria uma base de dados em **MySQL Databases**. Anota o hostname MySQL, o nome completo da base, o utilizador e a palavra-passe apresentados no painel. O hostname não é `localhost`.
2. Copia `config.example.php` para `config.php`. Preenche os quatro dados da base e substitui `setup_key` por uma chave aleatória com pelo menos 32 caracteres. Não publiques este ficheiro no GitHub nem partilhes as credenciais no chat.
3. No gestor de ficheiros ou por FTP, envia **o conteúdo desta pasta** para `htdocs` do domínio. Inclui `.htaccess` e a pasta `uploads` com o seu `.htaccess`. Não envies este guia nem `config.example.php`. Não é necessário Node.js ou comando de build.
4. Remove o antigo `index.html` de `htdocs`, caso exista, para não servir a versão estática. Activa HTTPS para o domínio antes de utilizar o painel.
5. Abre `https://TEU-DOMINIO/install.php`. Introduz a chave configurada e cria o teu utilizador e uma palavra-passe com pelo menos 12 caracteres. A instalação cria as tabelas e importa o conteúdo de `seed.json` automaticamente. Não é necessário importar SQL no phpMyAdmin.
6. Depois da instalação, apaga `install.php` e `seed.json` do servidor. A instalação também fica bloqueada automaticamente depois de existir um administrador.
7. Entra em `https://TEU-DOMINIO/admin.php` para editar os textos, contactos, competências, formação e projectos ou carregar imagens JPG, PNG e WebP até 2 MB. As alterações guardadas aparecem no site sem novo upload de código.

## Organização no servidor

```text
htdocs/
  index.php
  admin.php
  lib.php
  config.php
  .htaccess
  app.js
  style.css
  admin.css
  uploads/
    .htaccess
```

`install.php` e `seed.json` são necessários apenas durante a instalação inicial.

## Manutenção

- Faz cópias da base de dados pelo phpMyAdmin e da pasta `uploads`. Ambas são necessárias para recuperar o conteúdo.
- Não substituas `config.php` nem apagues `uploads` ao actualizar o código.
- Imagens substituídas deixam de aparecer, mas permanecem na pasta para evitar apagar ficheiros ainda referenciados. Podes limpar imagens antigas depois de fazer uma cópia.
- A opção “Demonstração / segundo link” permite conservar o segundo repositório de AMBEBE.
- Esta versão guarda perfil, competências e projectos num documento JSON dentro de uma tabela MySQL. O PHP lê a base de dados em cada pedido; `seed.json` só serve para a primeira instalação.
- O painel destina-se a um administrador. Cinco falhas de acesso bloqueiam novas tentativas durante 15 minutos; a sessão expira após uma hora sem actividade.
- Esta versão deve ser alojada num servidor PHP como o InfinityFree. O endereço anterior no Sites continua a servir a versão estática.

## Desenvolvimento local

Coloca esta pasta em `C:/xampp/htdocs/portfolio`, inicia Apache e MySQL no XAMPP e configura uma base local em `config.php`. Abre `http://localhost/portfolio/install.php`. Usa credenciais locais apenas no computador; no InfinityFree substitui-as pelas credenciais do alojamento.
