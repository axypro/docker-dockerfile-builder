<?php

declare(strict_types=1);

namespace axy\docker\dockerfile\builder\tests;

use PHPUnit\Framework\TestCase;
use axy\docker\dockerfile\builder\DockerfilePHPBuilder;

class DockerfilePHPBuilderTest extends TestCase
{
    public function testBuild(): void
    {
        $df = new DockerfilePHPBuilder('8.0', 'fpm', '');
        $df->user = '1000:1000';
        $df->packages(['curl', 'libpng'], 'Install Curl');
        $df->env['var'] = 'value';
        $df->extensions = ['bcmath', 'pdo_mysql'];
        $df->pecl = ['xdebug', 'imagick'];
        $df->ini = [
            'error_reporting' => 'E_ALL',
            'display_errors' => 'On',
        ];

        $actual = trim($df->build());
        $expected = trim(file_get_contents(__DIR__ . '/test-dockerfile-php.txt'));
        $this->assertSame($expected, $actual);
    }
}
