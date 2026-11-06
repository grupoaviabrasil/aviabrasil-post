# PRD — Sistema de Postagem Automatizada para Micro Franquiados Avia Brasil

## 1. Visão Geral

Sistema SaaS multi-tenant que permite aos micro franquiados da Avia Brasil se cadastrarem, vincularem suas contas do Instagram (Business/Creator) e automatizarem a publicação de posts (imagem + legenda), a partir de uma **biblioteca de mídia central**, mantida pela matriz Avia Brasil (pasta única com o conteúdo padrão para todos os franqueados), seguindo uma programação de dias/horários definida individualmente por cada franquiado. A automação pode ser pausada e retomada a qualquer momento pelo próprio usuário.

## 2. Objetivo

Eliminar a necessidade de postagem manual diária nas redes sociais dos franquiados, padronizando presença digital, economizando tempo e garantindo consistência de publicação, sem depender de ferramentas de terceiros pagas por franquiado.

## 3. Personas

- **Franquiado (usuário final):** dono de micro franquia Avia Brasil, sem conhecimento técnico avançado. Não gerencia conteúdo — apenas conecta o Instagram e define a frequência de postagem.
- **Administrador (Avia Brasil / matriz):** mantém a pasta central de conteúdo (imagens + legendas) e acompanha todos os franquiados, status de automação, volume de posts publicados, erros e tokens expirados.

## 4. Escopo — Funcionalidades

### 4.1 Cadastro e Autenticação
- Cadastro de franquiado (nome, e-mail, senha, dados da franquia).
- Login/logout, recuperação de senha.
- Painel individual por franquiado (multi-tenant: cada um só acessa seus próprios dados).

### 4.2 Conexão com Instagram
- Vinculação da conta Instagram via OAuth oficial da Meta, método **"Instagram API with Instagram Login"** (atualizado em 2026-08-23 — ver [GUIA_CONEXAO_INSTAGRAM.md](GUIA_CONEXAO_INSTAGRAM.md)).
- Conta deve ser Instagram Business ou Creator — **não precisa mais de Página do Facebook vinculada** (exigência do método antigo, descontinuado).
- Permissões usadas: `instagram_business_basic` e `instagram_business_content_publish`.
- Armazenamento seguro do token de acesso, com renovação automática antes da expiração (~60 dias).
- Aviso ao franquiado quando o token expirar ou a conexão precisar ser refeita.

### 4.3 Biblioteca de Mídia Central (mantida pela matriz)
- O franquiado **não faz upload de nada** — o conteúdo é único e compartilhado entre todos.
- A Avia Brasil (administrador) coloca os arquivos numa **pasta programada** no servidor: `public/midia_central/` (imagem + arquivo de legenda homônimo, ex: `promocao-verao.jpg` + `promocao-verao.txt`).
- O sistema varre essa pasta periodicamente (via cron) e registra automaticamente qualquer conteúdo novo na biblioteca central de mídia.
- Armazenamento com URL pública (exigência da API do Instagram, que não aceita upload binário direto) — pode ser a própria pasta pública do servidor ou um storage externo (S3/Cloudinary), a definir.
- Status de cada item: pendente, em uso, publicado (por quais franqueados), erro.

### 4.4 Agendamento
- Definição de regra de postagem por franquiado: dias da semana, horários, quantidade de posts por dia.
- Ordem de publicação: sequencial (todos seguem a mesma ordem da pasta central) ou aleatória — **cada franquiado avança na fila central no seu próprio ritmo**, conforme sua própria regra de horários (dois franqueados podem estar publicando itens diferentes da mesma pasta ao mesmo tempo).
- Geração automática do calendário de posts futuros, consumindo a biblioteca central conforme a regra de cada um.
- Quando a fila central se esgota para um franquiado, o sistema recomeça do início (loop) automaticamente, evitando que ele fique sem conteúdo para postar.

### 4.5 Motor de Publicação Automática
- Processo em segundo plano (rodando via cron do servidor, **1x por hora**) que verifica se há posts agendados para a hora atual, de franquiados com automação ativa.
- Publicação via Instagram Graph API (criação do container de mídia + publicação).
- Registro de log de cada tentativa de publicação (sucesso ou erro, com motivo).

### 4.6 Controle de Pausa/Retomada
- Botão único por franquiado: "Pausar automação" / "Retomar automação".
- Quando pausado, o motor ignora os posts daquele franquiado sem apagar a fila.
- Ao retomar, publica a partir do próximo horário da regra (não publica em rajada os posts atrasados).
- Opção de cancelar/pausar um item específico da fila, sem afetar os demais.

### 4.7 Painel de Acompanhamento (Franquiado)
- Calendário visual: publicado, agendado, com erro.
- Notificações (painel e/ou e-mail): token expirado, falha de publicação.

### 4.8 Painel Administrativo (Avia Brasil)
- Gerenciamento da pasta central de mídia: visualizar itens detectados, legendas associadas, itens com erro de leitura (ex: imagem sem legenda).
- Listagem de todos os franquiados, status de automação (ativo/pausado), volume de posts no período.
- Visão de franquiados com token expirado ou erros recorrentes.

## 5. Fora de Escopo (v1)

- Publicação em outras redes sociais além do Instagram (Facebook, TikTok etc.) — considerar em versão futura.
- Edição de imagem dentro do sistema (cortes, filtros).
- Stories e Reels — v1 cobre apenas posts de feed (imagem estática + legenda).
- Métricas de engajamento (curtidas, comentários, alcance).

## 6. Requisitos Não Funcionais

- **Segurança:** tokens de acesso armazenados de forma criptografada; nenhuma senha de Instagram é solicitada ou armazenada (fluxo 100% via OAuth oficial da Meta).
- **Confiabilidade:** falha de publicação de um franquiado não pode afetar os demais (execução isolada por tenant).
- **Limites da API:** respeitar rate limit real da Instagram Graph API — **100 publicações por conta a cada 24h** (confirmado na documentação oficial em 2026-08-23; valor anterior de ~25 estava desatualizado). A API só aceita imagem em **JPEG** (PNG não é aceito — `Config\PastaMidiaCentral` já restringe a varredura a `.jpg`/`.jpeg`).
- **Idioma:** toda a interface, nomes de tabelas, campos, classes e variáveis do sistema em português (exceto pastas obrigatórias do framework CodeIgniter 4, que seguem convenção em inglês: `Controllers`, `Models`, `Views`, `Config`).

## 7. Stack Técnica

- **Backend:** CodeIgniter 4 (PHP).
- **Banco de dados:** MySQL/MariaDB (padrão XAMPP).
- **Storage de mídia:** pasta programada no servidor com URL pública (exigência da API do Instagram); alternativa futura: S3/Cloudinary, a confirmar.
- **Agendador:** **cron do servidor de produção (Linux), rodando 1x por hora** — uma tarefa varre a pasta central em busca de conteúdo novo (`midia:varrer`), outra dispara as publicações da hora (`posts:publicar`). Em desenvolvimento local (XAMPP/Windows), os comandos são executados manualmente via `php spark`.
- **Integração externa:** Instagram Graph API (Meta for Developers).

## 8. Modelo de Dados

Banco `avia_brasil_postagens` (MySQL/MariaDB), 6 tabelas já criadas via migrations do CodeIgniter 4:

**`usuarios`** — franquiados cadastrados
| campo | tipo | observação |
|---|---|---|
| id | INT PK auto_increment | |
| nome | VARCHAR(150) | |
| email | VARCHAR(150) UNIQUE | |
| senha_hash | VARCHAR(255) | hash da senha, nunca texto puro |
| nome_franquia | VARCHAR(150) NULL | |
| automacao_ativa | TINYINT(1) default 0 | flag de pausar/retomar |
| created_at / updated_at | DATETIME | |

**`contas_instagram`** — conexão OAuth por franquiado (1:1 com usuarios)
| campo | tipo | observação |
|---|---|---|
| id | INT PK | |
| usuario_id | INT FK → usuarios.id, UNIQUE | on delete/update cascade |
| instagram_user_id | VARCHAR(100) | id da conta no Instagram |
| access_token | TEXT | recomendado armazenar cifrado (usar a `encryption.key` do CI4) |
| token_expira_em | DATETIME NULL | para disparar renovação/aviso |
| status_conexao | ENUM(conectado, desconectado, erro) default conectado | |
| created_at / updated_at | DATETIME | |

**`midias`** — biblioteca **central**, compartilhada entre todos os franqueados
| campo | tipo | observação |
|---|---|---|
| id | INT PK | |
| nome_arquivo | VARCHAR(255) UNIQUE | nome do arquivo em `public/midia_central/` |
| legenda | TEXT | conteúdo do .txt homônimo |
| ordem | INT (indexado) | define a sequência de publicação |
| status | ENUM(pendente, ativo, removido) default pendente | |
| created_at / updated_at | DATETIME | |

**`agendamentos`** — regra de dias/horários por franquiado (1:1 com usuarios)
| campo | tipo | observação |
|---|---|---|
| id | INT PK | |
| usuario_id | INT FK → usuarios.id, UNIQUE | |
| dias_semana | VARCHAR(100) | ex: `seg,qua,sex` |
| horarios | TEXT | ex: `09:00,18:00` |
| ordem_publicacao | ENUM(sequencial, aleatoria) default sequencial | |
| ultimo_indice_midia | INT default 0 | ponteiro: posição da biblioteca central onde este franquiado está |
| created_at / updated_at | DATETIME | |

**`fila_publicacoes`** — posts programados por franquiado
| campo | tipo | observação |
|---|---|---|
| id | INT PK | |
| usuario_id | INT FK → usuarios.id | |
| midia_id | INT FK → midias.id | |
| data_hora_programada | DATETIME | horário em que deve ser publicado |
| status | ENUM(pendente, publicado, erro, cancelado) default pendente | |
| created_at / updated_at | DATETIME | índice composto (usuario_id, status, data_hora_programada) para a query do motor de publicação |

**`logs_publicacao`** — histórico de tentativas
| campo | tipo | observação |
|---|---|---|
| id | INT PK | |
| fila_publicacao_id | INT FK → fila_publicacoes.id | |
| sucesso | TINYINT(1) default 0 | |
| mensagem_erro | TEXT NULL | preenchido apenas quando sucesso = 0 |
| created_at / updated_at | DATETIME | |

Migrations em `app/Database/Migrations/`, já executadas com `php spark migrate` no banco `avia_brasil_postagens`.

## 9. Fluxos Principais

1. **Onboarding do franquiado:** cadastro → login → conectar Instagram (OAuth) → configurar regra de agendamento (dias/horários). Sem etapa de upload.
2. **Atualização de conteúdo central:** administrador coloca imagem + legenda na pasta programada → cron varre a pasta periodicamente → novos itens entram na biblioteca central de mídia, disponíveis para todos os franqueados.
3. **Publicação automática:** cron do servidor dispara 1x por hora → verifica agendamentos com hora batendo e franquiado ativo → busca a próxima mídia central conforme o ponteiro daquele franquiado → publica via API → avança o ponteiro → atualiza status e log.
4. **Pausa:** franquiado clica em pausar → flag `automacao_ativa = false` → motor ignora a fila dele até ele retomar (o ponteiro na biblioteca central fica congelado, sem pular itens).

## 10. Riscos

- Mudanças na política ou API do Instagram/Meta podem exigir ajustes não previstos.
- Contas de franquiados sem Business/Creator configurado corretamente não conseguem ser conectadas (dependência de configuração externa ao sistema).
- Necessidade de storage com URL pública gera custo/complexidade adicional (a definir provedor).
- Como o conteúdo é o mesmo para todos os franqueados, publicações muito próximas no tempo (mesma imagem/legenda em várias contas) podem ser percebidas pelo Instagram como conteúdo duplicado — mitigar variando levemente horários entre franqueados ou legendas, se necessário no futuro.

## 11. Próximos Passos

1. ~~Criar estrutura do projeto CodeIgniter 4.~~ ✅ Feito (ver seção 12).
2. ~~Detalhar modelo de dados e criar as migrations.~~ ✅ Feito — 6 tabelas criadas no banco `avia_brasil_postagens` (ver seção 8).
3. ~~Implementar cadastro e login do franquiado.~~ ✅ Feito (ver seção 13).
4. ~~Implementar a tela de agendamento (dias/horários).~~ ✅ Feito (ver seção 14).
5. ~~Implementar fluxo de OAuth com Instagram/Meta.~~ ✅ Feito (ver seção 24) — falta apenas o teste de ponta a ponta num ambiente sem bloqueio de rede para `graph.instagram.com`/`api.instagram.com` (ver seção 24).
6. ~~Implementar a lógica de `midia:varrer`.~~ ✅ Feito (ver seção 15).
7. ~~Implementar o motor `posts:publicar`~~ ✅ Feito (ver seção 16), exceto a chamada real ao Instagram (aguardando item 5).
8. Configurar o **cron do servidor de produção (Linux)** para rodar `midia:varrer` e `posts:publicar` **1x por hora** — decisão confirmada pelo usuário em 2026-08-23: o serviço final roda em servidor próprio, não neste XAMPP local, e a automação é horária, não por minuto. Localmente, seguimos rodando os comandos manualmente via `php spark` para testes.
9. ~~Criar papel de administrador e travar `/admin`.~~ ✅ Feito (ver seção 17), incluindo bootstrap via seeder e gestão pela interface (sem depender de CLI no dia a dia).

## 25. Guia de Deploy — Hospedagem Compartilhada (2026-08-24)

Usuário confirmou que vai hospedar em **cPanel/Plesk (hospedagem
compartilhada)**, não um VPS com root. Criei
[GUIA_DEPLOY_CPANEL.md](GUIA_DEPLOY_CPANEL.md) cobrindo: onde apontar o
document root (`public/`), criação do banco via cPanel, importação do
schema (com fallback via phpMyAdmin para quem não tem Terminal — gerei
`docs/schema_avia_brasil_postagens.sql`, dump da estrutura das 7 tabelas +
estado das migrations, sem dados de usuário), `.env` de produção (HTTPS
obrigatório, nova chave de criptografia, `instagram.redirectUri`
atualizado — e lembrete de atualizar o mesmo valor no painel da Meta),
configuração dos 2 cron jobs horários, SSL/AutoSSL, e checklist de teste
final.

## 24. OAuth e Publicação Real no Instagram (implementado em 2026-08-24)

Com o App ID/Secret do Instagram fornecidos no `.env`, implementei o fluxo completo (deixando de ser TODO):

- **`Config\Instagram`** — nova classe de config, lê `instagram.appId`/`instagram.appSecret`/`instagram.redirectUri` do `.env` e define o `scope` (`instagram_business_basic,instagram_business_content_publish`).
- **`InstagramConexao::conectar()`** — monta a URL real de `https://www.instagram.com/oauth/authorize` com os parâmetros corretos e redireciona.
- **`InstagramConexao::retorno()`** — troca o `code` por token curto (`api.instagram.com/oauth/access_token`), depois por token longo de 60 dias (`graph.instagram.com/access_token`), salva `instagram_user_id` + token **cifrado** (via `service('encrypter')`, com `base64` para caber num campo `TEXT`) em `contas_instagram`. Trata explicitamente autorização negada e ausência de `code`.
- **`InstagramConexao::desconectar()`** — apaga o registro de `contas_instagram` do franquiado.
- **`PublicarPosts::publicarNoInstagram()`** — implementado de verdade: busca a conta conectada, descriptografa o token, renova automaticamente se faltar ≤10 dias para expirar (`refresh_access_token`), cria o container (`POST /{id}/media`) e publica (`POST /{id}/media_publish`).
- **Resiliência:** todas as chamadas de rede (OAuth e publicação) envolvidas em `try/catch` — uma falha de rede em um franquiado não derruba o processamento em lote dos demais, nem quebra a tela de callback do OAuth com um erro fatal (vira um redirect com mensagem amigável).

**Limitação de teste encontrada:** o ambiente sandbox onde rodei os testes **bloqueia a rede** para `graph.instagram.com` e `api.instagram.com` (SSL connection reset), embora `www.instagram.com` funcione normalmente — validado com `curl -v` mostrando reset na negociação TLS. Por isso, consegui testar e confirmar:
- A montagem da URL de autorização (`conectar()`) com os parâmetros reais e corretos.
- O tratamento de erro/negação no callback.
- A criptografia/decriptografia do token (round-trip via comando temporário de teste, removido depois).
- Que uma falha de rede real (comprovada com essa mesma restrição do sandbox) é tratada graciosamente em vez de derrubar o comando — foi assim que encontrei e corrigi o bug do `try/catch` faltando.

**Ainda não testado de ponta a ponta** (requer ambiente sem esse bloqueio de rede, ou seja, o servidor de produção ou a própria máquina do usuário): a troca real de token com a Meta e a publicação de uma imagem de verdade. Isso só pode ser validado quando o franquiado passar pelo fluxo completo (idealmente com uma conta testadora, ver Parte 2 do guia).

## 23. Aviso de Pré-requisitos para Conectar o Instagram (implementado, atualizado em 2026-08-23)

Antes desta mudança, o franquiado só via um botão "Conectar Instagram" sem nenhuma explicação do que precisava preparar antes.

- Bloco expansível (`<details>/<summary>` nativo do HTML, **sem JavaScript**) na seção "Conta do Instagram" do painel, visível apenas quando a conta ainda não está conectada.
- Lista em linguagem simples (não técnica) do que o franquiado precisa fazer **na própria conta dele**: (1) transformar em conta Business/Creator, (2) ter esse login em mãos na hora de autorizar.
- **Atualizado em 2026-08-23:** o passo de "vincular a uma Página do Facebook" foi removido do aviso — a Meta descontinuou essa exigência no método atual ("Instagram API with Instagram Login", ver seção 4.2 e [GUIA_CONEXAO_INSTAGRAM.md](GUIA_CONEXAO_INSTAGRAM.md)). Agora só precisa da conta Business/Creator, sem Página.
- Diferente do guia em `docs/GUIA_CONEXAO_INSTAGRAM.md` (que é para o administrador configurar o App na Meta), este texto é para o **usuário final** entender o que fazer na conta dele.

Testado: renderiza sem erro, aparece só quando não conectado.

## 22. Seletor de Horários (implementado)

O campo de horários deixou de ser texto livre (`input type=text`, onde o franquiado tinha que digitar "09:00, 18:00" manualmente e podia errar formato/vírgula) e virou um **seletor prático**:

- `<select>` com as 24 horas cheias (00:00 a 23:00) + botão "Adicionar".
- Ao adicionar, aparece como um **chip removível** (etiqueta com um "x") — sem recarregar a página.
- A hora já adicionada some da lista de opções do `<select>` (não dá pra adicionar duplicado).
- Um `<input type="hidden" name="horarios">` mantém a lista real (comma-separated) que é enviada ao servidor — **nenhuma mudança no backend foi necessária**, pois o campo continua se chamando `horarios` com o mesmo formato que `Agendamento::salvar()` já validava.
- Tudo em JavaScript vanilla (sem framework/dependência), ~60 linhas, carregado inline na própria view.
- Validação do servidor continua ativa como rede de segurança (caso JS esteja desabilitado ou o campo seja manipulado).

Testado: 24 opções no seletor, campo escondido pré-preenchido corretamente ao editar um agendamento existente, envio do formulário com múltiplos horários salva certo no banco, e a tela recarrega com o valor salvo pronto para os chips serem renderizados pelo JS.

## 21. Botões de Ícone com Dica (implementado)

Substituídos os links/botões de texto secundários (Conectar/Desconectar Instagram, Pausar/Retomar automação, Configurar agendamento, Gerenciar usuários, Biblioteca de mídia, Tornar/Remover admin) por **botões de ícone com tooltip** (`data-dica`, CSS puro — sem JS), num padrão comum de dashboards modernos: ação compacta, rótulo aparece só ao passar o mouse/focar.

- Classe `.btn-icone` (com variações `.positivo` verde e `.destrutivo` vermelho no hover, conforme a semântica da ação).
- Tooltip via atributo `data-dica` + `::after`, sem depender de JavaScript.
- **Botões de submit primários** (Salvar, Entrar, Cadastrar) foram mantidos como texto — converter a ação principal de uma tela para ícone-apenas prejudicaria a clareza, então a mudança ficou restrita às ações secundárias/utilitárias.
- Tela `/painel` reorganizada em linhas "rótulo + status à esquerda, ação à direita" (`.linha-config`) para acomodar os novos botões.

Testado: todas as páginas renderizam sem erro com os botões novos; os formulários por trás de cada botão continuam apontando para a ação correta (ex: `painel/retomar` continua funcionando).

## 20. Reformulação Completa do Leiaute (implementado)

Reformulação total do `layout/base.php`, inspirada em dashboards SaaS modernos (Linear/Stripe), com dois modos:

- **Tela de autenticação (login/cadastro):** split-screen — painel esquerdo com gradiente azul da marca, ícone, mensagem institucional e ano corrente; painel direito com o formulário. Em telas estreitas (`<860px`), o painel de marca vira um cabeçalho compacto e a mensagem institucional some, mantendo o formulário como foco.
- **Casca do app (painel/agendamento/admin):** barra lateral fixa com marca no topo, navegação com ícones (Painel, Agendamento, Administração — este último só para admins) e destaque visual automático do item ativo (via `uri_string()`), e rodapé da barra com nome/e-mail do usuário + Sair. Em telas estreitas (`<780px`), a barra lateral vira uma faixa horizontal compacta (só ícones).
- **Sistema de design:** cards com sombra suave e cantos arredondados (`--raio: 14px`), paleta azul/verde oficial da marca (seção 19), foco de campos com anel azul suave, botões com micro-interação (leve "afundar" ao clicar).
- Nenhuma view individual precisou ser alterada — como o wrapper de conteúdo (`.cartao`, antigo `.caixa`) é definido só no layout, todas as telas herdaram o novo visual automaticamente.

**Bug real encontrado e corrigido durante os testes** (não relacionado ao layout, mas exposto ao testar mais rotas): `Cadastro::index()` e `Login::index()` declaravam `: string` no retorno, mas retornavam um `RedirectResponse` quando o usuário já estava logado (redirecionamento para `/painel`) — isso causava `TypeError` (HTTP 500) ao acessar `/login` ou `/cadastro` estando autenticado. Corrigido removendo a tipagem de retorno fixa nesses dois métodos.

Testado: bateria completa de todas as rotas (logado e deslogado) sem nenhum erro; navegação ativa muda corretamente entre Painel/Agendamento/Administração.

## 19. Identidade Visual Avia Brasil (implementado)

- **Ícone e logotipo oficiais** — o usuário forneceu os arquivos reais (`ICONE AVIA.png` e `NOME AVIA.png`), salvos em `public/assets/img/avia-icone.png` e `avia-logo.png`. O cabeçalho usa a imagem real do ícone (não mais uma recriação em SVG).
- **Paleta de cores extraída por pixel** direto do PNG oficial (script PHP/GD lendo os pixels, não mais estimativa visual): `--cor-azul: #00548C`, `--cor-verde: #006E44` — cores exatas da marca, com tons escuros/suaves derivados para hover, badges e alertas de sucesso.
- **Tipografia:** fonte Inter (Google Fonts) substituindo a fonte padrão do sistema, para um visual mais moderno.
- **Cabeçalho global** (`layout/base.php`): logo + nome "AVIA" + tagline "A mobilidade que transforma" sempre visível; navegação (Painel / Administração / Sair) aparece automaticamente quando há sessão ativa, direto via `session()->get(...)` na própria view — nenhum controller precisou ser alterado para isso.
- **Cartões modernizados:** cantos mais arredondados, sombra suave, borda superior colorida com o azul da marca.
- **Limpeza de navegação duplicada:** links redundantes de "Voltar"/"Sair"/"Painel Administrativo" que existiam soltos no corpo de cada tela foram removidos, já que agora ficam centralizados no cabeçalho.
- **Correção incidental:** a tela `admin/midias.php` nunca tinha sido conectada ao layout compartilhado (faltava `$this->extend('layout/base')`) — corrigido.

Testado via renderização HTTP: página de login (sem sessão) não mostra navegação; painel do admin logado mostra nome, link Painel, link Administração e Sair; SVG e cores presentes no HTML gerado. **Ainda não verificado visualmente num navegador real** — recomendo conferir a aparência final você mesmo.

## 18. Painel do Franquiado — completo (implementado)

Correção de uma lacuna real: os controllers foram evoluindo (cadastro, login, agendamento, admin) mas a tela `/painel` continuava sendo o placeholder inicial do scaffolding — por isso admin e franquiado comum pareciam idênticos e não havia nenhum link para conectar o Instagram.

- **`PainelFranquiado::index()`** agora busca de verdade: dados do usuário (incl. `automacao_ativa`), a conexão em `contas_instagram` e o `agendamento` — e passa tudo para a view.
- **Seção "Conta do Instagram":** mostra conectado/não conectado; se não conectado, exibe o link **"Conectar Instagram"** (antes não existia nenhum ponto de entrada na interface para isso).
- **Seção "Automação de postagem":** mostra ativa/pausada com o botão correspondente — `PainelFranquiado::pausar()`/`retomar()` agora de fato atualizam `automacao_ativa` no banco (antes eram TODOs vazios).
- **Seção "Agendamento":** mostra o resumo (dias/horários) se já configurado, ou aviso + link para configurar.
- **Link "Painel Administrativo"** só aparece para quem tem `usuario['admin'] = true` — agora existe diferença visível entre logar como admin e como franquiado comum.
- **Stubs do Instagram** (`InstagramConexao::conectar/retorno/desconectar`) deixaram de devolver página em branco — agora redirecionam para `/painel` com mensagem clara explicando que depende da configuração do App no Meta (ver seção 5 / guia de OAuth).

Testado ponta a ponta com o admin real (`avia@aviabrasil.com.br`): painel mostra "Não conectado" + link Conectar Instagram, "Pausada" + botão Retomar, link Painel Administrativo visível; clicar em Conectar Instagram mostra a mensagem de pendência (não mais tela branca); botão Retomar atualiza `automacao_ativa` no banco corretamente.

## 17. Papel de Administrador (implementado)

- Coluna `admin` (TINYINT, default `0`) adicionada em `usuarios` via migration.
- No login, o valor é gravado na sessão (`usuario_admin`) junto com os demais dados — **por isso, promover alguém a admin só tem efeito a partir do próximo login** (a sessão atual não é atualizada em tempo real).
- Novo filtro `App\Filters\AdminFilter` (alias `admin`): bloqueia quem não tem `usuario_admin` na sessão, redirecionando para `/painel` com aviso "Acesso restrito a administradores."
- Rota `admin/*` agora exige **os dois filtros** (`['filter' => ['autenticado', 'admin']]`) — precisa estar logado E ser administrador.
- **Bootstrap do primeiro admin (`AdminSeeder`):** em vez de exigir CLI manual toda vez (abordagem inicial, considerada "fora do padrão"), criei `app/Database/Seeds/AdminSeeder.php`, que lê `ADMIN_EMAIL`/`ADMIN_SENHA` do `.env` e cria (ou promove, se o e-mail já existir) o administrador inicial. Roda uma vez por ambiente: `php spark db:seed AdminSeeder`. Padrão equivalente ao `createsuperuser` do Django ou ao seed inicial do Laravel.
- **Gestão de administradores pela interface:** tela `/admin/usuarios` (só para admins) lista todos os usuários com um botão "Tornar admin" / "Remover admin" por linha — depois do bootstrap inicial, **nenhuma operação de admin exige mais acesso ao servidor**.
- **Proteção contra lockout:** `PainelAdmin::alternarAdmin()` impede remover o último administrador do sistema (bloqueia com mensagem de erro) — evita que o sistema fique sem nenhum admin e precise de CLI de novo.
- O comando `php spark usuarios:promover-admin <email>` (seção anterior) continua existindo como via de emergência, mas não é mais o caminho principal.

Testado ponta a ponta: usuário comum bloqueado em `/admin`; seeder cria o admin a partir do `.env`; login com esse admin dá acesso a `/admin/usuarios`; promoção de um segundo usuário pela interface funciona; tentativa de remover o único admin restante é bloqueada; com 2 admins, a remoção do primeiro funciona normalmente.

## 15. Varredura da Pasta Central — `midia:varrer` (implementado)

- Lê `Config\PastaMidiaCentral` (caminho, extensões aceitas, extensão da legenda) e varre `public/midia_central/` em ordem natural de nome de arquivo.
- Para cada imagem encontrada, exige um `.txt` homônimo não vazio; sem isso, o arquivo é **ignorado** e listado no final da execução (não trava a varredura).
- **Item novo:** insere em `midias` com `status = ativo` e `ordem` = próximo número sequencial (`MAX(ordem) + 1`).
- **Item já existente:** atualiza a `legenda` se o `.txt` mudou; se estava `removido` e o arquivo voltou, reativa para `ativo`.
- **Item que sumiu da pasta:** marcado como `status = removido` (soft delete) — nunca é apagado de verdade, porque `fila_publicacoes` pode referenciar aquela mídia (FK `RESTRICT`).
- Idempotente: rodar sem nenhuma mudança na pasta não altera nada no banco (Novos: 0 | Atualizados: 0 | Removidos: 0).

Testado ponta a ponta com arquivos reais: detecção de item novo, execução repetida sem mudanças, edição de legenda, remoção de arquivo (soft delete) e devolução do arquivo (reativação) — todos os 5 cenários confirmados no banco.

## 16. Motor de Publicação — `posts:publicar` (implementado, exceto a chamada ao Instagram)

- A cada execução (pensado para rodar via cron a cada minuto), calcula o dia da semana e horário atuais e busca, com um único JOIN, todos os `agendamentos` de franquiados com `automacao_ativa = 1`.
- Casa `dias_semana`/`horarios` da regra com o momento atual (comparação exata ao minuto); se bater, segue para a publicação.
- **Deduplicação:** antes de processar, verifica se já existe uma linha em `fila_publicacoes` para aquele franquiado naquele minuto exato — evita publicar duas vezes caso o cron dispare mais de uma vez no mesmo minuto.
- **Escolha da mídia:** busca em `midias` (status `ativo`) a primeira com `ordem` maior que `ultimo_indice_midia` do franquiado; se não encontrar (chegou ao fim da lista), **reinicia do começo** (`ordem` mais baixa) — loop automático pela biblioteca central.
- Cria a linha em `fila_publicacoes`, chama `publicarNoInstagram()` (ver abaixo), atualiza o status (`publicado`/`erro`) e grava o resultado em `logs_publicacao`.
- **O ponteiro (`ultimo_indice_midia`) só avança em caso de sucesso** — uma falha de publicação não “pula” conteúdo da fila.
- Opção `--agora="Y-m-d H:i"` para simular data/hora em testes, sem precisar esperar o relógio real.
- **Granularidade horária:** o serviço roda em servidor com cron configurado para **1x por hora** (não por minuto). Por isso a comparação de horário e a chave de deduplicação usam a **hora cheia** (`H:00`), não o minuto exato — não importa em qual minuto da hora o cron efetivamente dispara, ele bate com o agendamento da hora corrente. A tela de agendamento (seção 14) só aceita horas cheias (`HH:00`) por causa disso.

**`publicarNoInstagram()` é o único ponto ainda não implementado**: hoje sempre retorna erro "Integração com a Instagram Graph API ainda não implementada (depende do OAuth)". Quando o OAuth (item 5) existir, essa função vai: 1) `POST /{ig-user-id}/media` (URL pública da imagem + legenda) para criar o container, 2) `POST /{ig-user-id}/media_publish` para publicar.

Testado ponta a ponta com dados reais via `--agora`: franquiado pausado é ignorado, franquiado ativo é processado, execução repetida na mesma hora (mesmo em minutos diferentes, ex: 09:37 e 09:52) não duplica, e — simulando sucesso temporariamente — confirmei que o ponteiro avança corretamente e faz loop ao chegar no fim da biblioteca (2 mídias: avançou 1→2→loop para 1).

## 14. Agendamento (implementado)

- **Tela** (`/agendamento`, protegida por login): checkboxes de dias da semana, campo de horas cheias (`HH:00` separadas por vírgula) e escolha de ordem (sequencial/aleatória).
- **Regra de negócio:** 1 agendamento por franquiado (`usuario_id` é UNIQUE em `agendamentos`) — salvar atualiza o registro existente em vez de criar duplicado.
- **Validação:** exige ao menos um dia marcado e todos os horários no formato `HH:00` (só horas cheias — ver seção 16 sobre granularidade horária do motor de publicação); em caso de erro, nada é gravado e o formulário reaparece preenchido com o que o usuário digitou (`old()`).
- **Ponteiro da biblioteca central:** `ultimo_indice_midia` é inicializado em `0` na criação e preservado em edições futuras — avançado pelo motor de publicação (`posts:publicar`, seção 16) somente quando a publicação é bem-sucedida.

Testado ponta a ponta: criar agendamento, reabrir a tela e confirmar pré-preenchimento correto, editar (confirmando UPDATE em vez de INSERT duplicado), e rejeição de formulário sem dia selecionado ou com horário inválido.

## 13. Cadastro e Login (implementado)

- **Cadastro** (`Cadastro::salvar`): valida nome, e-mail único, senha (mín. 8 caracteres, com confirmação), grava `senha_hash` com `password_hash()` — nunca a senha em texto puro.
- **Login** (`Login::autenticar`): valida credenciais com `password_verify()`, cria sessão (`usuario_id`, `usuario_nome`, `usuario_email`) e regenera o ID de sessão (proteção contra fixation).
- **Logout** (`Login::sair`): destrói a sessão.
- **Proteção de rotas:** filtro `App\Filters\AutenticacaoFilter` (alias `autenticado`) aplicado via grupo de rotas a `painel`, `agendamento` e `instagram/*` — quem não estiver logado é redirecionado para `/login`. O grupo `admin/*` exige adicionalmente o papel de administrador (ver seção 17).
- **CSRF:** habilitado globalmente (`Config\Filters::$globals['before']`); todos os formulários usam `form_open()`, que já injeta o token.
- **Layout:** `app/Views/layout/base.php` compartilhado entre as telas de autenticação e o painel, com exibição de mensagens de erro/sucesso via flashdata.

Testado ponta a ponta via linha de comando (cadastro → login → acesso ao painel → bloqueio sem sessão → logout → bloqueio pós-logout), incluindo rejeição de e-mail duplicado e senha incorreta.

## 12. Estrutura do Projeto (criada)

Projeto CodeIgniter 4 (v4.7.4) instalado na raiz de `c:\xampp\htdocs\post`.

```
post/
├── app/
│   ├── Commands/
│   │   ├── VarrerPastaCentral.php   → comando `spark midia:varrer`
│   │   ├── PublicarPosts.php        → comando `spark posts:publicar`
│   │   └── PromoverAdmin.php        → comando `spark usuarios:promover-admin`
│   ├── Filters/
│   │   ├── AutenticacaoFilter.php   → exige franquiado logado
│   │   └── AdminFilter.php          → exige papel de administrador
│   ├── Config/
│   │   ├── PastaMidiaCentral.php    → define o caminho da pasta central
│   │   └── Routes.php               → rotas em português
│   ├── Controllers/
│   │   ├── Cadastro.php
│   │   ├── Login.php
│   │   ├── PainelFranquiado.php     → painel + pausar/retomar automação
│   │   ├── InstagramConexao.php     → OAuth (conectar/retorno/desconectar)
│   │   ├── Agendamento.php          → regra de dias/horários
│   │   └── PainelAdmin.php          → visão da matriz + biblioteca de mídia
│   ├── Models/
│   │   ├── UsuarioModel.php
│   │   ├── ContaInstagramModel.php
│   │   ├── MidiaModel.php
│   │   ├── AgendamentoModel.php
│   │   ├── FilaPublicacaoModel.php
│   │   └── LogPublicacaoModel.php
│   └── Views/
│       ├── autenticacao/ (login, cadastro)
│       ├── painel/ (index, agendamento)
│       └── admin/ (index, midias)
├── public/
│   ├── midia_central/               → PASTA PROGRAMADA (pública) onde a
│   │                                   matriz coloca imagem + legenda .txt
│   └── index.php                    → front controller
├── writable/                        → cache, logs, sessões
├── docs/
│   └── PRD.md
└── .env                             → ambiente = development, banco
                                        `avia_brasil_postagens`, chave de
                                        criptografia já gerada
```

Todos os controllers/commands já criados são **esqueletos funcionais** (rotas registradas, sem erro ao rodar) com `TODO` marcando onde entra a lógica de negócio dos próximos passos.

**Como rodar localmente:** com o Apache do XAMPP ligado, acessar `http://localhost:8080/post/public/`. **Atenção:** este XAMPP está configurado para escutar na porta **8080**, não na porta 80 padrão (ver `Listen 8080` em `c:\xampp\apache\conf\httpd.conf`) — usar `http://localhost/...` sem a porta dá "conexão recusada". Alternativamente, `php spark serve` a partir da pasta do projeto também sobe em `http://localhost:8080`, mas não pode rodar ao mesmo tempo que o Apache (mesma porta).

**Banco de dados:** ainda precisa ser criado (`avia_brasil_postagens`) e as migrations escritas — próximo passo da lista acima.
