# Guia — Subir o Sistema para Hospedagem Compartilhada (cPanel/Plesk)

Este guia assume uma hospedagem tipo cPanel (Hostinger, GoDaddy, HostGator
etc.) — sem acesso root, com painel visual. Se o seu plano tiver o app
**"Terminal"** dentro do cPanel, alguns passos ficam mais rápidos (indicado
abaixo); se não tiver, tem alternativa pelo painel visual pra tudo.

## 0. Checklist do que você precisa ter em mãos

- [ ] Acesso ao cPanel/Plesk (usuário e senha).
- [ ] Domínio ou subdomínio já apontando pra essa hospedagem (ex:
      `post.aviabrasil.com.br`).
- [ ] Acesso FTP/SFTP ou o **Gerenciador de Arquivos** do cPanel.
- [ ] As credenciais reais do Instagram (`instagram.appId`/`appSecret`) que
      você já colocou no `.env` local.

## 1. Preparar o projeto localmente antes de enviar

No seu computador (aqui mesmo, eu faço isso pra você quando pedir), rodamos:

```
composer install --no-dev --optimize-autoloader
```

Isso baixa só as dependências de produção (sem PHPUnit/Faker, que só
servem pra testes) e otimiza o autoload — deixa o `vendor/` mais leve e
rápido pra subir.

## 2. Verificar a versão do PHP no cPanel

1. No cPanel, procure **"Selecionar Versão do PHP"** (Select PHP Version)
   ou **"MultiPHP Manager"**.
2. Escolha **PHP 8.1 ou superior** (o projeto foi desenvolvido em PHP 8.2).
3. Confirme que estas extensões estão habilitadas (normalmente já vêm
   ligadas por padrão): `intl`, `mbstring`, `mysqli`, `curl`, `json`,
   `openssl`, `gd`.

## 3. Onde colocar os arquivos — seu caso: subdomínio `post.aviabrasil.com.br`

O sistema tem uma pasta `public/` que é a **única** que deveria ficar
acessível pela internet — o resto (`app/`, `.env`, `vendor/` etc.) guarda
coisa sensível e não deveria ficar exposto. A boa notícia: criar um
**subdomínio** no cPanel já pede a pasta de destino na hora de criar, então
isso já sai certo desde o início, sem truque nenhum.

1. No cPanel, vá em **"Subdomínios"** (Subdomains).
2. Em "Subdomínio", digite `post` (o domínio principal
   `aviabrasil.com.br` já vem selecionado ao lado).
3. No campo **"Raiz do Documento"** (Document Root), o cPanel sugere um
   caminho automático (tipo `public_html/post.aviabrasil.com.br`) —
   **apague e digite este caminho no lugar**, apontando direto pra pasta
   `public/` do projeto:
   ```
   post_sistema/public
   ```
   *(O cPanel já entende isso como relativo à sua pasta pessoal —
   `/home/seuusuario/post_sistema/public`. O nome `post_sistema`
   pode ser o que você quiser, só precisa bater com o nome da pasta que
   você vai enviar por FTP no passo 8.)*
4. Clique em **"Criar"**.

Depois disso, envie o projeto inteiro (via FTP ou Gerenciador de Arquivos)
para dentro de `/home/seuusuario/post_sistema/` — ou seja, a pasta
`public/` do projeto vai ficar em
`/home/seuusuario/post_sistema/public/`, que é exatamente a pasta que
você apontou no passo 3. Assim `app/`, `.env` e `vendor/` ficam fora do
alcance de qualquer URL — só o que está dentro de `public/` responde pela
internet.

> Se em algum momento o campo "Raiz do Documento" não deixar digitar um
> caminho customizado (alguns cPaneis mais antigos travam isso), me avise
> que preparo a alternativa com `.htaccess` na raiz.

## 4. Criar o banco de dados

1. No cPanel, vá em **"Bancos de Dados MySQL"**.
2. Crie um banco (o cPanel geralmente prefixa com seu usuário, ex:
   `seuusuario_avia_brasil`).
3. Crie um usuário MySQL com senha forte, e **adicione esse usuário ao
   banco** com todos os privilégios.
4. Anote: nome do banco, usuário e senha — vai pro `.env`.

### Importar as tabelas

**Se seu plano tem Terminal (cPanel → "Terminal"):**
```
cd ~/post_sistema
php spark migrate --all
php spark db:seed AdminSeeder
```

**Se não tem Terminal:** use o **phpMyAdmin** (cPanel → "phpMyAdmin"):
1. Selecione o banco que você criou.
2. Vá em **"Importar"** → escolha o arquivo `docs/schema_avia_brasil_postagens.sql`
   (já preparei esse arquivo com toda a estrutura das 7 tabelas prontas).
3. Depois de importar, crie o administrador manualmente rodando este SQL
   no próprio phpMyAdmin (aba "SQL") — troque o e-mail/senha antes:
   ```sql
   INSERT INTO usuarios (nome, email, senha_hash, automacao_ativa, admin)
   VALUES ('Administrador', 'heveraldo@aviabrasil.com.br', '{HASH_AQUI}', 0, 1);
   ```
   O `{HASH_AQUI}` precisa ser gerado — me avise nessa etapa que eu gero o
   hash da senha que você quiser (nunca cole a senha em texto puro no
   banco).

## 5. Configurar o `.env` de produção

Copie o `.env` local, mas **mude estes valores** (não suba o `.env` de
desenvolvimento como está):

```
CI_ENVIRONMENT = production

app.baseURL = 'https://post.aviabrasil.com.br/'

database.default.hostname = localhost
database.default.database = seuusuario_avia_brasil
database.default.username = seuusuario_dbuser
database.default.password = SENHA_DO_BANCO_AQUI
database.default.DBDriver = MySQLi

encryption.key = (gerar uma NOVA, não reaproveitar a local — ver abaixo)


ADMIN_EMAIL = 'seu-email-real@aviabrasil.com.br'
ADMIN_SENHA = 'senha-forte-de-verdade'

instagram.appId       = "946384241057039"
instagram.appSecret   = "SEU_APP_SECRET"
instagram.redirectUri = "https://post.aviabrasil.com.br/instagram/retorno"
```

**Pontos de atenção:**
- `app.baseURL` e `instagram.redirectUri` precisam usar **HTTPS** — a Meta
  exige HTTPS pra OAuth em produção (só aceita `http://localhost` em
  desenvolvimento).
- **Gere uma chave de criptografia nova** pra produção (não reaproveite a
  do `.env` local) — se tiver Terminal: `php spark key:generate`. Sem
  Terminal, me avise que eu gero uma aqui e te passo pra colar.
- Depois de mudar `instagram.redirectUri`, **atualize também no painel do
  Meta for Developers** (App Dashboard → Instagram → API setup with
  Instagram login → OAuth redirect URIs) — os dois precisam ser idênticos.

## 6. Ativar HTTPS (SSL)

No cPanel, procure **"SSL/TLS Status"** ou **"AutoSSL"** e ative o
certificado gratuito (Let's Encrypt) pro seu domínio. A maioria dos
cPaneis já faz isso automaticamente, mas vale conferir o cadeado
aparecendo no navegador antes de seguir.

## 7. Configurar os Cron Jobs (a automação de verdade)

No cPanel, vá em **"Cron Jobs"**:

1. Primeiro descubra o caminho completo do PHP CLI do seu plano — normalmente
   algo como `/opt/cpanel/ea-php82/root/usr/bin/php` (varia por host; o
   próprio cPanel geralmente sugere isso, ou o suporte da hospedagem
   informa).
2. Adicione dois cron jobs, **rodando 1x por hora**:

   **Varredura da pasta central** (a cada hora, minuto 5):
   ```
   Minuto: 5   Hora: *   Dia: *   Mês: *   Dia da semana: *
   Comando: /caminho/do/php /home/seuusuario/post_sistema/spark midia:varrer
   ```

   **Motor de publicação** (a cada hora, minuto 10 — depois da varredura):
   ```
   Minuto: 10   Hora: *   Dia: *   Mês: *   Dia da semana: *
   Comando: /caminho/do/php /home/seuusuario/post_sistema/spark posts:publicar
   ```

   *(Não precisam rodar no mesmo minuto — dei uma folga de 5 min entre os
   dois de propósito, pra garantir que a varredura de mídia nova termine
   antes do motor de publicação rodar.)*

## 8. Subir os arquivos

Envie a pasta inteira do projeto (exceto o que não precisa: `tests/`,
`.git` se existir) via FTP/SFTP ou pelo Gerenciador de Arquivos do cPanel
(pode compactar em `.zip` local, subir e extrair lá — é bem mais rápido
que enviar milhares de arquivos um por um pelo FTP).

**Confirme que a pasta `writable/` tem permissão de escrita** (o cPanel
geralmente já deixa certo, mas se der erro de "permission denied" nos
logs, ajuste pra 755 ou 775 pelo Gerenciador de Arquivos, botão
"Permissions").

## 9. Colocar conteúdo na pasta central de mídia

Depois de tudo no ar, envie as imagens (`.jpg`/`.jpeg`) + legendas
(`.txt`) reais pra:
```
public/midia_central/
```
(mesma lógica de sempre — arquivo de imagem + `.txt` de mesmo nome com a
legenda.)

## 10. Teste final (checklist de fumaça)

- [ ] Acessar `https://post.aviabrasil.com.br/` — deve cair na tela de login.
- [ ] Fazer login com o admin criado no passo 4.
- [ ] Acessar `/admin/usuarios` — confirma que o banco está funcionando.
- [ ] Rodar `midia:varrer` uma vez manualmente (Terminal, se tiver) e
      conferir se detectou o conteúdo do passo 9.
- [ ] Testar "Conectar Instagram" com uma conta testadora — se a tela de
      autorização da Meta abrir normalmente, o redirect URI está certo.

## Próximos passos depois do deploy

Depois que tudo estiver no ar e testado, falta:
1. Ir até o painel da Meta e trocar o **domínio/redirect URI** de teste
   pelo definitivo (se ainda não fez).
2. Seguir a **Parte 2** do [GUIA_CONEXAO_INSTAGRAM.md](GUIA_CONEXAO_INSTAGRAM.md)
   para a Revisão do App (App Review) e liberar para todos os franqueados.

Me avise em qual etapa você está (ex: "já criei o banco", "já subi os
arquivos") que eu ajudo a validar cada uma.
