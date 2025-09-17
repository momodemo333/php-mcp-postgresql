# Résolution du problème "Invalid or expired session"

## Problème

L'erreur MCP `-32600` avec le message "Invalid or expired session. Please re-initialize the session." se produit quand :

1. Le processus serveur PHP redémarre
2. La connexion stdio est interrompue
3. Le client perd sa session avec le serveur

## Cause technique

Le SDK `php-mcp/server` utilise un `SessionManager` qui :
- Crée une session unique lors de l'appel `initialize`
- Maintient cette session en mémoire pendant la durée de vie du processus
- Ne supporte pas la persistance des sessions avec le transport stdio

Code responsable dans le SDK :
```php
// vendor/php-mcp/server/src/Protocol.php ligne 122-124
if ($session === null) {
    $error = Error::forInvalidRequest('Invalid or expired session. Please re-initialize the session.', $message->id);
}
```

## Solutions

### Solution 1 : Redémarrage du client (Immédiat)

**Pour Claude Code :**
- Recharger la fenêtre : `Cmd/Ctrl + R`
- Ou redémarrer Claude Code complètement

**Pour d'autres clients MCP :**
- Redémarrer le client
- Forcer une nouvelle connexion

### Solution 2 : Gestion des signaux (Implémenté)

Le serveur gère maintenant mieux les arrêts :
```php
// Gestion propre des signaux SIGTERM et SIGINT
pcntl_signal(SIGTERM, function() { ... });
pcntl_signal(SIGINT, function() { ... });
```

### Solution 3 : Monitoring de connexion

Ajoutez ces variables d'environnement pour un meilleur suivi :
```bash
LOG_LEVEL=DEBUG
```

Cela permet de voir dans les logs :
- Quand la connexion est établie
- Quand une session est créée
- Quand le serveur s'arrête

### Solution 4 : Utiliser HTTP Transport (Alternative)

Pour éviter complètement ce problème, utilisez le transport HTTP :

```php
// bin/server-http.php
use PhpMcp\Server\Transports\StreamableHttpServerTransport;

$transport = new StreamableHttpServerTransport(
    host: '127.0.0.1',
    port: 8080
);
```

Configuration Claude Code :
```json
{
    "mcpServers": {
        "postgresql-http": {
            "url": "http://localhost:8080/mcp/sse"
        }
    }
}
```

## Prévention

### 1. Éviter les timeouts PostgreSQL
```bash
QUERY_TIMEOUT=60
CONNECTION_POOL_SIZE=3
```

### 2. Logs détaillés
```bash
LOG_LEVEL=INFO
```

### 3. Test de santé
Utilisez `getServerStatus` périodiquement pour vérifier la connexion.

## Limitations connues

- Le transport stdio ne supporte pas la reconnexion automatique
- Les sessions ne sont pas persistées entre les redémarrages
- Le SDK php-mcp/server v3.3 n'a pas de mécanisme de "keep-alive" pour stdio

## Recommandation

Pour un usage en production ou de longue durée, préférez :
1. Le transport HTTP avec StreamableHttpServerTransport
2. Un processus supervisé (systemd, supervisor)
3. Des health checks réguliers