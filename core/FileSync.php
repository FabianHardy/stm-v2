<?php
/**
 * FileSync - Utilitaire de synchronisation des fichiers uploadés
 * 
 * Permet de copier les fichiers uploadés (images produits, icônes, etc.)
 * de la production vers le développement en mode différentiel.
 * 
 * @package Core
 * @created 2025/11/25 12:00
 */

namespace Core;

use Exception;

class FileSync
{
    /**
     * Répertoire source (prod)
     * @var string
     */
    private string $sourceDir;
    
    /**
     * Répertoire cible (dev) - local
     * @var string
     */
    private string $targetDir;
    
    /**
     * Dossiers à synchroniser
     * @var array
     */
    private array $folders = [
        'uploads/products',      // Images des produits
        'uploads/categories',    // Icônes des catégories
        'uploads/campaigns',     // Bannières des campagnes
        'uploads/misc'           // Autres fichiers
    ];
    
    /**
     * Extensions autorisées
     * @var array
     */
    private array $allowedExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf'
    ];
    
    /**
     * Résultats de la synchronisation
     * @var array
     */
    private array $results = [];
    
    /**
     * Constructeur
     * 
     * @param string $sourceDir Répertoire source (chemin absolu ou URL)
     * @param string $targetDir Répertoire cible local
     */
    public function __construct(string $sourceDir, string $targetDir)
    {
        $this->sourceDir = rtrim($sourceDir, '/');
        $this->targetDir = rtrim($targetDir, '/');
    }
    
    /**
     * Définit les dossiers à synchroniser
     * 
     * @param array $folders
     * @return self
     */
    public function setFolders(array $folders): self
    {
        $this->folders = $folders;
        return $this;
    }
    
    /**
     * Analyse les fichiers à synchroniser (mode différentiel)
     * 
     * Compare les fichiers source et cible et retourne la liste
     * des fichiers à copier (nouveaux ou modifiés)
     * 
     * @return array
     */
    public function analyzeFiles(): array
    {
        $analysis = [
            'folders' => [],
            'total_source_files' => 0,
            'total_target_files' => 0,
            'files_to_copy' => 0,
            'files_up_to_date' => 0,
            'total_size_to_copy' => 0
        ];
        
        foreach ($this->folders as $folder) {
            $sourcePath = $this->sourceDir . '/' . $folder;
            $targetPath = $this->targetDir . '/' . $folder;
            
            $folderAnalysis = [
                'source_path' => $sourcePath,
                'target_path' => $targetPath,
                'source_exists' => is_dir($sourcePath),
                'target_exists' => is_dir($targetPath),
                'files_to_copy' => [],
                'files_up_to_date' => [],
                'source_count' => 0,
                'target_count' => 0
            ];
            
            // Si le dossier source n'existe pas, on passe
            if (!$folderAnalysis['source_exists']) {
                $analysis['folders'][$folder] = $folderAnalysis;
                continue;
            }
            
            // Lister les fichiers source
            $sourceFiles = $this->listFiles($sourcePath);
            $folderAnalysis['source_count'] = count($sourceFiles);
            $analysis['total_source_files'] += count($sourceFiles);
            
            // Créer le dossier cible si nécessaire
            if (!$folderAnalysis['target_exists']) {
                @mkdir($targetPath, 0755, true);
                $folderAnalysis['target_exists'] = is_dir($targetPath);
            }
            
            // Lister les fichiers cible
            $targetFiles = $this->listFiles($targetPath);
            $folderAnalysis['target_count'] = count($targetFiles);
            $analysis['total_target_files'] += count($targetFiles);
            
            // Comparer les fichiers
            foreach ($sourceFiles as $filename => $sourceInfo) {
                if (!isset($targetFiles[$filename])) {
                    // Fichier absent dans la cible → à copier
                    $folderAnalysis['files_to_copy'][$filename] = [
                        'reason' => 'new',
                        'size' => $sourceInfo['size']
                    ];
                    $analysis['files_to_copy']++;
                    $analysis['total_size_to_copy'] += $sourceInfo['size'];
                    
                } elseif ($sourceInfo['size'] !== $targetFiles[$filename]['size'] ||
                          $sourceInfo['mtime'] > $targetFiles[$filename]['mtime']) {
                    // Fichier modifié → à copier
                    $folderAnalysis['files_to_copy'][$filename] = [
                        'reason' => 'modified',
                        'size' => $sourceInfo['size']
                    ];
                    $analysis['files_to_copy']++;
                    $analysis['total_size_to_copy'] += $sourceInfo['size'];
                    
                } else {
                    // Fichier identique → à jour
                    $folderAnalysis['files_up_to_date'][] = $filename;
                    $analysis['files_up_to_date']++;
                }
            }
            
            $analysis['folders'][$folder] = $folderAnalysis;
        }
        
        return $analysis;
    }
    
    /**
     * Liste les fichiers d'un dossier
     * 
     * @param string $path Chemin du dossier
     * @return array
     */
    private function listFiles(string $path): array
    {
        $files = [];
        
        if (!is_dir($path)) {
            return $files;
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                
                if (in_array($extension, $this->allowedExtensions)) {
                    $relativePath = str_replace($path . '/', '', $file->getPathname());
                    $files[$relativePath] = [
                        'path' => $file->getPathname(),
                        'size' => $file->getSize(),
                        'mtime' => $file->getMTime()
                    ];
                }
            }
        }
        
        return $files;
    }
    
    /**
     * Synchronise les fichiers (copie différentielle)
     * 
     * @param array|null $analysis Analyse préalable (optionnel)
     * @return array Résultats de la synchronisation
     */
    public function sync(?array $analysis = null): array
    {
        if ($analysis === null) {
            $analysis = $this->analyzeFiles();
        }
        
        $results = [
            'success' => true,
            'files_copied' => 0,
            'files_failed' => 0,
            'total_size_copied' => 0,
            'errors' => [],
            'details' => []
        ];
        
        foreach ($analysis['folders'] as $folder => $folderData) {
            if (empty($folderData['files_to_copy'])) {
                continue;
            }
            
            $sourcePath = $folderData['source_path'];
            $targetPath = $folderData['target_path'];
            
            foreach ($folderData['files_to_copy'] as $filename => $fileInfo) {
                $sourceFile = $sourcePath . '/' . $filename;
                $targetFile = $targetPath . '/' . $filename;
                
                // Créer le sous-dossier si nécessaire
                $targetSubDir = dirname($targetFile);
                if (!is_dir($targetSubDir)) {
                    @mkdir($targetSubDir, 0755, true);
                }
                
                // Copier le fichier
                if (@copy($sourceFile, $targetFile)) {
                    $results['files_copied']++;
                    $results['total_size_copied'] += $fileInfo['size'];
                    $results['details'][] = [
                        'file' => $folder . '/' . $filename,
                        'status' => 'success',
                        'reason' => $fileInfo['reason']
                    ];
                } else {
                    $results['files_failed']++;
                    $results['success'] = false;
                    $results['errors'][] = "Impossible de copier: {$folder}/{$filename}";
                    $results['details'][] = [
                        'file' => $folder . '/' . $filename,
                        'status' => 'failed',
                        'reason' => $fileInfo['reason']
                    ];
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Formate une taille en bytes de manière lisible
     * 
     * @param int $bytes
     * @return string
     */
    public static function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
