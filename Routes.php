<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Página inicial redireciona para o login.
$routes->get('/', 'Login::index');

// Autenticação (públicas)
$routes->get('cadastro', 'Cadastro::index');
$routes->post('cadastro', 'Cadastro::salvar');
$routes->get('login', 'Login::index');
$routes->post('login', 'Login::autenticar');
$routes->get('sair', 'Login::sair');

// Rotas que exigem franquiado logado
$routes->group('', ['filter' => 'autenticado'], static function (RouteCollection $routes) {
    // Painel do franquiado
    $routes->get('painel', 'PainelFranquiado::index');
    $routes->post('painel/pausar', 'PainelFranquiado::pausar');
    $routes->post('painel/retomar', 'PainelFranquiado::retomar');
    $routes->post('painel/postar-agora', 'PainelFranquiado::postarAgora');

    // Conexão com Instagram
    $routes->get('instagram/conectar', 'InstagramConexao::conectar');
    $routes->get('instagram/retorno', 'InstagramConexao::retorno');
    $routes->post('instagram/desconectar', 'InstagramConexao::desconectar');

    // Agendamento
    $routes->get('agendamento', 'Agendamento::index');
    $routes->post('agendamento', 'Agendamento::salvar');

    // Exclusão da própria conta
    $routes->get('painel/excluir-conta', 'PainelFranquiado::confirmarExclusao');
    $routes->post('painel/excluir-conta', 'PainelFranquiado::excluirConta');

});

// Painel administrativo (Avia Brasil) — exige login E papel de administrador.
$routes->group('admin', ['filter' => ['autenticado', 'admin']], static function (RouteCollection $routes) {
    $routes->get('/', 'PainelAdmin::index');
    $routes->get('usuarios', 'PainelAdmin::usuarios');
    $routes->post('usuarios/(:num)/alternar-admin', 'PainelAdmin::alternarAdmin/$1');

    // Monitoramento de publicações
    $routes->get('publicacoes', 'PainelAdmin::publicacoes');

    // Biblioteca central de mídia
    $routes->get('midias', 'PainelAdmin::midias');
    $routes->post('midias/varrer', 'PainelAdmin::midiaVarrer');
    $routes->post('midias/enviar', 'PainelAdmin::midiaEnviar');
    $routes->post('midias/(:num)/alternar-status', 'PainelAdmin::midiaAlternarStatus/$1');
    $routes->post('midias/(:num)/legenda', 'PainelAdmin::midiaEditarLegenda/$1');
    $routes->post('midias/(:num)/excluir', 'PainelAdmin::midiaExcluir/$1');
});
