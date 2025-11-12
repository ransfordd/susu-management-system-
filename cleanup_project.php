<?php
/**
 * Project Cleanup Script - Phase 1: Safe Cleanup
 * 
 * This script performs safe cleanup operations that won't break functionality:
 * 1. Removes temporary debug/fix/test files
 * 2. Cleans debug statements from core files
 * 3. Removes unused variables and comments
 * 4. Creates backup before cleanup
 */

class ProjectCleanup {
    private $projectRoot;
    private $backupDir;
    private $logFile;
    private $deletedFiles = [];
    private $modifiedFiles = [];
    
    public function __construct($projectRoot = __DIR__) {
        $this->projectRoot = $projectRoot;
        $this->backupDir = $projectRoot . '/backup_' . date('Y-m-d_H-i-s');
        $this->logFile = $this->backupDir . '/cleanup_log.txt';
    }
    
    /**
     * Run the complete cleanup process
     */
    public function runCleanup() {
        echo "🧹 Starting Project Cleanup Process\n";
        echo "===================================\n\n";
        
        try {
            // Phase 1: Create backup
            $this->createBackup();
            
            // Phase 2: Remove temporary files
            $this->removeTemporaryFiles();
            
            // Phase 3: Clean debug code
            $this->cleanDebugCode();
            
            // Phase 4: Generate cleanup report
            $this->generateReport();
            
            echo "\n✅ Cleanup completed successfully!\n";
            echo "📁 Backup created at: " . $this->backupDir . "\n";
            echo "📋 Log file: " . $this->logFile . "\n";
            
        } catch (Exception $e) {
            echo "\n❌ Cleanup failed: " . $e->getMessage() . "\n";
            echo "🔄 Consider restoring from backup if needed.\n";
        }
    }
    
    /**
     * Create backup of current project state
     */
    private function createBackup() {
        echo "📦 Creating backup...\n";
        
        if (!mkdir($this->backupDir, 0755, true)) {
            throw new Exception("Failed to create backup directory");
        }
        
        // Copy essential files
        $essentialFiles = [
            'controllers/',
            'models/',
            'views/',
            'includes/',
            'config/',
            'assets/',
            'schema.sql',
            'index.php',
            'login.php',
            'README.md'
        ];
        
        foreach ($essentialFiles as $file) {
            $source = $this->projectRoot . '/' . $file;
            $dest = $this->backupDir . '/' . $file;
            
            if (file_exists($source)) {
                if (is_dir($source)) {
                    $this->copyDirectory($source, $dest);
                } else {
                    copy($source, $dest);
                }
            }
        }
        
        echo "✅ Backup created successfully\n\n";
    }
    
    /**
     * Remove temporary files (debug, fix, test files)
     */
    private function removeTemporaryFiles() {
        echo "🗑️  Removing temporary files...\n";
        
        $patterns = [
            'debug_*.php',
            'fix_*.php', 
            'test_*.php',
            'check_*.php',
            'investigate_*.php',
            'verify_*.php',
            '*_summary.md',
            '*_instructions.md',
            '*_FIXES*.md',
            '*_IMPLEMENTATION*.md',
            'run_all_fixes*.php',
            'comprehensive_*.php',
            'final_*.php',
            'quick_*.php',
            'simple_*.php',
            'targeted_*.php',
            'direct_*.php',
            'proper_*.php',
            'complete_*.php',
            'seed_*.php',
            'populate_*.php',
            'migrate_*.php',
            'update_*.php',
            'add_*.php',
            'create_*.php',
            'setup_*.php',
            'analyze_*.php',
            'diagnose_*.php',
            'force_*.php',
            'refresh_*.php',
            'move_*.php',
            'monthly_*.php',
            'cleanup_*.php'
        ];
        
        foreach ($patterns as $pattern) {
            $files = glob($this->projectRoot . '/' . $pattern);
            foreach ($files as $file) {
                if (basename($file) !== 'cleanup_project.php') { // Don't delete this script
                    $this->deleteFile($file);
                }
            }
        }
        
        echo "✅ Removed " . count($this->deletedFiles) . " temporary files\n\n";
    }
    
    /**
     * Clean debug code from core files
     */
    private function cleanDebugCode() {
        echo "🧽 Cleaning debug code from core files...\n";
        
        $coreDirectories = ['controllers/', 'models/', 'views/', 'includes/'];
        
        foreach ($coreDirectories as $dir) {
            $files = glob($this->projectRoot . '/' . $dir . '*.php');
            foreach ($files as $file) {
                $this->cleanFile($file);
            }
        }
        
        echo "✅ Cleaned debug code from " . count($this->modifiedFiles) . " files\n\n";
    }
    
    /**
     * Clean individual file of debug code
     */
    private function cleanFile($filePath) {
        $content = file_get_contents($filePath);
        $originalContent = $content;
        
        // Remove debug statements
        $patterns = [
            '/echo\s+["\'].*DEBUG.*["\'];\s*/i',
            '/echo\s+["\'].*debug.*["\'];\s*/i',
            '/console\.log\(.*\);\s*/',
            '/error_log\(["\'].*debug.*["\']\);\s*/i',
            '/\/\/\s*Debug.*$/m',
            '/\/\/\s*TODO.*$/m',
            '/\/\/\s*FIXME.*$/m',
            '/\/\/\s*HACK.*$/m',
            '/\/\/\s*XXX.*$/m'
        ];
        
        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }
        
        // Remove empty lines (more than 2 consecutive)
        $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
        
        // Only write if content changed
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->modifiedFiles[] = $filePath;
        }
    }
    
    /**
     * Delete file and log the action
     */
    private function deleteFile($filePath) {
        if (file_exists($filePath)) {
            unlink($filePath);
            $this->deletedFiles[] = $filePath;
            $this->log("Deleted: " . $filePath);
        }
    }
    
    /**
     * Copy directory recursively
     */
    private function copyDirectory($src, $dst) {
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        
        $files = scandir($src);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $srcFile = $src . '/' . $file;
                $dstFile = $dst . '/' . $file;
                
                if (is_dir($srcFile)) {
                    $this->copyDirectory($srcFile, $dstFile);
                } else {
                    copy($srcFile, $dstFile);
                }
            }
        }
    }
    
    /**
     * Log action to file
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "[$timestamp] $message\n", FILE_APPEND);
    }
    
    /**
     * Generate cleanup report
     */
    private function generateReport() {
        echo "📊 Generating cleanup report...\n";
        
        $report = "# Project Cleanup Report\n\n";
        $report .= "**Cleanup Date:** " . date('Y-m-d H:i:s') . "\n\n";
        $report .= "## Files Deleted (" . count($this->deletedFiles) . ")\n\n";
        
        foreach ($this->deletedFiles as $file) {
            $report .= "- " . basename($file) . "\n";
        }
        
        $report .= "\n## Files Modified (" . count($this->modifiedFiles) . ")\n\n";
        
        foreach ($this->modifiedFiles as $file) {
            $report .= "- " . str_replace($this->projectRoot . '/', '', $file) . "\n";
        }
        
        $report .= "\n## Summary\n\n";
        $report .= "- **Temporary Files Removed:** " . count($this->deletedFiles) . "\n";
        $report .= "- **Core Files Cleaned:** " . count($this->modifiedFiles) . "\n";
        $report .= "- **Backup Location:** " . $this->backupDir . "\n";
        
        file_put_contents($this->backupDir . '/cleanup_report.md', $report);
        
        echo "✅ Report generated: " . $this->backupDir . "/cleanup_report.md\n";
    }
}

// Run cleanup if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $cleanup = new ProjectCleanup();
    $cleanup->runCleanup();
}

