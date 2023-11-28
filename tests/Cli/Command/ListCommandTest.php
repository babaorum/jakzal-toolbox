<?php declare(strict_types=1);

namespace Zalas\Toolbox\Tests\Cli\Command;

use PHPUnit\Framework\MockObject\MockObject;
use Zalas\Toolbox\Cli\Command\ListCommand;
use Zalas\Toolbox\Tool\Collection;
use Zalas\Toolbox\Tool\Command\ShCommand;
use Zalas\Toolbox\Tool\Command\TestCommand;
use Zalas\Toolbox\Tool\Filter;
use Zalas\Toolbox\Tool\Tool;
use Zalas\Toolbox\UseCase\ListTools;

class ListCommandTest extends ToolboxCommandTestCase
{
    protected const CLI_COMMAND_NAME = ListCommand::NAME;

    /**
     * @var ListTools|MockObject
     */
    private $useCase;

    protected function setUp(): void
    {
        $this->useCase = $this->createMock(ListTools::class);

        parent::setUp();
    }

    public function test_it_runs_the_list_tools_use_case()
    {
        $this->useCase->method('__invoke')->willReturn(Collection::create([
            $this->createTool('Behat', 'Tests business expectations', 'http://behat.org'),
        ]));

        $tester = $this->executeCliCommand();

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertMatchesRegularExpression('#Available tools#i', $tester->getDisplay());
        $this->assertMatchesRegularExpression('#Behat.*?Tests business expectations.*?http://behat.org#smi', $tester->getDisplay());
    }

    public function test_it_filters_by_tags()
    {
        $this->useCase->expects(self::once())
            ->method('__invoke')
            ->with(new Filter(['foo'], ['bar']))
            ->willReturn(Collection::create([
                 $this->createTool('Behat', 'Tests business expectations', 'http://behat.org'),
            ]));

        $this->executeCliCommand(['--exclude-tag' => ['foo'], '--tag' => ['bar']]);
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
            ListTools::class => $this->useCase,
        ];
    }

    private function createTool(string $name, string $summary, string $website): Tool
    {
        return new Tool(
            $name,
            $summary,
            $website,
            [],
            new ShCommand('any command'),
            new TestCommand('any test command', 'any')
        );
    }
}
