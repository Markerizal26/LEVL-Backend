<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure test database exists
        // Note: RefreshDatabase trait will handle migrations
        $this->ensureTestDatabaseExists();
    }

    /**
     * Ensure the test database exists.
     */
    protected function ensureTestDatabaseExists(): void
    {
        $database = config('database.connections.mysql.database');
        $host = config('database.connections.mysql.host');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        try {
            // Try to connect to MySQL server (without database)
            $pdo = new \PDO(
                "mysql:host={$host};port=" . config('database.connections.mysql.port', 3306),
                $username,
                $password
            );

            // Create database if it doesn't exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (\PDOException $e) {
            // Database server might not be available, skip creation
            // Tests will fail with proper error message
        }
    }
}
