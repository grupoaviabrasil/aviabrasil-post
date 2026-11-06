# Guia — Criar o App no Meta for Developers (conexão com Instagram)

> **Atualizado em 2026-08-23** com base na documentação atual da Meta. A
> versão anterior deste guia usava o método antigo ("Instagram API with
> Facebook Login"), que exigia uma Página do Facebook vinculada. A Meta
> agora recomenda o método **"Instagram API with Instagram Login"**, que
> **não exige mais Página do Facebook** — só a conta Instagram Business ou
> Creator direto. Isso simplifica bastante o cadastro dos franqueados, e é
> o método que este guia (e o código do sistema) agora segue.

Este guia é para você (ou quem administra a conta Avia Brasil no Meta) seguir
passo a passo. Ao final, você vai ter um **App ID** e um **App Secret** do
Instagram para colocar no `.env` do projeto — é só isso que eu preciso para
implementar o fluxo de OAuth (`InstagramConexao::conectar` / `retorno`).

> ⚠️ **Não confundir com a Marketing API.** Existe uma API separada da
> Meta para **anúncios pagos** (`developers.facebook.com/.../marketing-api`
> — Campanhas, Conjuntos de Anúncios, Anúncios, orçamento, segmentação).
> O sistema Avia Brasil **não usa isso** — só posta organicamente e de
> graça no feed, via **Instagram Graph API / Content Publishing**
> (documentada abaixo). Confirmado com o usuário em 2026-08-23 que o
> escopo do projeto é só postagem orgânica.

## Pré-requisitos (antes de mexer no Meta for Developers)

1. **Uma conta do Facebook** (pessoal, mas de preferência uma que a Avia
   Brasil controle de forma permanente — não a conta pessoal de um
   funcionário que pode sair da empresa). Ela é usada só para criar e
   administrar o App no painel de desenvolvedores — **não precisa virar
   uma Página nem ser vinculada ao Instagram**.
2. Pelo menos **uma conta de Instagram convertida para Business ou
   Creator** (para testar o fluxo). Isso se faz direto no app do Instagram:
   Configurações → Conta → "Mudar para conta profissional".

> Cada franquiado, no futuro, vai precisar repetir o passo 2 com a própria
> conta dele — só isso, sem precisar de Página do Facebook. É exigência da
> Meta, não do nosso sistema.

## Passo a passo no Meta for Developers

1. Acesse [developers.facebook.com](https://developers.facebook.com) e
   faça login com a conta do Facebook do passo 1.
2. Se ainda não tiver, complete o cadastro de desenvolvedor (pede telefone
   para verificação).
3. Clique em **"Meus Apps" → "Criar App"**.
4. Escolha o tipo de app **"Business"**.
5. Preencha nome do app (ex: "Avia Brasil — Postagem Automática") e o
   e-mail de contato.
6. Dentro do app criado, no painel lateral, clique em **"Adicionar Produto"**
   e adicione o produto **"Instagram"**.
7. Na página do produto Instagram, vá em **"API setup with Instagram
   login"** (Configuração da API com login do Instagram). Nessa tela você
   encontra:
   - **Instagram App ID** e **Instagram App Secret** (role até
     "Business login settings" / "Configurações de login comercial").
   - O campo **"OAuth redirect URIs"**, onde você adiciona:
     ```
     http://localhost:8080/post/public/index.php/instagram/retorno
     ```
     (Em produção, será o domínio real, ex:
     `https://app.aviabrasil.com.br/instagram/retorno` — atualizamos aqui e
     no `.env` quando tivermos o domínio definitivo.)
8. Vá em **Configurações do App → Básico** e preencha os campos
   obrigatórios para poder salvar:
   - **URL da Política de Privacidade** (pode ser uma página simples no
     site da Avia Brasil).
   - **Ícone do App** (o logo da Avia Brasil).
   - **Categoria** (ex: "Negócios").
9. As permissões que o sistema usa são:
   - `instagram_business_basic`
   - `instagram_business_content_publish`

   *(Se você tiver criado o app antes de agosto de 2026, pode aparecer
   ainda `instagram_basic`/`instagram_content_publish` em telas antigas —
   a Meta descontinuou esses nomes em janeiro de 2025; use sempre os
   nomes com `_business_` acima.)*

## Sobre o Modo de Desenvolvimento vs. Produção

- Enquanto o app estiver em **modo de desenvolvimento** (padrão ao criar),
  ele só funciona com contas cadastradas como **"testador"** no próprio
  painel do app. Isso é suficiente para nós testarmos o fluxo com 1-2
  franquiados antes de liberar geral.
- Para os **franquiados de verdade** conseguirem conectar suas próprias
  contas, o app precisa passar pela **Revisão do App** da Meta (App
  Review) para a permissão `instagram_business_content_publish` em nível
  avançado — ver Parte 2 deste guia, mais abaixo.

## O que fazer com o App ID e App Secret

**Não cole o App Secret aqui no chat.** Ele é uma credencial sensível —
se vazar, alguém pode publicar em nome da Avia Brasil. Duas opções mais
seguras:

1. **Você mesmo edita o `.env`** do projeto (`c:\xampp\htdocs\post\.env`),
   adicionando ao final:
   ```
   instagram.appId = "SEU_INSTAGRAM_APP_ID_AQUI"
   instagram.appSecret = "SEU_INSTAGRAM_APP_SECRET_AQUI"
   instagram.redirectUri = "http://localhost:8080/post/public/index.php/instagram/retorno"
   ```
2. Ou me avisa "já coloquei no `.env`" e eu sigo direto para implementar o
   `InstagramConexao.php` lendo essas variáveis — sem eu nunca precisar
   ver o valor do secret.

## Como o fluxo técnico funciona (referência para quando eu implementar)

1. **Autorização** — o franquiado é redirecionado para:
   ```
   https://www.instagram.com/oauth/authorize
     ?client_id=SEU_INSTAGRAM_APP_ID
     &redirect_uri=SEU_REDIRECT_URI
     &response_type=code
     &scope=instagram_business_basic,instagram_business_content_publish
   ```
2. **Callback** — a Meta redireciona de volta para `instagram/retorno` com
   `?code=...`.
3. **Trocar o código por um token de curta duração (1 hora):**
   `POST https://api.instagram.com/oauth/access_token` com `client_id`,
   `client_secret`, `grant_type=authorization_code`, `redirect_uri`, `code`.
4. **Trocar por um token de longa duração (60 dias):**
   `GET https://graph.instagram.com/access_token?grant_type=ig_exchange_token&client_secret=...&access_token=TOKEN_CURTO`.
5. **Renovar antes de expirar** (token precisa ter ≥24h e ainda não ter
   expirado): `GET https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token=TOKEN_LONGO`.
6. **Publicar** (usado em `posts:publicar`):
   - `POST https://graph.instagram.com/{ig-user-id}/media` com `image_url`,
     `caption`, `access_token` → devolve um `creation_id`.
   - `POST https://graph.instagram.com/{ig-user-id}/media_publish` com
     `creation_id`, `access_token` → publica de fato.

**Limitações importantes da API:**
- Só aceita imagem em **JPEG** (não aceita PNG) — a pasta central de mídia
  do sistema precisa ter só `.jpg`/`.jpeg`.
- Limite de **100 publicações por conta a cada 24 horas** (dá pra checar o
  saldo em `GET /{ig-user-id}/content_publishing_limit`).

## Próximo passo depois disso

Com o App ID/Secret no `.env`, eu implemento:
1. `InstagramConexao::conectar` — monta a URL de autorização acima e
   redireciona o franquiado.
2. `InstagramConexao::retorno` — recebe o `code` de volta, faz as duas
   trocas de token (curta → longa duração), descobre o `instagram_user_id`
   e salva tudo em `contas_instagram`.
3. A função `publicarNoInstagram()` em `PublicarPosts.php` (hoje um TODO)
   passa a chamar de verdade os endpoints de publicação acima.

---

## Parte 2 — Ativar a API de verdade e enviar o App para aprovação (App Review)

Esta parte é só sua (eu não tenho como mexer no painel do Meta for
Developers) — mas vou te guiar em cada tela. É o passo que faz o app
funcionar para **qualquer franquiado**, não só para contas de teste.

### Passo 1 — Testar primeiro em Modo de Desenvolvimento (sem aprovação)

Antes de pedir aprovação para a Meta, teste tudo funcionando com contas de
teste — isso não precisa de nenhuma revisão:

1. No painel do App → **Funções do App → Testadores** (ou "Roles → Testers").
2. Clique em **Adicionar testadores do Instagram**, digite o @ da conta do
   Instagram que vai testar (pode ser a sua própria, ou de um franquiado
   voluntário).
3. Essa pessoa recebe um convite dentro do próprio app do Instagram
   (Configurações → Apps e sites → Convites) e precisa **aceitar**.
4. Com isso, o fluxo de login e publicação já funciona de verdade para essa
   conta, mesmo com o App em modo Desenvolvimento — é assim que eu vou
   conseguir testar a integração com você antes de qualquer aprovação.

### Passo 2 — Completar os requisitos obrigatórios do App

A Meta não deixa nem solicitar revisão se faltar algum destes itens, em
**Configurações do App → Básico**:

- **URL da Política de Privacidade** (obrigatório) — uma página real
  explicando como os dados dos franquiados são usados/armazenados.
- **URL de Exclusão de Dados** (obrigatório) — uma página ou instrução de
  como um usuário pede para apagar os dados dele do sistema (a Meta exige
  isso de qualquer app que acesse dados de usuários).
- **Ícone do App** (1024×1024px) e **Categoria** (ex: "Negócios").
- **Domínios do App** — o domínio real onde o sistema vai rodar em
  produção (ex: `aviabrasil.com.br`), quando definido.

### Passo 3 — Verificação de Negócios (Business Verification)

Para liberar `instagram_business_content_publish` (publicar de verdade)
para contas que não são testadoras, a Meta normalmente exige que o
**Portfólio de Negócios** (Meta Business Manager) esteja verificado:

1. Acesse [business.facebook.com](https://business.facebook.com) e crie/entre
   no Portfólio de Negócios da Avia Brasil (se ainda não existir).
2. Vá em **Segurança do Negócio → Verificação de Negócios** → "Iniciar
   verificação".
3. A Meta pede documentos oficiais da empresa: CNPJ, comprovante de
   endereço, e às vezes um telefone/e-mail corporativo para confirmação.
4. Vincule o App do passo anterior a esse Portfólio de Negócios
   (Configurações do App → Básico → "Conta comercial").
5. Esse processo pode levar de alguns dias a duas semanas — vale começar
   isso **em paralelo**, não precisa esperar o resto pra iniciar.

### Passo 4 — Preparar o material para a revisão (App Review)

Para a permissão sensível, a Meta exige uma **demonstração em vídeo**
mostrando exatamente o uso dela dentro do seu app real (não adianta ser um
vídeo genérico). Grave um vídeo de tela (ex: com o OBS Studio, gratuito)
mostrando, na sequência:

- **`instagram_business_basic`:** o franquiado clicando em "Conectar
  Instagram" no painel, passando pela tela de login/permissão da Meta
  (`instagram.com/oauth/authorize`), e voltando conectado (mostrando o
  selo "Conectado" no painel).
- **`instagram_business_content_publish`:** a automação publicando de
  fato uma imagem no Instagram da conta conectada (pode ser rodando o
  comando `php spark posts:publicar` manualmente na tela, mostrando o
  post aparecendo no Instagram logo em seguida).

Além do vídeo, a Meta pede um **texto curto por permissão** explicando o
uso — por exemplo, para `instagram_business_content_publish`:

> "Nosso app permite que franquiados da rede Avia Brasil publiquem
> automaticamente, em horários programados por eles, imagens e legendas
> fornecidas pela matriz da franquia em suas próprias contas do Instagram,
> eliminando a necessidade de postagem manual diária."

### Passo 5 — Solicitar a revisão

1. No painel do App → **Revisão do App → Permissões e Recursos**.
2. Localize cada permissão abaixo e clique em **"Solicitar acesso
   avançado"** (Request Advanced Access):
   - `instagram_business_basic`
   - `instagram_business_content_publish`
3. Preencha o formulário de cada uma: o texto de uso (passo 4) + faça
   upload do vídeo correspondente.
4. Revise tudo e clique em **"Enviar para revisão"**.

### Passo 6 — O que esperar durante a análise

- O prazo costuma ser de **alguns dias até ~1-2 semanas**.
- É comum a Meta **recusar na primeira tentativa** pedindo mais detalhes
  ou um vídeo mais claro — não é incomum, é só reenviar com o ajuste
  pedido.
- Você recebe o resultado por e-mail e também vê o status em **Revisão do
  App → Permissões e Recursos** (Pendente / Aprovado / Recusado).

### Passo 7 — Colocar o App em modo "Live" (Ativo)

Depois que as permissões necessárias estiverem aprovadas:

1. No topo do painel do App, tem uma alternância **"Em desenvolvimento" /
   "Ativo"** (Live) — mude para **Ativo**.
2. A partir daqui, qualquer franquiado (não só testadores) consegue
   conectar a própria conta do Instagram pelo fluxo normal do sistema.

### Resumo do que só você pode fazer vs. o que eu faço

| Tarefa | Quem faz |
|---|---|
| Criar/configurar o App, pedir revisão, verificar negócio | **Você**, no painel da Meta |
| Gravar o vídeo de demonstração | **Você** (eu posso te ajudar a roteirizar exatamente o que mostrar) |
| Implementar o código que a Meta vai avaliar no vídeo (OAuth, publicação) | **Eu**, aqui no projeto |
| Colocar o App em "Live" após aprovado | **Você** |

Se quiser, no dia da gravação do vídeo eu já deixo tudo funcionando de
ponta a ponta (com uma conta de testador) para você só abrir a tela e
gravar o fluxo real acontecendo.
