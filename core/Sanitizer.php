<?php
/**
 * Classe Sanitizer - Nettoyage des données
 */

class Sanitizer
{
    public static function string(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }
    
    public static function email(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return filter_var(trim($value), FILTER_SANITIZE_EMAIL);
    }
    
    public static function int($value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }
    
    public static function float($value): float
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    public static function url(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return filter_var(trim($value), FILTER_SANITIZE_URL);
    }
    
    public static function slug(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        
        $value = mb_strtolower($value, 'UTF-8');
        
        $accents = [
            'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a',
            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e',
            'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i',
            'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o',
            'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u',
            'ç'=>'c', 'ñ'=>'n'
        ];
        $value = strtr($value, $accents);
        
        $value = preg_replace('/\s+/', '-', $value);
        $value = preg_replace('/[^a-z0-9\-]/', '', $value);
        $value = preg_replace('/-+/', '-', $value);
        
        return trim($value, '-');
    }
    
    public static function phone(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return preg_replace('/[^0-9\+\-\(\)\s]/', '', $value);
    }
    
    public static function array(array $data, array $rules = []): array
    {
        $cleaned = [];
        
        foreach ($data as $key => $value) {
            $type = $rules[$key] ?? 'string';
            
            switch ($type) {
                case 'int':
                    $cleaned[$key] = self::int($value);
                    break;
                case 'float':
                    $cleaned[$key] = self::float($value);
                    break;
                case 'email':
                    $cleaned[$key] = self::email($value);
                    break;
                case 'url':
                    $cleaned[$key] = self::url($value);
                    break;
                case 'slug':
                    $cleaned[$key] = self::slug($value);
                    break;
                case 'phone':
                    $cleaned[$key] = self::phone($value);
                    break;
                case 'raw':
                    $cleaned[$key] = $value;
                    break;
                default:
                    $cleaned[$key] = is_string($value) ? self::string($value) : $value;
            }
        }
        
        return $cleaned;
    }
    
    public static function post(array $rules = []): array
    {
        return self::array($_POST, $rules);
    }
    
    public static function get(array $rules = []): array
    {
        return self::array($_GET, $rules);
    }
}