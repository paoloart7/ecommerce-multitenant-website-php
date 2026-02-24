<?php
/**
 * Classe Validator - Validation des données
 */

class Validator
{
    private array $errors = [];
    private array $data = [];
    
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    
    public function required(string $field, string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if (empty(trim($value))) {
            $this->errors[$field] = $message ?? "Le champ $field est requis";
        }
        
        return $this;
    }
    
    public function email(string $field, string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?? "L'email n'est pas valide";
        }
        
        return $this;
    }
    
    public function min(string $field, int $length, string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && strlen($value) < $length) {
            $this->errors[$field] = $message ?? "Le champ $field doit contenir au moins $length caractères";
        }
        
        return $this;
    }
    
    public function max(string $field, int $length, string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && strlen($value) > $length) {
            $this->errors[$field] = $message ?? "Le champ $field ne doit pas dépasser $length caractères";
        }
        
        return $this;
    }
    
    public function match(string $field1, string $field2, string $message = null): self
    {
        $value1 = $this->data[$field1] ?? '';
        $value2 = $this->data[$field2] ?? '';
        
        if ($value1 !== $value2) {
            $this->errors[$field2] = $message ?? "Les champs ne correspondent pas";
        }
        
        return $this;
    }
    
    public function numeric(string $field, string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field] = $message ?? "Le champ $field doit être un nombre";
        }
        
        return $this;
    }
    
    public function positiveInt(string $field, string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && (!is_numeric($value) || (int)$value < 0)) {
            $this->errors[$field] = $message ?? "Le champ $field doit être un entier positif";
        }
        
        return $this;
    }
    
    public function strongPassword(string $field, string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value)) {
            $errors = Security::isStrongPassword($value);
            if (!empty($errors)) {
                $this->errors[$field] = $message ?? implode(', ', $errors);
            }
        }
        
        return $this;
    }
    
    public function phone(string $field, string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && !preg_match('/^[\+]?[0-9\s\-\(\)]{8,20}$/', $value)) {
            $this->errors[$field] = $message ?? "Le numéro de téléphone n'est pas valide";
        }
        
        return $this;
    }
    
    public function slug(string $field, string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && !preg_match('/^[a-z0-9\-]+$/', $value)) {
            $this->errors[$field] = $message ?? "Le slug ne peut contenir que des lettres minuscules, chiffres et tirets";
        }
        
        return $this;
    }
    
    public function isValid(): bool
    {
        return empty($this->errors);
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    public function getFirstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
    
    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }
}