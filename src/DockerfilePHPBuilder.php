<?php

declare(strict_types=1);

namespace axy\docker\dockerfile\builder;

class DockerfilePHPBuilder extends DockerfileBuilder
{
    public bool $isComposerRequired = true;

    public array $extensions = [];

    public array $pecl = [];

    public array $ini = [];

    public function __construct(?string $version, ?string $sapi, string|bool $alpine)
    {
        $isAlpine = ($alpine !== false);
        $image = array_values([$version, $sapi]);
        if ($isAlpine) {
            if ($alpine === true) {
                $alpine = '';
            }
            $image[] = "alpine$alpine";
        }
        $image = implode('-', $image);
        $image = "php:$image";
        parent::__construct($image, $isAlpine);
    }

    protected function getBlocks(bool $pretty): array
    {
        $commands = [
            $this->getExtensions(),
            $this->getPecl(),
            $this->getExtensionsEnable(),
            $this->getIni(),
            $this->getComposer(),
        ];
        $commands = array_filter($commands);
        $blocks = parent::getBlocks($pretty);
        if (!empty($commands)) {
            $blocks['middle'][] = 'RUN ' . implode(" \\\n&& ", $commands);
        }
        return $blocks;
    }

    private function getExtensions(): string
    {
        if (empty($this->extensions)) {
            return '';
        }
        $commands = array_merge([
            'docker-php-ext-install',
        ], $this->extensions);
        return implode(" \\\n    ", $commands);
    }

    private function getPecl(): string
    {
        if (empty($this->pecl)) {
            return '';
        }
        $commands = array_merge([
            'pecl install',
        ], $this->pecl);
        return implode(" \\\n    ", $commands);
    }

    private function getExtensionsEnable(): string
    {
        if (empty($this->pecl)) {
            return '';
        }
        $commands = array_merge([
            'docker-php-ext-enable',
        ], $this->pecl);
        return implode(" \\\n    ", $commands);
    }

    private function getIni(): string
    {
        $ini = [];
        foreach ($this->ini as $k => $v) {
            $ini[] = "echo \"$k=$v\" >> /usr/local/etc/php/conf.d/docker.ini";
        }
        return implode(" \\\n&& ", $ini);
    }

    private function getComposer(): string
    {
        if (!$this->isComposerRequired) {
            return '';
        }
        return 'curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer';
    }
}
