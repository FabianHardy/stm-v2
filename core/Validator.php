<?php
/**
 * Classe Validator
 * 
 * Validation des données utilisateur.
 * 
 * Fonctionnalités :
 * - Validation de champs (required, email, min, max, etc.)
 * - Messages d'erreur en français
 * - Règles personnalisables
 * - Validation de fichiers
 * 
 * @package STM
 * @version 2.0
 */

namespace Core;

class Validator
{
    /**
     * Données à valider
     * 
     * @var array
     */
    protected array $data = [];
    
    /**
     * Erreurs de validation
     * 
     * @var array
     */
    protected array $errors = [];
    
    /**
     * Constructeur
     * 
     * @param array $data Données à valider
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
    
    /**
     * Vérifie qu'un champ est requis (non vide)
     * 
     * @param string $field Nom du champ
     * @param string|null $message Message d'erreur personnalisé
     * @return self
     */
    public function required(string $field, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;
        
        if (empty($value) && $value !== '0') {
            $this->errors[$field][] = $message ?? "Le champ $field est requis.";
        }
        
        return $this;
    }
    
    /**
     * Vérifie qu'un champ est un email valide
     * 
     * @param string $field Nom du champ
     * @param string|null $message Message d'erreur personnalisé
     * @return self
     */
    public function email(string $field, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;
        
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = $message ?? "Le champ $field doit être une adresse email valide.";
        }
        
        return $this;
    }
    
    /**
     * Vérifie la longueur minimale d'un champ
     * 
     * @param string $field Nom du champ
     * @param int $length Longueur minimale
     * @param string|null $message Message d'erreur personnalisé
     * @return self
     */
    public function min(string $field, int $length, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;
        
        if (!empty($value) && strlen($value) < $length) {
            $this->errors[$field][] = $message ?? "Le champ $field doit contenir au moins $length caractères.";
        }
        
        return $this;
    }
    
    /**
     * Vérifie la longueur maximale d'un champ
     * 
     * @param string $field Nom du champ
     * @param int $length Longueur maximale
     * @param string|null $message Message d'erreur personnalisé
     * @return self
     */
    public function max(string $field, int $length, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;
        
        if (!empty($value) && strlen($value) > $length) {
            $this->errors[$field][] = $message ?? "Le champ $field ne peut pas dépasser $length caractères.";
        }
        
        return $this;
    }
    
    /**
     * Vérifie qu'un champ est numérique
     * 
     * @param string $field Nom du champ
     * @param string|null $message Message d'erreur personnalisé
     * @return self
     */
    public function numeric(string $field, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;
        
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field][] = $message ?? "Le champ $field doit être numérique.";
        }
        
        return $this;
    }
    
    /**
     * Vérifie qu'un champ est un entier
     * 
     * @param string $field Nom du champ
     * @param string|null $message Message d'erreur personnalisé
     * @return self
     */
    public function integer(string $field, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;
        
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$field][] = $message ?? "Le champ $field doit être un entier.";
        }
        
        return $this;
    }
    
    /**
     * Vérifie qu'un champ est dans une liste de valeurs
     * 
     * @param string $field Nom du champ
     * @param array $values Liste de valeurs autorisées
     * @param string|null $message Message d'erreur personnalisé
     * @return self
     */
    public function in(string $field, array $values, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;
        
        if (!empty($value) && !in_array($value, $values, true)) {
            $this->errors[$field][] = $message ?? "Le champ $field doit être l'une des valeurs suivantes : " . implode(', ', $values) . ".";
        }
        
        return $this;
    }
    
    /**
     * Vérifie qu'un champ correspond à un autre (confirmation)
     * 
     * @param string $field Nom du champ
     * @param string $matchField Nom du champ à comparer
     * @param string|null $message Message d'erreur personnalisé
     * @return self
     */
    public function matches(string $field, string $matchField, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;
        $matchValue = $this->data[$matchField] ?? null;
        
        if ($value !== $matchValue) {
            $this->errors[$field][] = $message ?? "Le champ $field doit correspondre au champ $matchField.";
        }
        
        return $this;
    }
    
    /**
     * Vérifie qu'un champ est une URL valide
     * 
     * @param string $field Nom du champ
     * @param string|null $message Message d'erreur personnalisé
     * @return self
     */
    public function url(string $field, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;
        
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field][] = $message ?? "Le champ $field doit être une URL valide.";
        }
        
        return $this;
    }
    
    /**
     * Vérifie qu'un champ est une date valide
     * 
     * @param string $field Nom du champ
     * @param string $format Format de date (ex: 'Y-m-d')
     * @param string|null $message Message d'erreur personnalisé
     * @return self
     */
    public function date(string $field, string $format = 'Y-m-d', ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;
        
        if (!empty($value)) {
            $date = \DateTime::createFromFormat($format, $value);
            
            if (!$date || $date->format($format) !== $value) {
                $this->errors[$field][] = $message ?? "Le champ $field doit être une date valide au format $format.";
            }
        }
        
        return $this;
    }
    
    /**
     * Vérifie qu'un champ respecte une expression régulière
     * 
     * @param string $field Nom du champ
     * @param string $pattern Expression régulière
     * @param string|null $message Message d'erreur personnalisé
     * @return self
     */
    public function regex(string $field, string $pattern, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;
        
        if (!empty($value) && !preg_match($pattern, $value)) {
            $this->errors[$field][] = $message ?? "Le champ $field ne respecte pas le format attendu.";
        }
        
        return $this;
    }
    
    /**
     * Vérifie qu'un champ contient un mot de passe fort
     * 
     * @param string $field Nom du champ
     * @param int $minLength Longueur minimale (défaut: 8)
     * @param string|null $message Message d'erreur personnalisé
     * @return self
     */
    public function strongPassword(string $field, int $minLength = 8, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;
        
        if (!empty($value)) {
            // Vérifier la longueur
            if (strlen($value) < $minLength) {
                $this->errors[$field][] = $message ?? "Le mot de passe doit contenir au moins $minLength caractères.";
                return $this;
            }
            
            // Vérifier la présence de majuscules, minuscules et chiffres
            $hasLower = preg_match('/[a-z]/', $value);
            $hasUpper = preg_match('/[A-Z]/', $value);
            $hasNumber = preg_match('/[0-9]/', $value);
            
            if (!$hasLower || !$hasUpper || !$hasNumber) {
                $this->errors[$field][] = $message ?? "Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre.";
            }
        }
        
        return $this;
    }
    
    /**
     * Vérifie qu'une valeur est unique en base de données
     * 
     * @param string $field Nom du champ
     * @param string $table Nom de la table
     * @param string $column Nom de la colonne (par défaut: même que $field)
     * @param int|null $excludeId ID à exclure (pour updates)
     * @param string|null $message Message d'erreur personnalisé
     * @return self
     */
    public function unique(string $field, string $table, ?string $column = null, ?int $excludeId = null, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;
        $column = $column ?? $field;
        
        if (!empty($value)) {
            $db = Database::getInstance()->getConnection();
            
            $query = "SELECT COUNT(*) as count FROM $table WHERE $column = ?";
            $params = [$value];
            
            if ($excludeId !== null) {
                $query .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                $this->errors[$field][] = $message ?? "Cette valeur pour $field existe déjà.";
            }
        }
        
        return $this;
    }
    
    /**
     * Vérifie qu'un fichier a été uploadé
     * 
     * @param string $field Nom du champ fichier
     * @param string|null $message Message d'erreur personnalisé
     * @return self
     */
    public function file(string $field, ?string $message = null): self
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            $this->errors[$field][] = $message ?? "Le fichier $field est requis.";
        }
        
        return $this;
    }
    
    /**
     * Vérifie la taille maximale d'un fichier uploadé
     * 
     * @param string $field Nom du champ fichier
     * @param int $maxSize Taille maximale en Ko
     * @param string|null $message Message d'erreur personnalisé
     * @return self
     */
    public function fileSize(string $field, int $maxSize, ?string $message = null): self
    {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $fileSize = $_FILES[$field]['size'] / 1024; // Convertir en Ko
            
            if ($fileSize > $maxSize) {
                $this->errors[$field][] = $message ?? "Le fichier $field ne peut pas dépasser {$maxSize}Ko.";
            }
        }
        
        return $this;
    }
    
    /**
     * Vérifie le type MIME d'un fichier uploadé
     * 
     * @param string $field Nom du champ fichier
     * @param array $mimeTypes Types MIME autorisés
     * @param string|null $message Message d'erreur personnalisé
     * @return self
     */
    public function fileMimeType(string $field, array $mimeTypes, ?string $message = null): self
    {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $fileMime = mime_content_type($_FILES[$field]['tmp_name']);
            
            if (!in_array($fileMime, $mimeTypes, true)) {
                $this->errors[$field][] = $message ?? "Le fichier $field doit être de type : " . implode(', ', $mimeTypes) . ".";
            }
        }
        
        return $this;
    }
    
    /**
     * Vérifie si la validation a réussi
     * 
     * @return bool
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }
    
    /**
     * Vérifie si la validation a échoué
     * 
     * @return bool
     */
    public function fails(): bool
    {
        return !$this->passes();
    }
    
    /**
     * Récupère toutes les erreurs
     * 
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }
    
    /**
     * Récupère la première erreur pour un champ
     * 
     * @param string $field Nom du champ
     * @return string|null
     */
    public function getError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
    
    /**
     * Ajoute une erreur manuellement
     * 
     * @param string $field Nom du champ
     * @param string $message Message d'erreur
     * @return self
     */
    public function addError(string $field, string $message): self
    {
        $this->errors[$field][] = $message;
        return $this;
    }

public function uniqueComposite(array $fields, string $table, ?int $excludeId = null, ?string $message = null): self
{
    // Récupérer les valeurs depuis $this->data
    $values = [];
    $whereClauses = [];
    $params = [];
    
    foreach ($fields as $fieldName => $columnName) {
        $value = $this->data[$fieldName] ?? null;
        
        if (empty($value)) {
            // Si une des valeurs est vide, on ne peut pas vérifier l'unicité
            return $this;
        }
        
        $values[$fieldName] = $value;
        $whereClauses[] = "$columnName = ?";
        $params[] = $value;
    }
    
    // Construire la requête SQL
    $db = Database::getInstance()->getConnection();
    $query = "SELECT COUNT(*) as count FROM $table WHERE " . implode(' AND ', $whereClauses);
    
    // Exclure l'ID actuel lors d'un update
    if ($excludeId !== null) {
        $query .= " AND id != ?";
        $params[] = $excludeId;
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    // Si un doublon existe
    if ($result['count'] > 0) {
        // Déterminer quel champ afficher dans l'erreur (le premier par défaut)
        $firstField = array_key_first($fields);
        
        $this->errors[$firstField][] = $message ?? "Cette combinaison de valeurs existe déjà.";
    }
    
    return $this;
}
}
