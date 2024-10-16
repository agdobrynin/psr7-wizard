<?php

declare(strict_types=1);

use Kaspi\HttpMessage\HttpFactory;
use Kaspi\Psr7Wizard\ServerRequestWizard;
use org\bovigo\vfs\vfsStream;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

\describe('Test for uploaded files', function () {
    \beforeEach(function () {
        $this->httpFactory = new HttpFactory();
        $this->serverRequestWizard = new ServerRequestWizard(
            $this->httpFactory,
            $this->httpFactory,
            $this->httpFactory,
            $this->httpFactory
        );
    });

    \it('one field', function () {
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

        /** @var ServerRequestInterface $sr */
        $sr = $this->serverRequestWizard->fromParams([], files: $files);

        /** @var UploadedFileInterface $file */
        $file = $sr->getUploadedFiles()['docs'];

        \expect($file->getSize())->toBe(19)
            ->and($file->getClientMediaType())->toBe('plain/text')
            ->and($file->getClientFilename())->toBe('my-document.txt')
            ->and($file->getError())->toBe(UPLOAD_ERR_OK)
            ->and((string) $file->getStream())->toBe('Hello world in file')
        ;
    });

    \it('one file with array notation', function () {
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

        /** @var ServerRequestInterface $sr */
        $sr = $this->serverRequestWizard->fromParams([], files: $files);

        /** @var UploadedFileInterface $file */
        $file = $sr->getUploadedFiles()['my-form']['details']['note'];

        \expect($file->getSize())->toBe(20)
            ->and($file->getClientMediaType())->toBe('plain/text')
            ->and($file->getClientFilename())->toBe('my-document.txt')
            ->and($file->getError())->toBe(UPLOAD_ERR_OK)
            ->and((string) $file->getStream())->toBe('Note print here 🎈')
        ;
    });

    \it('multiple upload files', function () {
        $root = vfsStream::setup(structure: [
            'phpmFLrzD' => 'File content 1',
            'phpV2pBil' => 'Content file 2',
            'php8RUG8v' => '🎈',
            'phpPonUpg' => 'I am tester',
            'phpwkiI9l' => \file_get_contents(__DIR__.'/../Fixtures/clip-icon.svg'),
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

        /** @var ServerRequestInterface $sr */
        $sr = $this->serverRequestWizard->fromParams([], files: $files);

        /** @var UploadedFileInterface[] $notes */
        $notes = $sr->getUploadedFiles()['my-form']['details']['notes'];

        \expect($notes)->toHaveCount(3)
        // file 1
            ->and((string) $notes[0]->getStream())->toBe('File content 1')
            ->and($notes[0]->getSize())->toBe(14)
        // file 2
            ->and((string) $notes[1]->getStream())->toBe('Content file 2')
            ->and($notes[1]->getSize())->toBe(14)
        // file 3
            ->and((string) $notes[2]->getStream())->toBe('🎈')
            ->and($notes[2]->getSize())->toBe(4)
        ;

        // File in my-form.details array
        /** @var UploadedFileInterface $details */
        $details = $sr->getUploadedFiles()['my-form']['details'][0];
        \expect($details->getClientMediaType())->toBe('image/svg+xml')
            ->and((string) $details->getStream())->toStartWith('<?xml version="1.0" encoding="utf-8"')
            ->and($details->getSize())->toBe(\filesize(__DIR__.'/../Fixtures/clip-icon.svg'))
        ;

        // resume as one file
        /** @var UploadedFileInterface $resume */
        $resume = $sr->getUploadedFiles()['resume'];

        \expect((string) $resume->getStream())->toBe('I am tester')
            ->and($resume->getError())->toBe(UPLOAD_ERR_OK)
            ->and($resume->getClientFilename())->toBe('resume.txt')
            ->and($resume->getClientMediaType())->toBe('application/msword')
        ;

        // image file not loaded
        /** @var UploadedFileInterface $image */
        $image = $sr->getUploadedFiles()['image'];

        \expect($image->getError())->toBe(UPLOAD_ERR_NO_FILE)
            ->and($image->getSize())->toBe(0)
            ->and($image->getClientFilename())->toBe('')
        ;
    });

    \it('cannot create stream from uploaded file', function () {
        \set_error_handler(static fn () => false);

        $files = [
            'docs' => [
                'tmp_name' => '/tmp/'.\uniqid('fix', true),
                'name' => 'my-document.txt',
                'size' => 19,
                'type' => 'plain/text',
                'error' => 0,
            ],
        ];

        /** @var ServerRequestInterface $sr */
        $sr = $this->serverRequestWizard->fromParams([], files: $files);

        /** @var UploadedFileInterface $docs */
        $docs = $sr->getUploadedFiles()['docs'];

        \expect($docs->getStream()->getSize())->toBe(0)
            ->and((string) $docs->getStream())->toBe('')
        ;

        \restore_error_handler();
    });

    \it('wrong structure for uploaded file', function ($files) {
        \set_error_handler(static fn () => false);

        /** @var ServerRequestInterface $sr */
        $sr = $this->serverRequestWizard->fromParams([], files: $files);
    })
        ->throws(InvalidArgumentException::class)
        ->with([
            'one file without tmp_name key' => [
                [
                    'docs' => [
                        'name' => 'my-document.txt',
                        'error' => 0,
                    ],
                ],
            ],
            'one file without error key' => [
                [
                    'docs' => [
                        'name' => 'my-document.txt',
                        'tmp_name' => '/aaaa.txt',
                    ],
                ],
            ],
            'wrong structure of files' => [
                [
                    'my-form' => [
                        'name' => 'file1.txt',
                        'tmp_name' => (object) [],
                        'error' => 0,
                    ],
                ],
            ],
            'many files without error key' => [
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
                                    0 => '/tmp/phpmFLrzD'.\uniqid('test', true),
                                ],
                                0 => '/tmp/phpmMmTeW'.\uniqid('test', true),
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
            ],
        ])
    ;

    \it('add files with UploadedFileInterface', function () {
        $root = vfsStream::setup();
        $files = [
            $this->httpFactory->createUploadedFile(
                stream: $this->httpFactory->createStreamFromFile(vfsStream::newFile('1')->at($root)->url())
            ),
            $this->httpFactory->createUploadedFile(
                stream: $this->httpFactory->createStreamFromFile(vfsStream::newFile('2')->at($root)->url())
            ),
        ];

        /** @var ServerRequestInterface $sr */
        $sr = $this->serverRequestWizard->fromParams([], files: $files);

        \expect($sr->getUploadedFiles())->toBe($files);
    });
})->covers(ServerRequestWizard::class);
