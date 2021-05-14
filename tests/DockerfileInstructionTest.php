<?php

declare(strict_types=1);

namespace axy\docker\dockerfile\builder\tests;

use PHPUnit\Framework\TestCase;
use axy\docker\dockerfile\builder\DockerfileInstruction;

class DockerfileInstructionTest extends TestCase
{
    public function testFull(): void
    {
        $instruction = new DockerfileInstruction([
            'RUN something \\',
            '&& anything',
        ], '  Do something ');
        $this->assertSame("RUN something \\\n&& anything", $instruction->instruction);
        $this->assertSame('Do something', $instruction->comment);
        $this->assertSame(implode("\n", [
            '# Do something',
            'RUN something \\',
            '&& anything',
        ]), (string)$instruction);
        $this->assertSame(implode("\n", [
            'RUN something \\',
            '&& anything',
        ]), $instruction->representation(false));
    }

    public function testWithoutComment(): void
    {
        $instruction = new DockerfileInstruction('RUN something ', '  ');
        $this->assertSame('RUN something', $instruction->instruction);
        $this->assertNull($instruction->comment);
        $this->assertSame('RUN something', (string)$instruction);
        $this->assertSame('RUN something', $instruction->representation(false));
    }
}
