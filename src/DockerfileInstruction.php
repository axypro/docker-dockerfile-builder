<?php

declare(strict_types=1);

namespace axy\docker\dockerfile\builder;

class DockerfileInstruction
{
    public string $instruction;

    public ?string $comment;

    public function __construct(string|array $instruction, ?string $comment = null)
    {
        if (is_array($instruction)) {
            $instruction = implode("\n", $instruction);
        }
        $this->instruction = trim($instruction);
        if (is_string($comment)) {
            $comment = trim($comment);
            if ($comment === '') {
                $comment = null;
            }
        }
        $this->comment = $comment;
    }

    public function representation(bool $withComment = true): string
    {
        $result = $this->instruction;
        if ($withComment && ($this->comment !== null)) {
            $result = "# {$this->comment}\n{$result}";
        }
        return $result;
    }

    public function __toString(): string
    {
        return $this->representation(true);
    }
}
