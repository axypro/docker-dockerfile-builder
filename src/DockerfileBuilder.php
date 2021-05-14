<?php

declare(strict_types=1);

namespace axy\docker\dockerfile\builder;

class DockerfileBuilder
{
    /** @var DockerfileInstruction[] */
    public array $instructions = [];

    /** @var string|string[]|null  */
    public string|array|null $cmd = null;

    /** @var string|string[]|null  */
    public string|array|null $entrypoint = null;

    /** @var string[] */
    public array $labels = [];

    /** @var string[] */
    public array $directives = [];

    public ?string $syntax = null;

    public ?string $escape = null;

    /** @var string[] */
    public array $env = [];

    /** @var string[] */
    public array $args = [];

    /** @var string[] */
    public array $volumes = [];

    /** @var string[] */
    public array $expose = [];

    public ?string $user = null;

    public bool $isPackagesCacheDisabled = true;

    /**
     * @param string $image
     * @param bool $isAlpine
     */
    public function __construct(public string $image, public bool $isAlpine = true)
    {
    }

    /**
     * @param string|string[] $instruction
     * @param string|null $comment
     * @return DockerfileInstruction
     */
    public function instruction(string|array $instruction, ?string $comment = null): DockerfileInstruction
    {
        $ins = new DockerfileInstruction($instruction, $comment);
        $this->instructions[] = $ins;
        return $ins;
    }

    /**
     * @param string|array $commands
     * @param string|null $comment
     * @return DockerfileInstruction
     */
    public function run(string|array $commands, ?string $comment = null): DockerfileInstruction
    {
        if (is_array($commands)) {
            $commands = implode(" /\n    && ", $commands);
        }
        return $this->instruction("RUN $commands", $comment);
    }

    public function copy(
        string|array $src,
        string $dest,
        ?string $chown = null,
        ?string $comment = null,
    ): DockerfileInstruction {
        return $this->addOrCopy('COPY', $src, $dest, $chown, $comment);
    }

    public function add(
        string|array $src,
        string $dest,
        ?string $chown = null,
        ?string $comment = null,
    ): DockerfileInstruction {
        return $this->addOrCopy('ADD', $src, $dest, $chown, $comment);
    }

    /**
     * @param array $packages
     * @param string|null $comment
     * @return DockerfileInstruction
     */
    public function packages(array $packages, ?string $comment = null): DockerfileInstruction
    {
        $first = [];
        if (!$this->isPackagesUpdated) {
            $first[] = $this->isAlpine ? 'apk update' : 'apt-get update';
            $this->isPackagesUpdated = true;
        }
        if ($this->isAlpine) {
            $first[] = 'apk add' . ($this->isPackagesCacheDisabled ? ' --no-cache' : '');
        } else {
            $first[] = 'apt-get install -y';
        }
        $lines = [
            implode(' && ', $first),
        ];
        foreach ($packages as $package) {
            $lines[] = "    $package";
        }
        $command = implode(" \\\n", $lines);
        return $this->run($command, $comment);
    }

    /**
     * @param string $dir
     * @param string|null $comment
     * @return DockerfileInstruction
     */
    public function workdir(string $dir, ?string $comment = null): DockerfileInstruction
    {
        return $this->instruction("WORKDIR $dir", $comment);
    }

    public function build(bool $pretty = true): string
    {
        $blocks = $this->getBlocks($pretty);
        $blocks = array_merge(...array_values($blocks));
        $blocks = array_filter($blocks);
        $separator = $pretty ? "\n\n" : "\n";
        return implode($separator, $blocks) . PHP_EOL;
    }

    public function __toString(): string
    {
        return $this->build(true);
    }

    protected function getBlocks(bool $pretty): array
    {
        return [
            'top' => [
                $this->getDirectives(),
                $this->getArgs(),
                "FROM {$this->image}",
                $this->getLabels(),
                $this->getEnv(),
            ],
            'middle' => [
                $this->getInstructions($pretty),
            ],
            'bottom' => [
                $this->getVolumes(),
                $this->getExpose(),
                $this->getDeletePackagesCache(),
                $this->getEntrypoint('ENTRYPOINT', $this->entrypoint),
                $this->getEntrypoint('CMD', $this->cmd),
                $this->getUser(),
            ],
        ];
    }

    private function addOrCopy(
        string $type,
        string|array $src,
        string $dest,
        ?string $chown = null,
        ?string $comment = null,
    ): DockerfileInstruction {
        if (is_array($src)) {
            $src[] = $dest;
            $command = json_encode($src);
        } else {
            $command = "$src $dest";
        }
        if ($chown !== null) {
            $command = "--chown=$chown $command";
        }
        $command = "$type $command";
        return $this->instruction($command, $comment);
    }

    private function getDirectives(): string
    {
        $directives = [];
        if ($this->syntax !== null) {
            $directives['syntax'] = $this->syntax;
        }
        if ($this->escape !== null) {
            $directives['escape'] = $this->escape;
        }
        $directives = array_replace($directives, $this->directives);
        $result = [];
        foreach ($directives as $k => $v) {
            $result[] = "# $k=$v";
        }
        return implode("\n", $result);
    }

    private function getArgs(): string
    {
        $result = [];
        foreach ($this->args as $k => $v) {
            if ($v !== null) {
                $v = '=' . json_encode($v);
            } else {
                $v = '';
            }
            $result[] = "ARG $k$v";
        }
        return implode("\n", $result);
    }

    private function getUser(): string
    {
        if ($this->user === null) {
            return '';
        }
        return "USER {$this->user}";
    }

    private function getLabels(): string
    {
        $result = [];
        foreach ($this->labels as $k => $v) {
            $v = json_encode($v);
            $v = str_replace('\n', "\\\n", $v);
            $result[] = "LABEL $k=$v";
        }
        return implode("\n", $result);
    }

    private function getEnv(): string
    {
        $result = [];
        foreach ($this->env as $k => $v) {
            $result[] = "ENV $k=$v";
        }
        return implode("\n", $result);
    }

    private function getInstructions(bool $pretty): string
    {
        $result = [];
        foreach ($this->instructions as $instruction) {
            $result[] = $instruction->representation($pretty);
        }
        $separator = $pretty ? "\n\n" : "\n";
        return implode($separator, $result);
    }

    private function getEntrypoint(string $type, string|array|null $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_array($value)) {
            $value = json_encode($value);
        }
        return "$type $value";
    }

    private function getVolumes(): string
    {
        if (empty($this->volumes)) {
            return '';
        }
        return 'VOLUME ' . json_encode($this->volumes, JSON_UNESCAPED_SLASHES);
    }

    private function getExpose(): string
    {
        $lines = [];
        foreach ($this->expose as $port) {
            $lines[] = "EXPOSE $port";
        }
        return implode("\n", $lines);
    }

    private function getDeletePackagesCache(): string
    {
        if ($this->isAlpine || (!$this->isPackagesCacheDisabled) || (!$this->isPackagesUpdated)) {
            return '';
        }
        return 'RUN rm -rf /var/lib/apt/lists/*';
    }

    private bool $isPackagesUpdated = false;
}
