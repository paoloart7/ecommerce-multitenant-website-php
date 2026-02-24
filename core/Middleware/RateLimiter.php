<?php
/**
 * Middleware Rate Limiter
 */

class RateLimiter
{
    private string $storageFile;
    private int $maxAttempts;
    private int $decayMinutes;
    
    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/security.php';
        
        $this->maxAttempts = $config['rate_limit']['max_attempts'];
        $this->decayMinutes = $config['rate_limit']['decay_minutes'];
        $this->storageFile = dirname(__DIR__, 2) . '/storage/logs/rate_limit.json';
    }
    
    public function handle(): void
    {
        $config = require dirname(__DIR__, 2) . '/config/security.php';
        
        if (!$config['rate_limit']['enabled']) {
            return;
        }
        
        $ip = Security::getClientIP();
        $key = $this->getKey($ip);
        
        if ($this->tooManyAttempts($key)) {
            http_response_code(429);
            $minutes = $this->decayMinutes;
            echo json_encode([
                'error' => "Trop de tentatives. Réessayez dans $minutes minutes."
            ]);
            exit;
        }
    }
    
    private function getKey(string $ip): string
    {
        return md5($ip . $_SERVER['REQUEST_URI']);
    }
    
    public function tooManyAttempts(string $key): bool
    {
        $attempts = $this->getAttempts($key);
        return $attempts >= $this->maxAttempts;
    }
    
    private function getAttempts(string $key): int
    {
        $data = $this->loadData();
        
        if (!isset($data[$key])) {
            return 0;
        }
        
        if (time() > $data[$key]['expires']) {
            unset($data[$key]);
            $this->saveData($data);
            return 0;
        }
        
        return $data[$key]['attempts'];
    }
    
    public function hit(string $key = null): void
    {
        if (!$key) {
            $key = $this->getKey(Security::getClientIP());
        }
        
        $data = $this->loadData();
        
        if (!isset($data[$key]) || time() > $data[$key]['expires']) {
            $data[$key] = [
                'attempts' => 1,
                'expires' => time() + ($this->decayMinutes * 60)
            ];
        } else {
            $data[$key]['attempts']++;
        }
        
        $this->saveData($data);
    }
    
    public function clear(string $key = null): void
    {
        if (!$key) {
            $key = $this->getKey(Security::getClientIP());
        }
        
        $data = $this->loadData();
        unset($data[$key]);
        $this->saveData($data);
    }
    
    private function loadData(): array
    {
        if (!file_exists($this->storageFile)) {
            return [];
        }
        
        $content = file_get_contents($this->storageFile);
        return json_decode($content, true) ?: [];
    }
    
    private function saveData(array $data): void
    {
        $dir = dirname($this->storageFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($this->storageFile, json_encode($data));
    }
}