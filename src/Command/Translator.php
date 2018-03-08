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
     * @throws \Exception
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
            $this->line("JS lang " . str_replace('.json', '', basename($file)));
            $added = [];

            foreach ($translationJSKeys as $key) {
                if (!isset($translationJSData[$key])) {
                    $this->warn(" - Added {$key}");
                    $translationJSData[$key] = '';
                    $added[] = $key;
                }
            }
            
            if ($added) {
                $this->line("updating file...");
                $this->writeNewTranslationFile($file, $translationJSData);
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
        $this->getTranslationKeysFromDir($allKeys, resource_path('assets/js'),'vue',true);
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
    private function getTranslationKeysFromDir(&$keys, $dirPath, $fileExt = 'php', $includeBracketsInRegex = false)
    {
        $files = glob_recursive("{$dirPath}/*.{$fileExt}", GLOB_BRACE);

        foreach ($files as $file) {
            $content = $this->getSanitizedContent($file);

            $this->getTranslationKeysFromFunction($keys, 'lang', $content, $includeBracketsInRegex);
            $this->getTranslationKeysFromFunction($keys, '__', $content, $includeBracketsInRegex);
        }
    }

    /**
     * @param array $keys
     * @param string $functionName
     * @param string $content
     */
    private function getTranslationKeysFromFunction(&$keys, $functionName, $content, $includeBracketsInRegex = false)
    {
        $matches = [];
        
        if($includeBracketsInRegex) {
            $regex = "#\{\{(.*?){$functionName}\((.*?)\)(.*?)\}\}#";
        } else {
            $regex = "#{$functionName}\((.*?)\)#";
        }

        
        // Find functions __() and lang() and store their content in $matches
        preg_match_all($regex, $content, $matches);

        if (!empty($matches)) {
            
            
            if($includeBracketsInRegex) {
                $the_matches = $matches[2];
            } else {
                $the_matches = $matches[1];
            }
            
            // Loop through the function's "contents", e.g. 'String Name', ['abc' => 'xyz']
            foreach ($the_matches as $match) {
                $strings = [];
                
                // Find anything that is between ' ' 
                preg_match('#\"(.*?)\"#', $match, $strings);
                
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
        $files = glob("{$path}/_javascript/*.json", GLOB_BRACE);

        return $files;
    }

    /**
     * @param $filePath
     * @return mixed
     * @throws \Exception
     */
    private function getAlreadyTranslatedKeys($filePath)
    {
        $current = json_decode(file_get_contents($filePath), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Unable to load json file, check if it has a valid json and try again');
        }
        
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
