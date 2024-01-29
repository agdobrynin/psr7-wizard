<?php

declare(strict_types=1);

use Kaspi\HttpMessage\HttpFactory;
use Kaspi\Psr7Wizard\ServerRequestWizard;
use org\bovigo\vfs\vfsStream;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

\describe('Test for uploaded files', function () {
    \beforeEach(function () {
        $httpFactory = new HttpFactory();
        $this->serverRequestWizard = new ServerRequestWizard(
            $httpFactory,
            $httpFactory,
            $httpFactory,
            $httpFactory
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
                    ],
                ],
                'type' => [
                    'details' => [
                        'notes' => [
                            0 => 'plain/text',
                            1 => 'plain/text',
                            2 => 'plain/text',
                        ],
                    ],
                ],
                'tmp_name' => [
                    'details' => [
                        'notes' => [
                            0 => 'vfs://root/phpmFLrzD',
                            1 => 'vfs://root/phpV2pBil',
                            2 => 'vfs://root/php8RUG8v',
                        ],
                    ],
                ],
                'error' => [
                    'details' => [
                        'notes' => [
                            0 => 0,
                            1 => 0,
                            2 => 0,
                        ],
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
})->covers(ServerRequestWizard::class);
