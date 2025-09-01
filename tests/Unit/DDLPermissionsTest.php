<?php

declare(strict_types=1);

namespace PostgreSqlMcp\Tests\Unit;

use PostgreSqlMcp\Services\SecurityService;
use PostgreSqlMcp\Exceptions\SecurityException;
use Codeception\Test\Unit;
use Tests\Support\UnitTester;

/**
 * Test des permissions DDL (CREATE, ALTER, DROP) pour PostgreSQL
 * Reproduit et vérifie la correction du même bug que dans php-mcp-mysql
 */
class DDLPermissionsTest extends Unit
{
    protected UnitTester $tester;

    /**
     * Test que les opérations DDL sont bloquées par défaut
     */
    public function testDDLOperationsBlockedByDefault()
    {
        $config = [
            'ALLOW_DDL_OPERATIONS' => 'false',
            'ALLOW_ALL_OPERATIONS' => 'false',
            'BLOCK_DANGEROUS_KEYWORDS' => 'true'
        ];
        
        $securityService = new SecurityService($config);

        // CREATE devrait être bloqué
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Mot-clé non autorisé détecté: CREATE');
        $securityService->validateQuery("CREATE TABLE test (id SERIAL PRIMARY KEY)", 'CREATE');
    }

    /**
     * Test que ALTER est bloqué par défaut
     */
    public function testAlterOperationBlockedByDefault()
    {
        $config = [
            'ALLOW_DDL_OPERATIONS' => 'false',
            'ALLOW_ALL_OPERATIONS' => 'false'
        ];
        
        $securityService = new SecurityService($config);

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Mot-clé non autorisé détecté: ALTER');
        $securityService->validateQuery("ALTER TABLE test ADD COLUMN name VARCHAR(50)", 'ALTER');
    }

    /**
     * Test que DROP est bloqué par défaut
     */
    public function testDropOperationBlockedByDefault()
    {
        $config = [
            'ALLOW_DDL_OPERATIONS' => 'false'
        ];
        
        $securityService = new SecurityService($config);

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Mot-clé non autorisé détecté: DROP');
        $securityService->validateQuery("DROP TABLE test", 'DROP');
    }

    /**
     * Test principal : les opérations DDL sont autorisées quand ALLOW_DDL_OPERATIONS=true
     * Reproduit la correction du bug identique à php-mcp-mysql
     */
    public function testDDLOperationsAllowedWhenConfigured()
    {
        $config = [
            'ALLOW_DDL_OPERATIONS' => 'true',
            'ALLOW_ALL_OPERATIONS' => 'false',
            'BLOCK_DANGEROUS_KEYWORDS' => 'true'
        ];
        
        $securityService = new SecurityService($config);

        // Ces opérations ne devraient plus lever d'exception
        $securityService->validateQuery("CREATE TABLE test (id SERIAL PRIMARY KEY)", 'CREATE');
        $securityService->validateQuery("ALTER TABLE test ADD COLUMN name VARCHAR(50)", 'ALTER');
        $securityService->validateQuery("DROP TABLE test", 'DROP');
        
        // Si nous arrivons ici, le test a réussi
        $this->assertTrue(true);
    }

    /**
     * Test que ALLOW_ALL_OPERATIONS autorise tout y compris DDL
     */
    public function testAllOperationsMode()
    {
        $config = [
            'ALLOW_DDL_OPERATIONS' => 'false', // Même si DDL est false
            'ALLOW_ALL_OPERATIONS' => 'true',   // ALL prime sur DDL
            'BLOCK_DANGEROUS_KEYWORDS' => 'true'
        ];
        
        $securityService = new SecurityService($config);

        // DDL devrait être autorisé grâce à ALLOW_ALL
        $securityService->validateQuery("CREATE TABLE test (id SERIAL PRIMARY KEY)", 'CREATE');
        $securityService->validateQuery("ALTER TABLE test ADD COLUMN name VARCHAR(50)", 'ALTER');
        $securityService->validateQuery("DROP TABLE test", 'DROP');
        
        $this->assertTrue(true);
    }

    /**
     * Test que ALLOW_ALL_OPERATIONS autorise même les mots-clés dangereux spécifiques à PostgreSQL
     */
    public function testAllOperationsModeAllowsDangerousKeywords()
    {
        $config = [
            'ALLOW_ALL_OPERATIONS' => 'true',
            'BLOCK_DANGEROUS_KEYWORDS' => 'true' // Même avec blocage activé
        ];
        
        $securityService = new SecurityService($config);

        // COPY devrait être autorisé car ALLOW_ALL=true prime sur BLOCK_DANGEROUS
        $securityService->validateQuery("COPY users TO '/tmp/users.csv'", 'UNKNOWN');
        
        // VACUUM devrait être autorisé
        $securityService->validateQuery("VACUUM ANALYZE users", 'UNKNOWN');
        
        $this->assertTrue(true);
    }

    /**
     * Test que les mots-clés dangereux PostgreSQL restent bloqués si ALLOW_ALL=false
     */
    public function testPostgresDangerousKeywordsStillBlockedWhenNotAllowAll()
    {
        $config = [
            'ALLOW_DDL_OPERATIONS' => 'true',   // DDL autorisé
            'ALLOW_ALL_OPERATIONS' => 'false',  // Mais pas tout
            'BLOCK_DANGEROUS_KEYWORDS' => 'true'
        ];
        
        $securityService = new SecurityService($config);

        // COPY devrait rester bloqué
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Mot-clé non autorisé détecté: COPY');
        $securityService->validateQuery("COPY users TO '/tmp/users.csv'", 'UNKNOWN');
    }

    /**
     * Test que les requêtes DDL PostgreSQL complexes fonctionnent
     */
    public function testComplexPostgresDDLQueries()
    {
        $config = ['ALLOW_DDL_OPERATIONS' => 'true'];
        $securityService = new SecurityService($config);

        // Requêtes DDL complexes spécifiques à PostgreSQL
        $complexQueries = [
            "CREATE TABLE users (id SERIAL PRIMARY KEY, email VARCHAR(255) UNIQUE, created_at TIMESTAMP DEFAULT NOW())",
            "ALTER TABLE users ADD COLUMN status user_status_enum DEFAULT 'active', ADD CONSTRAINT email_check CHECK (email ~ '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$')",
            "CREATE INDEX CONCURRENTLY idx_users_email ON users USING btree (email)",
            "DROP INDEX IF EXISTS idx_users_email"
        ];

        foreach ($complexQueries as $query) {
            $securityService->validateQuery($query, 'CREATE'); // L'opération importe peu ici
        }
        
        $this->assertTrue(true);
    }

    /**
     * Test que les faux positifs sont évités (ex: created_at ne déclenche pas CREATE)
     */
    public function testWordBoundariesPreventFalsePositives()
    {
        $config = ['ALLOW_DDL_OPERATIONS' => 'false'];
        $securityService = new SecurityService($config);

        // Ces requêtes ne devraient pas être bloquées car elles contiennent des mots
        // qui incluent les mots-clés DDL mais ne sont pas des mots-clés complets
        $securityService->validateQuery("SELECT created_at, altered_by FROM users", 'SELECT');
        $securityService->validateQuery("SELECT * FROM users WHERE name = 'CreateUser'", 'SELECT');
        $securityService->validateQuery("UPDATE users SET recreated_flag = true WHERE id = 1", 'UPDATE');
        
        $this->assertTrue(true);
    }

    /**
     * Test spécifique PostgreSQL : les commandes VACUUM restent bloquées par défaut
     */
    public function testPostgresSpecificDangerousCommands()
    {
        $config = [
            'ALLOW_DDL_OPERATIONS' => 'true',   // DDL autorisé
            'ALLOW_ALL_OPERATIONS' => 'false',  // Mais pas tout
            'BLOCK_DANGEROUS_KEYWORDS' => 'true'
        ];
        
        $securityService = new SecurityService($config);

        // VACUUM devrait être bloqué
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Mot-clé non autorisé détecté: VACUUM');
        $securityService->validateQuery("VACUUM ANALYZE users", 'UNKNOWN');
    }
}