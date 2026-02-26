<?php

declare(strict_types=1);

namespace Tests\Kaspi\Psr7Wizard;

use Generator;
use InvalidArgumentException;
use Kaspi\HttpMessage\HttpFactory;
use Kaspi\Psr7Wizard\ServerRequestWizard;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;

use function file_get_contents;
use function filesize;
use function restore_error_handler;
use function set_error_handler;
use function uniqid;

/**
 * @internal
 */
#[CoversClass(ServerRequestWizard::class)]
class UploadedFilesTest extends TestCase
{
    protected HttpFactory $httpFactory;

    protected function setUp(): void
    {
        $this->httpFactory = new HttpFactory();
        $this->serverRequestWizard = new ServerRequestWizard(
            $this->httpFactory,
            $this->httpFactory,
            $this->httpFactory,
            $this->httpFactory
        );
    }

    protected function tearDown(): void
    {
        unset($this->httpFactory);
    }

    public function testOneField(): void
    {
        vfsStream::setup(structure: [
            'phpUxcOty' => 'Hello world in file',
        ]);

        $files = [
            'docs' => [
                'tmp_name' => 'vfs://root/phpUxcOty',
                'name' => 'my-document.txt',
                'size' => 19,
                'type' => 'plain/text',
                'error' => 0,
            ],
        ];

        $sr = $this->serverRequestWizard->fromParams([], files: $files);

        /** @var UploadedFileInterface $file */
        $file = $sr->getUploadedFiles()['docs'];

        self::assertEquals(19, $file->getSize());
        self::assertEquals('plain/text', $file->getClientMediaType());
        self::assertEquals('my-document.txt', $file->getClientFilename());
        self::assertEquals(UPLOAD_ERR_OK, $file->getError());
        self::assertEquals('Hello world in file', (string) $file->getStream());
    }

    public function testOneFileWithArrayNotation(): void
    {
        vfsStream::setup(structure: [
            'phpmFLrzD' => 'Note print here 🎈',
        ]);

        $files = [
            'my-form' => [
                'name' => [
                    'details' => [
                        'note' => 'my-document.txt',
                    ],
                ],
                'type' => [
                    'details' => [
                        'note' => 'plain/text',
                    ],
                ],
                'tmp_name' => [
                    'details' => [
                        'note' => 'vfs://root/phpmFLrzD',
                    ],
                ],
                'error' => [
                    'details' => [
                        'note' => 0,
                    ],
                ],
            ],
        ];

        $sr = $this->serverRequestWizard->fromParams([], files: $files);

        /** @var UploadedFileInterface $file */
        $file = $sr->getUploadedFiles()['my-form']['details']['note'];

        self::assertEquals(20, $file->getSize());
        self::assertEquals('plain/text', $file->getClientMediaType());
        self::assertEquals('my-document.txt', $file->getClientFilename());
        self::assertEquals(UPLOAD_ERR_OK, $file->getError());
        self::assertEquals('Note print here 🎈', (string) $file->getStream());
    }

    public function testMultipleUploadFiles(): void
    {
        vfsStream::setup(structure: [
            'phpmFLrzD' => 'File content 1',
            'phpV2pBil' => 'Content file 2',
            'php8RUG8v' => '🎈',
            'phpPonUpg' => 'I am tester',
            'phpwkiI9l' => file_get_contents(__DIR__.'/Fixtures/clip-icon.svg'),
        ]);

        $files = [
            'my-form' => [
                'name' => [
                    'details' => [
                        'notes' => [
                            0 => 'note-first.txt',
                            1 => 'note-second.txt',
                            2 => 'note.txt',
                        ],
                        0 => 'clip.svg',
                    ],
                ],
                'type' => [
                    'details' => [
                        'notes' => [
                            0 => 'plain/text',
                            1 => 'plain/text',
                            2 => 'plain/text',
                        ],
                        0 => 'image/svg+xml',
                    ],
                ],
                'tmp_name' => [
                    'details' => [
                        'notes' => [
                            0 => 'vfs://root/phpmFLrzD',
                            1 => 'vfs://root/phpV2pBil',
                            2 => 'vfs://root/php8RUG8v',
                        ],
                        0 => 'vfs://root/phpwkiI9l',
                    ],
                ],
                'error' => [
                    'details' => [
                        'notes' => [
                            0 => 0,
                            1 => 0,
                            2 => 0,
                        ],
                        0 => 0,
                    ],
                ],
            ],
            'resume' => [
                'name' => 'resume.txt',
                'type' => 'application/msword',
                'tmp_name' => 'vfs://root/phpPonUpg',
                'error' => 0,
            ],
            'image' => [
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => 4,
            ],
        ];

        $sr = $this->serverRequestWizard->fromParams([], files: $files);

        /** @var UploadedFileInterface[] $notes */
        $notes = $sr->getUploadedFiles()['my-form']['details']['notes'];

        self::assertCount(3, $notes);
        self::assertEquals('File content 1', (string) $notes[0]->getStream());
        self::assertEquals(14, $notes[0]->getSize());

        self::assertEquals('Content file 2', (string) $notes[1]->getStream());
        self::assertEquals(14, (string) $notes[1]->getSize());

        self::assertEquals('🎈', (string) $notes[2]->getStream());
        self::assertEquals(4, (string) $notes[2]->getSize());

        // File in my-form.details array
        /** @var UploadedFileInterface $details */
        $details = $sr->getUploadedFiles()['my-form']['details'][0];

        self::assertEquals('image/svg+xml', $details->getClientMediaType());
        self::assertStringStartsWith('<?xml version="1.0" encoding="utf-8"', (string) $details->getStream());
        self::assertEquals(filesize(__DIR__.'/Fixtures/clip-icon.svg'), $details->getSize());

        // resume as one file
        /** @var UploadedFileInterface $resume */
        $resume = $sr->getUploadedFiles()['resume'];

        self::assertEquals('I am tester', (string) $resume->getStream());
        self::assertEquals(UPLOAD_ERR_OK, $resume->getError());
        self::assertEquals('resume.txt', $resume->getClientFilename());
        self::assertEquals('application/msword', $resume->getClientMediaType());

        // image file not loaded
        /** @var UploadedFileInterface $image */
        $image = $sr->getUploadedFiles()['image'];

        self::assertEquals(UPLOAD_ERR_NO_FILE, $image->getError());
        self::assertEquals(0, $image->getSize());
        self::assertEquals('', $image->getClientFilename());
    }

    public function testCannotCreateStreamFromUploadedFile(): void
    {
        set_error_handler(static fn () => false);

        $files = [
            'docs' => [
                'tmp_name' => '/tmp/'.uniqid('fix', true),
                'name' => 'my-document.txt',
                'size' => 19,
                'type' => 'plain/text',
                'error' => 0,
            ],
        ];

        $sr = $this->serverRequestWizard->fromParams([], files: $files);

        /** @var UploadedFileInterface $docs */
        $docs = $sr->getUploadedFiles()['docs'];

        self::assertEquals(0, $docs->getStream()->getSize());
        self::assertEquals('', (string) $docs->getStream());

        restore_error_handler();
    }

    #[DataProvider('dataProviderWrongStructureForUploadedFile')]
    public function testWrongStructureForUploadedFile($files): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->serverRequestWizard->fromParams([], files: $files);
    }

    public static function dataProviderWrongStructureForUploadedFile(): Generator
    {
        yield 'one file without tmp_name key' => [
            [
                'docs' => [
                    'name' => 'my-document.txt',
                    'error' => 0,
                ],
            ],
        ];

        yield 'one file without error key' => [
            [
                'docs' => [
                    'name' => 'my-document.txt',
                    'tmp_name' => '/aaaa.txt',
                ],
            ],
        ];

        yield 'wrong structure of files' => [
            [
                'my-form' => [
                    'name' => 'file1.txt',
                    'tmp_name' => (object) [],
                    'error' => 0,
                ],
            ],
        ];

        yield 'many files without error key' => [
            [
                'my-form' => [
                    'name' => [
                        'details' => [
                            'notes' => [
                                0 => 'note-first.txt',
                            ],
                            0 => 'clip.svg',
                        ],
                    ],
                    'type' => [
                        'details' => [
                            'notes' => [
                                0 => 'plain/text',
                            ],
                            0 => 'image/svg+xml',
                        ],
                    ],
                    'tmp_name' => [
                        'details' => [
                            'notes' => [
                                0 => '/tmp/phpmFLrzD'.uniqid('test', true),
                            ],
                            0 => '/tmp/phpmMmTeW'.uniqid('test', true),
                        ],
                    ],
                    'error' => [
                        'details' => [
                            'notes' => [
                                0 => 0,
                            ],
                            // Error key for field my-form[details][0] must be here
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testAddFilesWithUploadedFileInterface(): void
    {
        $root = vfsStream::setup();
        $files = [
            $this->httpFactory->createUploadedFile(
                stream: $this->httpFactory->createStreamFromFile(vfsStream::newFile('1')->at($root)->url())
            ),
            $this->httpFactory->createUploadedFile(
                stream: $this->httpFactory->createStreamFromFile(vfsStream::newFile('2')->at($root)->url())
            ),
        ];

        $sr = $this->serverRequestWizard->fromParams([], files: $files);

        self::assertEquals($files, $sr->getUploadedFiles());
    }
}
