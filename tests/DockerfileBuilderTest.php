<?php

declare(strict_types=1);

namespace axy\docker\dockerfile\builder\tests;

use PHPUnit\Framework\TestCase;
use axy\docker\dockerfile\builder\DockerfileBuilder;

class DockerfileBuilderTest extends TestCase
{
    /**
     * @dataProvider providerBuild
     * @param bool $pretty
     */
    public function testBuild(bool $pretty): void
    {
        $df = new DockerfileBuilder('image:2.3-alpine', true);
        $df->entrypoint = '/bin/sh';
        $df->cmd = ['one', 'two', 'three'];
        $df->syntax = 'docker/dockerfile:1';
        $df->directives['x'] = 'y';
        $df->labels['author'] = 'Me';
        $df->labels['version'] = "0.0\n0.1";
        $df->args['one'] = 1;
        $df->args['two'] = "second";
        $df->args['three'] = null;
        $df->env['var'] = 'value';
        $df->user = '1000:1000';
        $df->volumes = ['/var/log', '/home/me'];
        $df->expose = ['80/tcp', '80/udp'];

        $df->packages(['curl', 'libpng'], 'Install Curl');
        $df->workdir('/var/www/app', 'Change work dir');
        $df->packages(['xxx', 'yyy']);
        $df->copy('file.txt', 'file.txt', null, 'File to file');
        $df->add('http://file', 'file');
        $df->workdir('/var/log');
        $df->copy(['a', 'b', 'c'], 'dir', '1000:1000');
        $df->run(['one', 'two', 'three'], 'Run test');

        $actual = trim($df->build($pretty));
        $expected = trim(file_get_contents(__DIR__ . '/test-dockerfile-alpine.txt'));
        if (!$pretty) {
            $expected = str_replace("\n\n", "\n", $expected);
            $e = explode('FROM', $expected, 2);
            $expected = trim(implode('FROM', [
                $e[0],
                preg_replace('/\n#.*?\n/', "\n", $e[1]),
            ]));
        }
        $this->assertSame($expected, $actual);
    }

    public function providerBuild(): array
    {
        return [
            'pretty' => [true],
            'min' => [false],
        ];
    }

    public function testApkNormal(): void
    {
        $df = new DockerfileBuilder('image:2.3', false);
        $df->packages(['curl', 'libpng'], 'Install Curl');
        $df->packages(['xxx', 'yyy']);
        $actual = trim($df->build(true));
        $expected = trim(file_get_contents(__DIR__ . '/test-dockerfile-apk.txt'));
        $this->assertSame($expected, $actual);
    }
}
