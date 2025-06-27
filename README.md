# WP Content Scheduler Pro

![WordPress Plugin Version](https://img.shields.io/badge/WordPress-5.0%2B-blue)
![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple)
![License](https://img.shields.io/badge/License-GPL%20v2%2B-green)
![Version](https://img.shields.io/badge/Version-4.0.0-orange)

**Plugin profissional para publicação automática de postagens agendadas perdidas no WordPress**

## 📋 Descrição

O WP Content Scheduler Pro é um plugin robusto e seguro que resolve um problema comum do WordPress: postagens agendadas que falham em ser publicadas automaticamente. O plugin monitora e publica automaticamente conteúdo que deveria ter sido publicado, mas por algum motivo ficou com status "future".

## ✨ Características Principais

### 🔒 **Segurança Avançada**
- Proteção contra acesso direto
- Queries preparadas contra SQL injection
- Sanitização completa de dados
- Verificação de nonces e permissões
- Validação rigorosa de entradas

### ⚡ **Performance Otimizada**
- Sistema de cache inteligente (5 minutos)
- Processamento limitado (10 posts por execução)
- Execução condicional apenas quando necessário
- Queries otimizadas com índices
- Prevenção de race conditions

### 🎯 **Arquitetura Profissional**
- Padrão Singleton implementado
- Orientação a objetos completa
- Estrutura modular e extensível
- Documentação PHPDoc detalhada
- Código seguindo WordPress Coding Standards

### 🛠️ **Funcionalidades Avançadas**
- Painel administrativo completo
- Sistema de cron alternativo
- Suporte à internacionalização (i18n)
- Logging detalhado de atividades
- Filtros para desenvolvedores

## 🚀 Instalação

### Método 1: Upload Manual
1. Baixe o arquivo do plugin
2. Acesse **Plugins > Adicionar Novo > Enviar Plugin**
3. Selecione o arquivo e clique em **Instalar Agora**
4. Ative o plugin

### Método 2: FTP
1. Extraia os arquivos do plugin
2. Envie a pasta para `/wp-content/plugins/`
3. Ative o plugin no painel WordPress

### Método 3: WP-CLI
```bash
wp plugin install wp-content-scheduler-pro --activate
```

## ⚙️ Configuração

### Acesso às Configurações
1. Vá para **Configurações > Content Scheduler**
2. Configure as opções conforme sua necessidade
3. Salve as alterações

### Opções Disponíveis

#### **Processamento via Cron**
- ✅ **Recomendado** para sites com baixo tráfego
- ✅ Execução mais confiável
- ✅ Não depende de visitantes no site

**Como ativar:**
```php
// Via admin ou programaticamente
update_option('wp_content_scheduler_pro_enable_cron', true);
```

## 📖 Como Usar

### Funcionamento Automático
O plugin funciona automaticamente após a ativação:

1. **Monitoramento:** Verifica postagens com status "future"
2. **Verificação:** Compara data/hora agendada com atual
3. **Publicação:** Publica automaticamente postagens em atraso
4. **Log:** Registra todas as ações realizadas

### Tipos de Conteúdo Suportados
- ✅ Posts (artigos)
- ✅ Páginas
- ✅ Custom Post Types públicos
- ✅ Qualquer tipo personalizado via filtro

### Filtragem de Tipos de Post
```php
// Adicionar tipos personalizados
add_filter('wp_content_scheduler_pro_post_types', function($post_types) {
    $post_types[] = 'produto';
    $post_types[] = 'evento';
    return $post_types;
});
```

## 🔧 Configurações Avançadas

### Constantes Personalizáveis
```php
// No wp-config.php

// Alterar duração do cache (padrão: 300 segundos)
define('WP_CONTENT_SCHEDULER_CACHE_DURATION', 600);

// Alterar limite de posts por execução (padrão: 10)
define('WP_CONTENT_SCHEDULER_MAX_POSTS', 20);

// Ativar modo debug
define('WP_CONTENT_SCHEDULER_DEBUG', true);
```

### Hooks Disponíveis

#### Actions
```php
// Antes do processamento
do_action('wp_content_scheduler_pro_before_process');

// Após publicação de um post
do_action('wp_content_scheduler_pro_post_published', $post_id);

// Após processamento completo
do_action('wp_content_scheduler_pro_after_process', $published_count);
```

#### Filters
```php
// Modificar tipos de post suportados
apply_filters('wp_content_scheduler_pro_post_types', $post_types);

// Modificar query de busca
apply_filters('wp_content_scheduler_pro_query_args', $args);

// Modificar limite de posts
apply_filters('wp_content_scheduler_pro_posts_limit', $limit);
```

## 📊 Monitoramento e Logs

### Logs do Sistema
O plugin registra atividades no log de erro do WordPress:

```
[WP Content Scheduler Pro] Successfully published post ID: 123, Title: "Meu Artigo"
[WP Content Scheduler Pro] Error processing scheduled posts: Database connection failed
[WP Content Scheduler Pro] Plugin activated successfully
```

### Localização dos Logs
- **WordPress:** `/wp-content/debug.log`
- **Servidor:** Varia conforme configuração do hosting
- **cPanel:** Logs de erro no painel

### Ativando Logs (wp-config.php)
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## 🛡️ Segurança

### Medidas Implementadas
- **Sanitização completa** de todas as entradas
- **Prepared statements** para queries SQL
- **Verificação de nonces** em formulários
- **Validação de permissões** de usuário
- **Escape de dados** na saída HTML
- **Prevenção de acesso direto** aos arquivos

### Boas Práticas
```php
// Exemplo de uso seguro
$post_id = absint($_POST['post_id']);
$nonce = sanitize_text_field($_POST['_wpnonce']);

if (!wp_verify_nonce($nonce, 'my_action')) {
    wp_die('Acesso negado');
}
```

## 🔄 Troubleshooting

### Problemas Comuns

#### **Posts não estão sendo publicados**
1. Verifique se o plugin está ativo
2. Confirme se há posts com status "future" em atraso
3. Ative o modo cron nas configurações
4. Verifique os logs de erro

#### **Plugin consumindo muitos recursos**
1. Reduza o limite de posts por execução
2. Aumente a duração do cache
3. Use o modo cron em vez do frontend
4. Otimize a query com filtros

#### **Cron não está funcionando**
```bash
# Teste o cron do WordPress
wp cron test

# Listar eventos agendados
wp cron event list

# Executar manualmente
wp cron event run wp_content_scheduler_pro_cron
```

### Debug Mode
```php
// Ativar debug específico do plugin
add_action('init', function() {
    if (defined('WP_CONTENT_SCHEDULER_DEBUG') && WP_CONTENT_SCHEDULER_DEBUG) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }
});
```

## 🧪 Testes

### Teste Manual
1. Crie um post agendado para o passado
2. Visite uma página do site
3. Verifique se o post foi publicado
4. Consulte os logs para confirmação

### Teste via WP-CLI
```bash
# Executar processamento manualmente
wp eval "WP_Content_Scheduler_Pro::get_instance()->process_scheduled_posts();"

# Verificar posts futuros em atraso
wp post list --post_status=future --format=table
```

## 📈 Performance

### Otimizações Implementadas
- **Cache de transients** para reduzir queries
- **Limite de processamento** por execução
- **Queries indexadas** para melhor performance
- **Execução condicional** apenas quando necessário
- **Cleanup automático** de dados temporários

### Métricas Recomendadas
- **Cache Duration:** 300 segundos (5 minutos)
- **Posts Limit:** 10 posts por execução
- **Memory Usage:** < 2MB por execução
- **Execution Time:** < 5 segundos

## 🌐 Internacionalização

### Idiomas Suportados
- 🇧🇷 Português (Brasil) - `pt_BR`
- 🇺🇸 English (US) - `en_US`
- 🇪🇸 Español - `es_ES`

### Adicionar Tradução
1. Crie arquivo `.po` em `/languages/`
2. Use o text domain: `wp-content-scheduler-pro`
3. Compile para `.mo`

```php
// Exemplo de string traduzível
__('Settings saved successfully!', 'wp-content-scheduler-pro');
```

## 📝 Changelog

### Version 4.0.0 (2025-06-27)
- ✨ **NEW:** Implementação do padrão Singleton
- 🔒 **SECURITY:** Melhorias completas de segurança
- ⚡ **PERFORMANCE:** Sistema de cache inteligente
- 🛠️ **FEATURE:** Painel administrativo completo
- 🐛 **FIX:** Prevenção de race conditions
- 📚 **DOCS:** Documentação PHPDoc detalhada

### Version 3.2 (Original)
- 🎯 Funcionalidade básica de publicação
- 📝 Suporte a custom post types
- ⚡ Execução via wp_head

## 🤝 Contribuição

### Como Contribuir
1. Fork o repositório
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Push para a branch
5. Abra um Pull Request

### Padrões de Código
- Seguir [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Usar PHPDoc para documentação
- Implementar testes unitários
- Manter compatibilidade com PHP 7.4+

### Reportar Bugs
1. Verifique se o bug já foi reportado
2. Forneça steps para reproduzir
3. Inclua informações do ambiente
4. Anexe logs se possível

## 📄 Licença

Este plugin é licenciado sob a **GPL v2 ou posterior**.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 👨‍💻 Autor

**Daniel Oliveira da Paixão**
- 🌐 Website: [rd5.com.br/dev](http://rd5.com.br/dev)
- 📧 Email: contato@rd5.com.br
- 💼 LinkedIn: [Daniel Oliveira](https://linkedin.com/in/daniel-oliveira)

## 🆘 Suporte

### Canais de Suporte
- 📧 **Email:** suporte@rd5.com.br
- 💬 **Forum:** [WordPress.org Support](https://wordpress.org/support/plugin/wp-content-scheduler-pro)
- 📚 **Documentação:** [rd5.com.br/docs](http://rd5.com.br/docs)
- 🐛 **Issues:** [GitHub Issues](https://github.com/daniel-rd5/wp-content-scheduler-pro/issues)

### Informações Úteis para Suporte
Ao solicitar ajuda, inclua:
- Versão do WordPress
- Versão do PHP
- Plugins ativos
- Tema utilizado
- Logs de erro
- Passos para reproduzir o problema

---

**⭐ Se este plugin foi útil, considere deixar uma avaliação!**

*Desenvolvido com ❤️ para a comunidade WordPress*
