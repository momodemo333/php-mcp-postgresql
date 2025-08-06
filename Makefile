# Makefile pour PHP MCP MySQL
# Facilite l'exécution des tests et tâches de développement

.PHONY: help install test test-unit test-integration test-coverage test-docker clean build

# Configuration par défaut
PHP_VERSION ?= 8.1
COMPOSE_FILE ?= docker-compose.test.yml

# Couleurs pour l'affichage
BLUE = \033[0;34m
GREEN = \033[0;32m
YELLOW = \033[1;33m
RED = \033[0;31m
NC = \033[0m # No Color

# Aide par défaut
help: ## Affiche cette aide
	@echo "$(BLUE)PHP MCP MySQL - Commandes disponibles:$(NC)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "$(GREEN)%-20s$(NC) %s\n", $$1, $$2}'
	@echo ""
	@echo "$(YELLOW)Exemples:$(NC)"
	@echo "  make install          # Installation des dépendances"
	@echo "  make test             # Tous les tests avec Docker"
	@echo "  make test-unit        # Tests unitaires uniquement"
	@echo "  make test-coverage    # Tests avec rapport de couverture"
	@echo "  make clean            # Nettoyage des ressources Docker"

# Installation et setup
install: ## Installe les dépendances Composer
	@echo "$(BLUE)Installation des dépendances...$(NC)"
	composer install --no-interaction --prefer-dist --optimize-autoloader
	@echo "$(GREEN)✓ Dépendances installées$(NC)"

install-dev: ## Installe les dépendances de développement
	@echo "$(BLUE)Installation des dépendances de développement...$(NC)"
	composer install --dev --no-interaction --prefer-dist --optimize-autoloader
	@echo "$(GREEN)✓ Dépendances de développement installées$(NC)"

update: ## Met à jour les dépendances
	@echo "$(BLUE)Mise à jour des dépendances...$(NC)"
	composer update --no-interaction --prefer-dist --optimize-autoloader
	@echo "$(GREEN)✓ Dépendances mises à jour$(NC)"

# Build et vérifications
build: ## Reconstruit les classes Codeception
	@echo "$(BLUE)Reconstruction des classes Codeception...$(NC)"
	vendor/bin/codecept build
	@echo "$(GREEN)✓ Classes reconstruites$(NC)"

validate: ## Valide la configuration Composer
	@echo "$(BLUE)Validation de la configuration...$(NC)"
	composer validate --strict
	@echo "$(GREEN)✓ Configuration valide$(NC)"

# Tests locaux (sans Docker)
test-local: ## Execute tous les tests localement (sans Docker)
	@echo "$(BLUE)Exécution des tests locaux...$(NC)"
	vendor/bin/codecept run --verbose
	@echo "$(GREEN)✓ Tests locaux terminés$(NC)"

test-unit: ## Execute uniquement les tests unitaires (sans Docker)
	@echo "$(BLUE)Exécution des tests unitaires...$(NC)"
	vendor/bin/codecept run unit --verbose
	@echo "$(GREEN)✓ Tests unitaires terminés$(NC)"

# Tests avec Docker
test: ## Execute tous les tests avec Docker MySQL
	@echo "$(BLUE)Exécution de tous les tests avec Docker...$(NC)"
	./tests/scripts/docker-test-complete.sh -v
	@echo "$(GREEN)✓ Tests Docker terminés$(NC)"

test-integration: ## Execute uniquement les tests d'intégration avec Docker
	@echo "$(BLUE)Exécution des tests d'intégration avec Docker...$(NC)"
	./tests/scripts/docker-test-complete.sh -i -v
	@echo "$(GREEN)✓ Tests d'intégration terminés$(NC)"

test-coverage: ## Execute tous les tests avec rapport de couverture
	@echo "$(BLUE)Exécution des tests avec couverture...$(NC)"
	./tests/scripts/docker-test-complete.sh -v -c
	@echo "$(GREEN)✓ Tests avec couverture terminés$(NC)"
	@echo "$(YELLOW)Rapport disponible dans: coverage/index.html$(NC)"

test-quick: ## Execute les tests unitaires rapidement
	@echo "$(BLUE)Tests rapides (unitaires seulement)...$(NC)"
	./tests/scripts/docker-test-complete.sh -u
	@echo "$(GREEN)✓ Tests rapides terminés$(NC)"

# Tests alternatifs avec PHP
test-php: ## Execute les tests avec le script PHP (legacy)
	@echo "$(BLUE)Exécution avec le script PHP...$(NC)"
	php tests/scripts/run-docker-tests.php --verbose
	@echo "$(GREEN)✓ Tests PHP terminés$(NC)"

# Docker management
docker-up: ## Démarre le container MySQL de test
	@echo "$(BLUE)Démarrage du container MySQL...$(NC)"
	docker-compose -f $(COMPOSE_FILE) up -d
	@echo "$(GREEN)✓ Container démarré$(NC)"

docker-down: ## Arrête le container MySQL de test
	@echo "$(BLUE)Arrêt du container MySQL...$(NC)"
	docker-compose -f $(COMPOSE_FILE) down
	@echo "$(GREEN)✓ Container arrêté$(NC)"

docker-logs: ## Affiche les logs du container MySQL
	@echo "$(BLUE)Logs du container MySQL:$(NC)"
	docker-compose -f $(COMPOSE_FILE) logs -f

docker-shell: ## Ouvre un shell dans le container MySQL
	@echo "$(BLUE)Ouverture du shell MySQL...$(NC)"
	docker exec -it php-mcp-mysql-test mysql -u testuser -ptestpass testdb

# Nettoyage
clean: ## Nettoie les ressources Docker et fichiers temporaires
	@echo "$(BLUE)Nettoyage des ressources...$(NC)"
	docker-compose -f $(COMPOSE_FILE) down --volumes --remove-orphans || true
	docker container rm php-mcp-mysql-test --force || true
	docker volume rm php-mcp-mysql_mysql_test_data || true
	rm -rf tests/_output/*
	rm -rf tests/reports/*
	rm -rf coverage/*
	@echo "$(GREEN)✓ Nettoyage terminé$(NC)"

clean-all: clean ## Nettoyage complet incluant les dépendances
	@echo "$(BLUE)Nettoyage complet...$(NC)"
	rm -rf vendor/
	rm -rf composer.lock
	@echo "$(GREEN)✓ Nettoyage complet terminé$(NC)"

# Qualité du code
lint: ## Vérifie la syntaxe PHP
	@echo "$(BLUE)Vérification de la syntaxe PHP...$(NC)"
	find src tests -name "*.php" -exec php -l {} \; | grep -v "No syntax errors"
	@echo "$(GREEN)✓ Syntaxe PHP valide$(NC)"

# Développement et debug
debug: ## Informations de debug pour les tests
	@echo "$(BLUE)Informations de debug:$(NC)"
	@echo "PHP Version: $(shell php -v | head -n 1)"
	@echo "Composer Version: $(shell composer --version)"
	@echo "Codeception Version: $(shell vendor/bin/codecept --version 2>/dev/null || echo 'Non installé')"
	@echo "Docker Version: $(shell docker --version)"
	@echo "Docker Compose Version: $(shell docker-compose --version)"
	@echo "Environnement test:"
	@env | grep -E "^(MYSQL_|ALLOW_|TEST_)" || echo "  Aucune variable d'environnement de test définie"

server: ## Démarre le serveur MCP pour test
	@echo "$(BLUE)Démarrage du serveur MCP...$(NC)"
	php bin/server.php

server-http: ## Démarre le serveur MCP en mode HTTP
	@echo "$(BLUE)Démarrage du serveur MCP HTTP sur port 8080...$(NC)"
	php bin/server.php --transport=http --host=127.0.0.1 --port=8080

# Continuous Integration helpers
ci-install: ## Installation pour CI
	composer install --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-dev

ci-test: ## Tests pour CI (sans interface interactive)
	./tests/scripts/docker-test-complete.sh

ci-coverage: ## Tests avec couverture pour CI
	./tests/scripts/docker-test-complete.sh -c

# Documentation et release helpers
docs: ## Génère la documentation (placeholder)
	@echo "$(YELLOW)Documentation: Voir README.md et docs/$(NC)"

version: ## Affiche la version actuelle
	@echo "$(BLUE)Version actuelle:$(NC)"
	@grep '"version"' composer.json | head -1 | cut -d'"' -f4

# Targets par défaut
.DEFAULT_GOAL := help

# Variables d'environnement pour les tests
export MYSQL_HOST ?= 127.0.0.1
export MYSQL_PORT ?= 33306  
export MYSQL_USER ?= testuser
export MYSQL_PASS ?= testpass
export MYSQL_DB ?= testdb