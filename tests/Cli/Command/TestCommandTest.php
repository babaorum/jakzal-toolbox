<?php declare(strict_types=1);

namespace Zalas\Toolbox\Tests\Cli\Command;

use PHPUnit\Framework\MockObject\Stub;
use Zalas\Toolbox\Cli\Command\TestCommand;
use Zalas\Toolbox\Runner\Runner;
use Zalas\Toolbox\Tool\Command;
use Zalas\Toolbox\Tool\Command\ShCommand;
use Zalas\Toolbox\Tool\Filter;
use Zalas\Toolbox\UseCase\TestTools;

class TestCommandTest extends ToolboxCommandTestCase
{
    protected const CLI_COMMAND_NAME = TestCommand::NAME;

    /**
     * @var Runner|Stub
     */
    private $runner;

    /**
     * @var TestTools|Stub
     */
    private $useCase;

    protected function setUp(): void
    {
        $this->runner = $this->createStub(Runner::class);
        $this->useCase = $this->createStub(TestTools::class);

        parent::setUp();
    }

    public function test_it_runs_the_test_tools_use_case()
    {
        $command = $this->createCommand();
        $this->useCase->method('__invoke')->willReturn($command);
        $this->runner->method('run')->with($command)->willReturn(0);

        $tester = $this->executeCliCommand();

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function test_it_returns_the_status_code_of_the_run()
    {
        $this->useCase->method('__invoke')->willReturn($this->createCommand());
        $this->runner->method('run')->willReturn(1);

        $tester = $this->executeCliCommand();

        $this->assertSame(1, $tester->getStatusCode());
    }

    public function test_it_filters_by_tags()
    {
        $this->useCase->method('__invoke')->with(new Filter(['foo'], ['bar']))->willReturn($this->createCommand());
        $this->runner->method('run')->willReturn(0);

        $tester = $this->executeCliCommand(['--exclude-tag' => ['foo'], '--tag' => ['bar']]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function test_it_defines_dry_run_option()
    {
        $this->assertTrue($this->cliCommand()->getDefinition()->hasOption('dry-run'));
    }

    public function test_it_defines_target_dir_option()
    {
        $this->assertSame('/usr/local/bin', $this->cliCommand()->getDefinition()->getOption('target-dir')->getDefault());
        $this->assertTrue($this->cliCommand()->getDefinition()->hasOption('target-dir'));
    }

    /**
     * @putenv TOOLBOX_TARGET_DIR=/tmp
     */
    public function test_it_takes_the_target_dir_option_default_from_environment_if_present()
    {
        $this->assertSame('/tmp', $this->cliCommand()->getDefinition()->getOption('target-dir')->getDefault());
    }

    public function test_it_defines_exclude_tag_option()
    {
        $this->assertTrue($this->cliCommand()->getDefinition()->hasOption('exclude-tag'));
        $this->assertSame([], $this->cliCommand()->getDefinition()->getOption('exclude-tag')->getDefault());
    }

    /**
     * @putenv TOOLBOX_EXCLUDED_TAGS=foo,bar,baz
     */
    public function test_it_takes_the_excluded_tag_option_default_from_environment_if_present()
    {
        $this->assertSame(['foo', 'bar', 'baz'], $this->cliCommand()->getDefinition()->getOption('exclude-tag')->getDefault());
    }

    public function test_it_defines_tag_option()
    {
        $this->assertTrue($this->cliCommand()->getDefinition()->hasOption('tag'));
    }

    /**
     * @putenv TOOLBOX_TAGS=foo,bar,baz
     */
    public function test_it_takes_the_tag_option_default_from_environment_if_present()
    {
        $this->assertSame(['foo', 'bar', 'baz'], $this->cliCommand()->getDefinition()->getOption('tag')->getDefault());
    }

    protected function getContainerTestDoubles(): array
    {
        return [
            Runner::class => $this->runner,
            TestTools::class => $this->useCase,
        ];
    }

    private function createCommand(): Command
    {
        return new ShCommand('true');
    }
}
