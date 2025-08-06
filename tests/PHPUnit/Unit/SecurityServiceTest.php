<?php

declare(strict_types=1);

namespace MySqlMcp\Tests\PHPUnit\Unit;

use PHPUnit\Framework\TestCase;
use MySqlMcp\Services\SecurityService;
use MySqlMcp\Exceptions\SecurityException;

class SecurityServiceTest extends TestCase
{
    private SecurityService $securityService;

    protected function setUp(): void
    {
        $config = [
            'ALLOW_DDL_OPERATIONS' => false,
            'ALLOW_ALL_OPERATIONS' => false,
            'BLOCK_DANGEROUS_KEYWORDS' => true
        ];
        $this->securityService = new SecurityService($config);
    }

    public function testValidatesSelectQuery(): void
    {
        // Should not throw exception for SELECT queries
        $this->securityService->validateQuery('SELECT * FROM users');
        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    public function testBlocksDangerousKeywords(): void
    {
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('SHUTDOWN');
        
        $this->securityService->validateQuery('SHUTDOWN');
    }

    public function testBlocksDDLWhenDisabled(): void
    {
        $config = [
            'ALLOW_DDL_OPERATIONS' => false,
            'ALLOW_ALL_OPERATIONS' => false,
            'BLOCK_DANGEROUS_KEYWORDS' => true
        ];
        $securityService = new SecurityService($config);
        
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('ALTER');
        
        $securityService->validateQuery('ALTER TABLE users ADD COLUMN test VARCHAR(50)');
    }

    public function testAllowsDDLWhenEnabled(): void
    {
        $config = [
            'ALLOW_DDL_OPERATIONS' => true,
            'ALLOW_ALL_OPERATIONS' => false,
            'BLOCK_DANGEROUS_KEYWORDS' => true
        ];
        $securityService = new SecurityService($config);
        
        // Should not throw exception when DDL is enabled
        $securityService->validateQuery('ALTER TABLE users ADD COLUMN test VARCHAR(50)');
        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    public function testAllowsAllOperationsWhenEnabled(): void
    {
        $config = [
            'ALLOW_DDL_OPERATIONS' => false,
            'ALLOW_ALL_OPERATIONS' => true,
            'BLOCK_DANGEROUS_KEYWORDS' => true
        ];
        $securityService = new SecurityService($config);
        
        // Should not throw exception in super admin mode
        $securityService->validateQuery('DROP DATABASE test_db');
        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    protected function tearDown(): void
    {
        // Clean up environment variables
        putenv('ALLOW_DDL_OPERATIONS');
        putenv('ALLOW_ALL_OPERATIONS');
    }
}