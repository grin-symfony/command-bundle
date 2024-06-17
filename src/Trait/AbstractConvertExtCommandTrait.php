<?php

namespace GS\Command\Trait;

use function Symfony\Component\String\u;

use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Finder\SplFileInfo;
use Carbon\Carbon;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\{
    Path,
    Filesystem
};
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Helper\{
    ProgressBar,
    Table
};
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Validator\{
    Constraints,
    Validation
};
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\{
    TableSeparator
};
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Completion\{
    CompletionSuggestions,
    CompletionInput
};
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\{
    AsCommand
};
use Symfony\Component\Console\Input\{
    InputArgument,
    InputOption,
    InputInterface
};
use Symfony\Component\Console\Output\{
    OutputInterface
};
use GS\Service\Service\{
    StringService,
    FilesystemService,
    RegexService,
    DumpInfoService
};
use GS\Command\Contracts\AbstractConstructedFromToPathsDataSupplier;
use GS\Command\Contracts\PatternAbleCommandInterface;
use GS\Command\Trait\MoveAbleTrait;
use GS\Command\Trait\AskAbleTrait;
use GS\Command\Trait\OverrideAbleTrait;
use GS\Command\Trait\DumpInfoAbleTrait;
use GS\Command\Trait\DepthAbleTrait;

/*
    Find From files
        (depth, extensions, initFrom)
    then convert it with another .ext
        (to extension, initTo)
*/
trait AbstractConvertExtCommandTrait
{
    use AbstractGetCommandTrait;
    use MoveAbleTrait;
    use DepthAbleTrait;
    use AskAbleTrait;
    use DumpInfoAbleTrait;
    use OverrideAbleTrait;


    //###> ABSTRACT ###

    /* AbstractConvertExtCommandTrait */
    abstract protected function &gsCommandGetStringServiceForTrait(): StringService;

    /* AbstractConvertExtCommandTrait */
    abstract protected function &gsCommandGetFilesystemServiceForTrait(): FilesystemService;

    /* AbstractConvertExtCommandTrait */
    abstract protected function &gsCommandGetDumpInfoServiceForTrait(): DumpInfoService;

    /* AbstractConvertExtCommandTrait */
    abstract protected function &gsCommandGetRegexServiceForTrait(): RegexService;

    /* AbstractConvertExtCommandTrait */
    abstract protected function &isDumpConvertedInfo(): bool;

    /* AbstractConvertExtCommandTrait */
    abstract protected function getFromExtensions(): string|array;

    /* AbstractConvertExtCommandTrait */
    abstract protected function getToExtension(): string;

    /* AbstractConvertExtCommandTrait */
    abstract protected function getFromDescription(): string;

    /* AbstractConvertExtCommandTrait */
    abstract protected function getToDescription(): string;

    /* AbstractConvertExtCommandTrait */
    abstract protected function getDefaultFrom(): string;

    /* AbstractConvertExtCommandTrait */
    abstract protected function getDefaultTo(): string;

    /* AbstractConvertExtCommandTrait */
    abstract protected function saveConvertedTo(
        string $absPathFrom,
        string $absPathTo,
    ): void;

    //###< ABSTRACT ###


    //###> CAN OVERRIDE ###

    protected function finderPass(
        Finder &$finder,
    ): void {
        $finder
            ->notName([
                $this->gsCommandGetRegexServiceForTrait()->getDocxSysFileRegex(),
            ])
        ;
    }

    protected function afterProcessFromToWasMadeDumpInfo(
        string $absPathFrom,
        string $absPathTo,
    ): void {
        $this->gsCommandGetCommandForTrait()->getIo()->note([
            $this->gsCommandGetStringServiceForTrait()->replaceSlashWithSystemDirectorySeparator($absPathTo),
            $this->gsCommandGetCommandForTrait()->getTranslator()->trans('gs_command.convertor.converted'),
        ]);
    }

    protected function afterProcessFromToWasNotMadeDumpInfo(
        string $absPathFrom,
        string $absPathTo,
    ): void {
        $this->gsCommandGetCommandForTrait()->getIo()->warning([
            $this->gsCommandGetStringServiceForTrait()->replaceSlashWithSystemDirectorySeparator($absPathTo),
            $this->gsCommandGetCommandForTrait()->getTranslator()->trans('gs_command.convertor.not_converted'),
        ]);
    }

    //###< CAN OVERRIDE ###


    private ?array $fromExtensions = null;
    private null|array|string $from = null;
    private null|array|string $to = null;

    protected function configure(): void
    {
        $this->configureMoveOption();

        $this->configureDepthOption();

        $this->configureAskOption();

        $this->configureDumpInfoOption();

        $this->configureOverrideOptions();

        $this->gsCommandGetCommandForTrait()->configureOption(
            'from',
            default:        $this->getDefaultFrom(),
            description:    $this->getFromDescription(),
            mode:           InputOption::VALUE_REQUIRED,
        );

        $this->gsCommandGetCommandForTrait()->configureOption(
            'to',
            default:        $this->getDefaultTo(),
            description:    $this->getToDescription(),
            mode:           InputOption::VALUE_REQUIRED,
        );

        $this->gsCommandGetCommandForTrait()->configureOption(
            'dump-converted-info',
            default:        $this->isDumpConvertedInfo(),
            description:    $this->gsCommandGetCommandForTrait()->getTranslator()->trans('gs_command.convertor.is_dump_converting_info'),
            mode:           InputOption::VALUE_NEGATABLE,
        );

        parent::configure();
    }

    public function initialize(
        InputInterface $input,
        OutputInterface $output,
    ) {
        parent::initialize(
            $input,
            $output,
        );

        $this->initializeMoveOption(
            $input,
            $output,
        );

        $this->initializeDepthOption(
            $input,
            $output,
        );

        $this->initializeAskOption(
            $input,
            $output,
        );

        $this->initializeDumpInfoOption(
            $input,
            $output,
        );

        $this->initializeOverrideOptions(
            $input,
            $output,
        );

        $this->gsCommandGetCommandForTrait()->initializeOption(
            $input,
            $output,
            'from',
            $this->from,
        );

        $this->gsCommandGetCommandForTrait()->initializeOption(
            $input,
            $output,
            'to',
            $this->to,
        );

        $this->gsCommandGetCommandForTrait()->initializeOption(
            $input,
            $output,
            'dump-converted-info',
            $this->isDumpConvertedInfo(),
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        return parent::execute(
            $input,
            $output,
        );
    }


    //###> ABSTRACT REALIZATION ###

    /* AbstractCommand */
    protected function command(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->setFromTo();
        //\dd($this->from, $this->to);
        //###> SINGULAR
        if ($this->isFilter(from: $this->from, to: $this->to)) {
            //###>
            $to = \is_string($this->to) ? $this->to : null;

            $from = \is_string($this->from) ? $this->from : null;

            $message = [
                ''
                . $this->gsCommandGetCommandForTrait()->getTranslator()->trans(
                    'gs_command.convertor.there_is_no_newer_file_for_converting',
                    [
                        '%ext%' => $this->getToExtensionInner(),
                    ],
                ),
            ];
            if ($from !== null && $to !== null) {
                $fromToString = [
                    'Откуда:',
                    'Куда:',
                ];
                $message = [
                    ''
                    . $this->gsCommandGetCommandForTrait()->getTranslator()->trans(
                        'gs_command.convertor.have_not_been_converted',
                        [
                            '%ext%' => $this->getToExtensionInner()
                        ],
                    ),
                    \str_pad(
                        $fromToString[0],
                        $this->gsCommandGetStringServiceForTrait()->getOptimalWidthForStrPad(
                            $fromToString[0],
                            $fromToString,
                        )
                    ) . $this->gsCommandGetStringServiceForTrait()->replaceSlashWithSystemDirectorySeparator($from),
                    \str_pad(
                        $fromToString[1],
                        $this->gsCommandGetStringServiceForTrait()->getOptimalWidthForStrPad(
                            $fromToString[1],
                            $fromToString,
                        )
                    ) . $this->gsCommandGetStringServiceForTrait()->replaceSlashWithSystemDirectorySeparator($to),
                ];
            }

            $this->noFilesToConvertMessage(
                $input,
                $output,
                message: $message,
            );
            return Command::SUCCESS;
        }

        //###> PLURAL
        if ($this->isNotThereToConvert()) {
            $this->noFilesToConvertMessage(
                $input,
                $output,
                message: $this->gsCommandGetCommandForTrait()->getTranslator()->trans(
                    'gs_command.convertor.there_is_no_newer_files_for_converting',
                    [
                        '%ext%' => $this->getToExtensionInner(),
                    ],
                ),
            );
            return Command::SUCCESS;
        }

        $this->dumpFromTo(
            $input,
            $output,
        );

        $this->tryToMake(
            $input,
            $output,
        );

        return Command::SUCCESS;
    }

    //###< ABSTRACT REALIZATION ###


    //###> HELPER ###

    private function convertFromToInner(
        string $absPathFrom,
        string $absPathTo,
    ): void {

        $madeResult = $this->gsCommandGetFilesystemServiceForTrait()->executeWithoutChangeMTime(
            $this->saveConvertedTo(...),
            $absPathFrom,
            $absPathTo,
            $this->getOverrideProperty(),
            $this->getMoveProperty(),
        );

        $wasMade = !empty($madeResult);

        if ($this->isDumpConvertedInfo()) {
            if ($wasMade) {
                $this->afterProcessFromToWasMadeDumpInfo(
                    $absPathFrom,
                    $absPathTo,
                );
            } else {
                $this->afterProcessFromToWasNotMadeDumpInfo(
                    $absPathFrom,
                    $absPathTo,
                );
            }
        }
    }

    private function tryToMake(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        if ($this->getAskProperty()) {
            if ($this->gsCommandGetCommandForTrait()->isOk()) {
                $this->makeFromTo();
                return;
            }
            $this->gsCommandGetCommandForTrait()->getIo()->warning(
                $this->gsCommandGetCommandForTrait()->getTranslator()->trans('gs_command.trait.convert_trait.cancel_converting')
            );
            return;
        }
        $this->makeFromTo();
    }

    private function noFilesToConvertMessage(
        InputInterface $input,
        OutputInterface $output,
        string|array $message,
    ): void {
        $this->gsCommandGetCommandForTrait()->getIo()->warning($message);
    }

    private function isNotThereToConvert(): bool
    {
        return empty($this->from);
    }

    private function makeFromTo(): void
    {
        if (\is_string($this->from) && \is_string($this->to)) {
            $this->convertFromToInner($this->from, $this->to);
            return;
        }

        if (\is_array($this->from) && \is_array($this->to) && \count($this->from) === \count($this->to)) {
            $indexedFrom    = \array_values($this->from);
            $indexedTo      = \array_values($this->to);
            foreach ($indexedFrom as $k => $from) {
                $this->convertFromToInner(
                    $from,
                    $indexedTo[$k],
                );
            }
            return;
        }

        throw new \Exception(''
            . ' "'
            . $this->gsCommandGetCommandForTrait()->getTranslator()->trans('gs_command.command_word')
            . ' ' . $this->getName()
            . '" '
            . $this->gsCommandGetCommandForTrait()->getTranslator()->trans(
                'gs_command.trait.convert_trait.didn_not_convert',
            )
            . '!',);
    }

    private function dumpFromTo(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $this->gsCommandGetDumpInfoServiceForTrait()->dumpInfo(
            $this,
            $this->from,
            $this->to,
            dirname: false,
        );
    }

    private function setFromTo(): void
    {
        $this->setFromIfItsWithoutExtTryToGuess();

        /*###> FOR FROM AND TO STRINGS */
        if ($this->assignToIfFromIsFile()) {
            return;
        }

        /*###> FOR FROM AND TO ARRAYS */
        $this->assignFromsAndTosIfFromIsDir();
    }

    private function setFromIfItsWithoutExtTryToGuess(): void
    {
        $from = $this->gsCommandGetStringServiceForTrait()->makeAbsolute(
            $this->from,
            $this->getDefaultFrom(),
        );

        $ext = $this->gsCommandGetStringServiceForTrait()->getExtFromPath(
            $from,
            onlyExistingPath: true,
            amongExtensions: $this->getFromExtensionsInner(),
        );

        $this->from = $this->gsCommandGetStringServiceForTrait()->makeAbsolute(
            $this->gsCommandGetStringServiceForTrait()->getFilenameWithExt(
                $from,
                $ext,
            ),
            $this->gsCommandGetStringServiceForTrait()->getDirectory($from),
        );
    }

    private function assignFromsAndTosIfFromIsDir(): void
    {
        $this->checkFrom();

        $getFromExtensionsInner = $this->getFromExtensionsInner(...);
        $getNormalizedExtension = $this->getNormalizedExtension(...);

        $finder = (new Finder())
            ->ignoreUnreadableDirs()
            ->in($this->from)
            ->files()
            ->depth($this->getDepthProperty())
            ->name([
                //###> FILTERED BY EXTENSION
                $this->getFromExtensionsInnerRegex(),
            ])
        ;

        $this->finderPass(
            $finder,
        );

        $this
            ->setFrom([])
            ->setTo([])
        ;
        foreach ($finder as $finderSplFileInfo) {
            $from   = Path::normalize($finderSplFileInfo->getRealPath());
            $to     = $this->gsCommandGetStringServiceForTrait()->makeAbsolute(
                $finderSplFileInfo->getFilenameWithoutExtension() . '.' . $this->getToExtensionInner(),
                $this->gsCommandGetStringServiceForTrait()->makeAbsolute(
                    $finderSplFileInfo->getRelativePath(),
                    $this->getDefaultTo(),
                ),
            );

            if (
                $this->isFilter(
                    from:   $from,
                    to:     $to,
                )
            ) {
                continue;
            }

            $this->from     [] = $from;
            $this->to       [] = $to;
        }
    }

    private function isFilter(
        $from,
        $to,
    ): bool {
        /* ANALYSIS ONLY STRINGS! */
        if (!\is_string($from) || !\is_string($to)) {
            return false;
        }

        $notOverride            = !$this->getOverrideProperty();
        $toNewer                = !$this->gsCommandGetFilesystemServiceForTrait()->firstFileNewer(
            first:  $from,
            second: $to,
        );

        return $notOverride && $toNewer;
    }

    private function setFrom(null|array|string $from): self
    {
        $this->from = $from;
        return $this;
    }

    private function setTo(null|array|string $to): self
    {
        $this->to = $to;
        return $this;
    }

    private function checkFrom(): void
    {
        $this->gsCommandGetFilesystemServiceForTrait()->throwIfNot(
            [
                'exists',
                'isAbsolutePath',
                'isDir',
            ],
            $this->from,
        );
    }

    private function assignToIfFromIsFile(): bool
    {
        if (!\is_file($this->from)) {
            return false;
        }
        //from is ready
        $this->to = $this->getAbsToWithExt();
        return true;
    }

    /*
        Calls only when $this->from is absolute file
    */
    private function getAbsToWithExt(): string
    {
        $to = $this->to;

        //###>
        if (\is_dir($to)) {
            $filename = $this->gsCommandGetStringServiceForTrait()->getFilenameWithExt(
                $this->from,
                $this->getToExtensionInner(),
            );

            $to = $this->gsCommandGetStringServiceForTrait()->getPath(
                $to,
                $filename,
            );
        } else {
            //###> $to is file
            $toDir = $this->gsCommandGetStringServiceForTrait()->getDirectory($to);
            $this->gsCommandGetFilesystemServiceForTrait()->throwIfNot(
                [
                    'exists',
                    'isDir',
                ],
                $toDir,
            );

            $filename = $this->gsCommandGetStringServiceForTrait()->getFilenameWithExt(
                $to,
                $this->getToExtensionInner(),
            );

            $to = $this->gsCommandGetStringServiceForTrait()->getPath(
                $toDir,
                $filename,
            );
        }

        return $this->gsCommandGetStringServiceForTrait()->makeAbsolute($to, $this->getDefaultTo());
    }

    private function getNormalizedExtension(
        string $ext,
    ): string {
        return \mb_strtolower(
            \trim(
                \ltrim(
                    (string) $ext,
                    '.',
                ),
            ),
        );
    }

    private function getFromExtensionsInner(): array
    {
        if ($this->fromExtensions !== null) {
            return $this->fromExtensions;
        }

        $fromExtensions = $this->getFromExtensions();
        if (\is_string($fromExtensions)) {
            $fromExtensions = [$fromExtensions];
        }

        $this->fromExtensions = \array_map(
            $this->getNormalizedExtension(...),
            $fromExtensions,
        );
        if (\is_string($this->fromExtensions)) {
            $this->fromExtensions = [$this->fromExtensions];
        }

        return $this->fromExtensions;
    }

    private function getToExtensionInner(): string
    {
        return $this->getNormalizedExtension(
            $this->getToExtension(),
        );
    }

    private function getFromExtensionsInnerRegex(): string
    {
        return '~^.*[.](?:' . \implode('|', $this->getFromExtensionsInner()) . ')$~ui';
    }

    //###< HELPER ###
}
