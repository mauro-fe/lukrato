# Configuração lukrato.test

## Configuração concluída!

### Passos para ativar lukrato.test:

1. **Abra o Laragon**
2. **Clique com botão direito no ícone do Laragon** (system tray)
3. **Selecione "Apache" > "Virtual Hosts" > "Auto Create Virtual Hosts"**
   - Isso criará automaticamente o virtual host `lukrato.test`
4. **Reinicie o Laragon**
   - Menu: Apache > Restart All

### Verificação:

Após reiniciar o Laragon, acesse no navegador:
- http://lukrato.test

### Arquivos atualizados:

- ✅ `.env` - BASE_URL configurada para http://lukrato.test/
- ✅ `config/config.php` - URL padrão atualizada
- ✅ `bootstrap.php` - URL padrão atualizada
- ✅ `public/assets/js/site/landing-base.js` - API URL atualizada

### O que o Laragon faz automaticamente:

1. Adiciona entrada no arquivo hosts: `127.0.0.1 lukrato.test`
2. Cria configuração do Apache para o virtual host
3. Aponta o DocumentRoot para: `M:/laragon/www/lukrato/public`

### Alternativa Manual (se necessário):

Se o Laragon não criar automaticamente, você pode:

1. Adicionar manualmente ao arquivo hosts (requer admin):
   ```
   C:\Windows\System32\drivers\etc\hosts
   ```
   Adicione a linha:
   ```
   127.0.0.1 lukrato.test
   ```

2. Criar virtual host do Apache manualmente:
   ```
   M:\laragon\etc\apache2\sites-enabled\lukrato.test.conf
   ```
