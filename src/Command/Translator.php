<?php

namespace Translator\Command;

use Illuminate\Console\Command;

class Translator extends Command
{
    /**
     * @var string
     */
    protected $signature = 'translator:update';

    /**
     * @var string
     */
    protected $description = 'Search new keys and update translation file';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $translationKeys = $this->findProjectTranslationsKeys();
        $translationFiles = $this->getProjectTranslationFiles();

        foreach ($translationFiles as $file) {
            $translationData = $this->getAlreadyTranslatedKeys($file);
            $this->line("lang " . str_replace('.json', '', basename($file)));
            $added = [];

            foreach ($translationKeys as $key) {
                if (!isset($translationData[$key])) {
                    $this->warn(" - Added {$key}");
                    $translationData[$key] = '';
                    $added[] = $key;
                }
            }

            if ($added) {
                $this->line("updating file...");
                $this->writeNewTranslationFile($file, $translationData);
                $this->info("done!");
            } else {
                $this->warn("new keys not found for this language");
            }
            $this->line("");
        }
        
        $translationJSKeys = $this->findProjectJSTranslationsKeys();
        $translationJSFiles = $this->getProjectJSTranslationFiles();

        foreach ($translationJSFiles as $file) {
            $translationJSData = $this->getAlreadyTranslatedKeys($file);
            $this->line("JS lang " . str_replace('-javascript.json', '', basename($file)));
            $added = [];

            foreach ($translationJSKeys as $key) {
                if (!isset($translationData[$key])) {
                    $this->warn(" - Added {$key}");
                    $translationJSData[$key] = '';
                    $added[] = $key;
                }
            }

            if ($added) {
                $this->line("updating file...");
                $this->writeNewTranslationFile($file, $translationData);
                $this->info("done!");
            } else {
                $this->warn("new keys not found for this language");
            }
            $this->line("");
        }
    }

    /**
     * @return array
     */
    private function findProjectTranslationsKeys()
    {
        $allKeys = [];
        $this->getTranslationKeysFromDir($allKeys, app_path());
        $this->getTranslationKeysFromDir($allKeys, resource_path('views'));
        ksort($allKeys);

        return $allKeys;
    }
    
    /**
     * @return array
     */
    private function findProjectJSTranslationsKeys()
    {
        $allKeys = [];
        $this->getTranslationKeysFromDir($allKeys, resource_path('assets/js'),'vue');
        $this->getTranslationKeysFromDir($allKeys, resource_path('assets/js'),'js');
        ksort($allKeys);

        return $allKeys;
    }

    /**
     * @param array $keys
     * @param string $dirPath
     * @param string $fileExt
     */
    private function getTranslationKeysFromDir(&$keys, $dirPath, $fileExt = 'php')
    {
        $files = glob_recursive("{$dirPath}/*.{$fileExt}", GLOB_BRACE);

        foreach ($files as $file) {
            $content = $this->getSanitizedContent($file);

            $this->getTranslationKeysFromFunction($keys, 'lang', $content);
            $this->getTranslationKeysFromFunction($keys, '__', $content);
        }
    }

    /**
     * @param array $keys
     * @param string $functionName
     * @param string $content
     */
    private function getTranslationKeysFromFunction(&$keys, $functionName, $content)
    {
        $matches = [];
        
        // Find functions __() and lang() and store their content in $matches
        preg_match_all("#{$functionName}\((.*?)\)#", $content, $matches);

        if (!empty($matches)) {
            
            // Loop through the function's "contents", e.g. 'String Name', ['abc' => 'xyz']
            foreach ($matches[1] as $match) {
                $strings = [];
                
                // Find anything that is between ' ' 
                preg_match('#\'(.*?)\'#', str_replace('"', "'", $match), $strings);
                
                if (!empty($strings)) {
                    // Store the first value found which will contain the string to be translated
                    $keys[$strings[1]] = $strings[1];
                }
            }
        }
    }

    /**
     * @return array
     */
    private function getProjectTranslationFiles()
    {
        $path = resource_path('lang');
        $files = glob("{$path}/*.json", GLOB_BRACE);

        return $files;
    }
    
    /**
     * @return array
     */
    private function getProjectJSTranslationFiles()
    {
        $path = resource_path('lang');
        $files = glob("{$path}/*-javascript.json", GLOB_BRACE);

        return $files;
    }

    /**
     * @param string $filePath
     * @return array
     */
    private function getAlreadyTranslatedKeys($filePath)
    {
        $current = json_decode(file_get_contents($filePath), true);
        ksort($current);

        return $current;
    }

    /**
     * @param string $filePath
     * @param array $translations
     */
    private function writeNewTranslationFile($filePath, $translations)
    {
        file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT));
    }

    /**
     * @param string $filePath
     * @return string
     */
    private function getSanitizedContent($filePath)
    {
        return str_replace("\n", ' ', file_get_contents($filePath));
    }

}
