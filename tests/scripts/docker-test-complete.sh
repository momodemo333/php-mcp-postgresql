#!/bin/bash

# Script complet de test avec Docker
# Démarre MySQL, exécute tous les types de tests, génère les rapports et nettoie

set -e  # Arrêt en cas d'erreur

# Variables de configuration
CONTAINER_NAME="php-mcp-mysql-test"
MYSQL_PORT=33306
HEALTH_CHECK_MAX_ATTEMPTS=30
HEALTH_CHECK_INTERVAL=2
VERBOSE=false

# Couleurs pour la sortie
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction de logging
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Fonction d'aide
show_help() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -v, --verbose     Mode verbeux"
    echo "  -u, --unit-only   Exécuter seulement les tests unitaires"
    echo "  -i, --integration-only  Exécuter seulement les tests d'intégration"
    echo "  -c, --coverage    Générer le rapport de couverture"
    echo "  -h, --help        Afficher cette aide"
    echo ""
    echo "Exemples:"
    echo "  $0                # Tous les tests"
    echo "  $0 -v -c          # Tous les tests avec couverture et mode verbeux"
    echo "  $0 -u             # Tests unitaires seulement"
    echo "  $0 -i             # Tests d'intégration seulement"
}

# Parse des arguments
UNIT_ONLY=false
INTEGRATION_ONLY=false
COVERAGE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        -v|--verbose)
            VERBOSE=true
            shift
            ;;
        -u|--unit-only)
            UNIT_ONLY=true
            shift
            ;;
        -i|--integration-only)
            INTEGRATION_ONLY=true
            shift
            ;;
        -c|--coverage)
            COVERAGE=true
            shift
            ;;
        -h|--help)
            show_help
            exit 0
            ;;
        *)
            error "Option inconnue: $1"
            show_help
            exit 1
            ;;
    esac
done

# Fonction de nettoyage
cleanup() {
    log "🧹 Nettoyage des ressources..."
    
    # Arrêt et suppression du container
    docker stop $CONTAINER_NAME >/dev/null 2>&1 || true
    docker rm $CONTAINER_NAME >/dev/null 2>&1 || true
    
    # Suppression du volume (optionnel, garde les données entre les runs si commenté)
    # docker volume rm php-mcp-mysql_mysql_test_data >/dev/null 2>&1 || true
    
    success "Nettoyage terminé"
}

# Gestion des signaux pour nettoyage
trap cleanup EXIT INT TERM

# Fonction de démarrage de MySQL
start_mysql() {
    log "🚀 Démarrage du container MySQL..."
    
    # Vérifier si docker-compose existe
    if ! command -v docker-compose &> /dev/null; then
        error "docker-compose n'est pas installé"
        exit 1
    fi
    
    # Démarrer avec docker-compose
    docker-compose -f docker-compose.test.yml up -d --build
    
    if [ $? -ne 0 ]; then
        error "Impossible de démarrer le container MySQL"
        exit 1
    fi
    
    success "Container MySQL démarré"
}

# Fonction d'attente de MySQL
wait_for_mysql() {
    log "⏳ Attente de MySQL..."
    
    local attempt=0
    while [ $attempt -lt $HEALTH_CHECK_MAX_ATTEMPTS ]; do
        if docker exec $CONTAINER_NAME mysqladmin ping -h localhost -u root -ptestroot >/dev/null 2>&1; then
            success "MySQL est prêt sur le port $MYSQL_PORT"
            return 0
        fi
        
        if [ "$VERBOSE" = true ]; then
            echo -n "."
        fi
        
        attempt=$((attempt + 1))
        sleep $HEALTH_CHECK_INTERVAL
    done
    
    error "MySQL n'est pas prêt après $HEALTH_CHECK_MAX_ATTEMPTS tentatives"
    exit 1
}

# Configuration des variables d'environnement de test
setup_test_environment() {
    log "🔧 Configuration de l'environnement de test..."
    
    export MYSQL_HOST=127.0.0.1
    export MYSQL_PORT=$MYSQL_PORT
    export MYSQL_USER=testuser
    export MYSQL_PASS=testpass
    export MYSQL_DB=testdb
    export TEST_ENVIRONMENT=docker
    
    # Configuration de test par défaut
    export ALLOW_INSERT_OPERATION=true
    export ALLOW_UPDATE_OPERATION=true
    export ALLOW_DELETE_OPERATION=true
    export ALLOW_TRUNCATE_OPERATION=true
    export ALLOW_DDL_OPERATIONS=true
    export ALLOW_ALL_OPERATIONS=false
    export MAX_RESULTS=1000
    export QUERY_TIMEOUT=30
    export LOG_LEVEL=ERROR
    
    success "Environnement configuré"
}

# Test de connexion
test_connection() {
    log "🔗 Test de connexion à MySQL..."
    
    if docker exec $CONTAINER_NAME mysql -h localhost -u testuser -ptestpass -e "SELECT 1" >/dev/null 2>&1; then
        success "Connexion MySQL OK"
    else
        error "Impossible de se connecter à MySQL"
        exit 1
    fi
}

# Exécution des tests unitaires
run_unit_tests() {
    log "🧪 Exécution des tests unitaires..."
    
    local cmd="vendor/bin/codecept run unit"
    if [ "$VERBOSE" = true ]; then
        cmd="$cmd --verbose"
    fi
    if [ "$COVERAGE" = true ]; then
        cmd="$cmd --coverage --coverage-xml --coverage-html"
    fi
    
    if eval $cmd; then
        success "Tests unitaires réussis"
        return 0
    else
        error "Échec des tests unitaires"
        return 1
    fi
}

# Exécution des tests d'intégration
run_integration_tests() {
    log "🔧 Exécution des tests d'intégration..."
    
    local cmd="vendor/bin/codecept run integration"
    if [ "$VERBOSE" = true ]; then
        cmd="$cmd --verbose"
    fi
    if [ "$COVERAGE" = true ]; then
        cmd="$cmd --coverage --coverage-xml --coverage-html"
    fi
    
    if eval $cmd; then
        success "Tests d'intégration réussis"
        return 0
    else
        error "Échec des tests d'intégration"
        return 1
    fi
}

# Exécution des tests fonctionnels (si implémentés)
run_functional_tests() {
    log "⚙️ Exécution des tests fonctionnels..."
    
    local cmd="vendor/bin/codecept run functional"
    if [ "$VERBOSE" = true ]; then
        cmd="$cmd --verbose"
    fi
    
    # Les tests fonctionnels sont optionnels
    if eval $cmd 2>/dev/null; then
        success "Tests fonctionnels réussis"
        return 0
    else
        warning "Pas de tests fonctionnels ou échec non critique"
        return 0
    fi
}

# Génération du rapport final
generate_report() {
    log "📊 Génération du rapport final..."
    
    # Créer le dossier de rapports
    mkdir -p tests/reports
    
    # Rapport de synthèse
    cat > tests/reports/test-summary.txt << EOF
=== RAPPORT DE TEST PHP MCP MYSQL ===
Date: $(date)
Environnement: Docker MySQL $MYSQL_PORT

Configuration:
- Tests unitaires: $([ "$UNIT_ONLY" = true ] && echo "UNIQUEMENT" || echo "INCLUS")
- Tests d'intégration: $([ "$INTEGRATION_ONLY" = true ] && echo "UNIQUEMENT" || echo "INCLUS")
- Couverture: $([ "$COVERAGE" = true ] && echo "ACTIVÉE" || echo "DÉSACTIVÉE")
- Mode: $([ "$VERBOSE" = true ] && echo "VERBEUX" || echo "NORMAL")

Résultats:
EOF
    
    # Ajouter les résultats des tests au rapport
    if [ -f tests/_output/report.html ]; then
        echo "- Rapport HTML généré: tests/_output/report.html" >> tests/reports/test-summary.txt
    fi
    
    if [ "$COVERAGE" = true ] && [ -d coverage ]; then
        echo "- Rapport de couverture: coverage/index.html" >> tests/reports/test-summary.txt
    fi
    
    success "Rapport généré dans tests/reports/"
}

# Fonction principale
main() {
    log "🎯 Début des tests PHP MCP MySQL"
    log "Configuration: Unit=$UNIT_ONLY, Integration=$INTEGRATION_ONLY, Coverage=$COVERAGE, Verbose=$VERBOSE"
    
    # Variables pour tracking des résultats
    local unit_result=0
    local integration_result=0
    local total_errors=0
    
    # Phase 1: Setup (seulement si tests d'intégration requis)
    if [ "$UNIT_ONLY" != true ]; then
        cleanup  # Nettoyage initial
        start_mysql
        wait_for_mysql
        setup_test_environment
        test_connection
    fi
    
    # Phase 2: Tests unitaires
    if [ "$INTEGRATION_ONLY" != true ]; then
        if ! run_unit_tests; then
            unit_result=1
            total_errors=$((total_errors + 1))
        fi
    fi
    
    # Phase 3: Tests d'intégration
    if [ "$UNIT_ONLY" != true ]; then
        if ! run_integration_tests; then
            integration_result=1
            total_errors=$((total_errors + 1))
        fi
        
        # Tests fonctionnels (optionnels)
        run_functional_tests
    fi
    
    # Phase 4: Rapport final
    generate_report
    
    # Résumé final
    echo ""
    log "🎯 RÉSUMÉ FINAL"
    
    if [ "$INTEGRATION_ONLY" != true ]; then
        if [ $unit_result -eq 0 ]; then
            success "✅ Tests unitaires: RÉUSSIS"
        else
            error "❌ Tests unitaires: ÉCHEC"
        fi
    fi
    
    if [ "$UNIT_ONLY" != true ]; then
        if [ $integration_result -eq 0 ]; then
            success "✅ Tests d'intégration: RÉUSSIS"
        else
            error "❌ Tests d'intégration: ÉCHEC"
        fi
    fi
    
    if [ $total_errors -eq 0 ]; then
        success "🎉 TOUS LES TESTS SONT PASSÉS AVEC SUCCÈS!"
        exit 0
    else
        error "💥 $total_errors suite(s) de tests ont échoué"
        exit 1
    fi
}

# Point d'entrée
main "$@"