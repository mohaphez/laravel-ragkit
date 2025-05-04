<?php

namespace RagKit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RagKit\Contracts\RagServiceInterface;
use RagKit\Models\RagAccount;
use RagKit\Models\RagCollection;

class RagDocumentImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ragkit:import
                            {directory : Directory containing files to import}
                            {collection_id : ID of the collection to import documents into}
                            {--recursive : Import files recursively from subdirectories}
                            {--extensions=pdf,docx,doc,txt : Comma-separated list of file extensions to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import documents from a directory into a RagKit collection';

    /**
     * @var RagServiceInterface
     */
    protected $ragService;

    /**
     * Create a new command instance.
     *
     * @param RagServiceInterface $ragService
     */
    public function __construct(RagServiceInterface $ragService)
    {
        parent::__construct();
        $this->ragService = $ragService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $directory = $this->argument('directory');
        $collectionId = $this->argument('collection_id');
        $recursive = $this->option('recursive');
        $extensions = explode(',', $this->option('extensions'));

        if (!file_exists($directory) || !is_dir($directory)) {
            $this->error("Directory not found: {$directory}");
            return 1;
        }

        $collection = RagCollection::find($collectionId);
        if (!$collection) {
            $this->error("Collection not found with ID: {$collectionId}");
            return 1;
        }

        $this->info("Importing documents from {$directory} to collection '{$collection->name}'");

        if ($recursive) {
            $files = $this->getFilesRecursively($directory, $extensions);
        } else {
            $files = $this->getFiles($directory, $extensions);
        }

        $totalFiles = count($files);
        if ($totalFiles === 0) {
            $this->warn("No matching files found in the directory.");
            return 0;
        }

        $this->info("Found {$totalFiles} files to import.");

        $importedCount = 0;
        $failedCount = 0;

        foreach ($files as $file) {
            $fileName = basename($file);
            $result = $this->importFile($collection, $file, $fileName);
            if ($result) {
                $importedCount++;
            } else {
                $failedCount++;
            }
        }

        $this->info("Import completed:");
        $this->info("- Successfully imported: {$importedCount}");
        if ($failedCount > 0) {
            $this->warn("- Failed to import: {$failedCount}");
        }

        return 0;
    }

    /**
     * Import a single file into the collection.
     *
     * @param RagCollection $collection
     * @param string $filePath
     * @param string $fileName
     * @return bool
     */
    protected function importFile(RagCollection $collection, string $filePath, string $fileName): bool
    {
        try {
            $document = $this->ragService->uploadDocument(
                $collection,
                $filePath,
                $fileName,
                [
                    'source' => 'command_import',
                    'imported_at' => now()->toDateTimeString(),
                ]
            );

            if ($document !== null) {
                $this->info("Successfully imported {$fileName}");
                return true;
            }
            return false;
        } catch (\Exception $e) {
            $this->error("Error importing {$fileName}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get files from a directory with specified extensions.
     *
     * @param string $directory
     * @param array $extensions
     * @return array
     */
    protected function getFiles(string $directory, array $extensions): array
    {
        $files = [];
        $dirContents = scandir($directory);

        foreach ($dirContents as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;
            if (is_file($path) && $this->hasAllowedExtension($item, $extensions)) {
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * Get files recursively from a directory with specified extensions.
     *
     * @param string $directory
     * @param array $extensions
     * @return array
     */
    protected function getFilesRecursively(string $directory, array $extensions): array
    {
        $files = [];
        $dirContents = scandir($directory);

        foreach ($dirContents as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;
            if (is_dir($path)) {
                $files = array_merge($files, $this->getFilesRecursively($path, $extensions));
            } elseif (is_file($path) && $this->hasAllowedExtension($item, $extensions)) {
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * Check if a file has an allowed extension.
     *
     * @param string $fileName
     * @param array $extensions
     * @return bool
     */
    protected function hasAllowedExtension(string $fileName, array $extensions): bool
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        return in_array($extension, $extensions);
    }
} 