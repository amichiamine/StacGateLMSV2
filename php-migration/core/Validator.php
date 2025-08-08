<?php
/**
 * Classe Validator - Validation des données entrantes
 */

class Validator {
    private $data;
    private $rules;
    private $errors = [];
    private $messages = [
        'required' => 'Le champ :field est obligatoire',
        'email' => 'Le champ :field doit être une adresse email valide',
        'min' => 'Le champ :field doit contenir au moins :min caractères',
        'max' => 'Le champ :field ne peut pas dépasser :max caractères',
        'numeric' => 'Le champ :field doit être numérique',
        'integer' => 'Le champ :field doit être un entier',
        'in' => 'Le champ :field doit être l\'une des valeurs suivantes: :values',
        'unique' => 'Cette valeur pour :field existe déjà',
        'confirmed' => 'Le champ :field ne correspond pas à sa confirmation',
        'url' => 'Le champ :field doit être une URL valide',
        'alpha' => 'Le champ :field ne peut contenir que des lettres',
        'alpha_num' => 'Le champ :field ne peut contenir que des lettres et des chiffres',
        'regex' => 'Le format du champ :field est invalide',
        'date' => 'Le champ :field doit être une date valide',
        'boolean' => 'Le champ :field doit être vrai ou faux',
        'json' => 'Le champ :field doit être du JSON valide'
    ];
    
    public function __construct($data, $rules) {
        $this->data = $data;
        $this->rules = $rules;
    }
    
    /**
     * Valider les données
     */
    public function validate() {
        foreach ($this->rules as $field => $ruleSet) {
            $rules = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            
            foreach ($rules as $rule) {
                $this->applyRule($field, $rule);
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Appliquer une règle de validation
     */
    private function applyRule($field, $rule) {
        $parameters = [];
        
        if (strpos($rule, ':') !== false) {
            [$rule, $paramString] = explode(':', $rule, 2);
            $parameters = explode(',', $paramString);
        }
        
        $value = $this->getValue($field);
        
        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, 'required');
                }
                break;
                
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'email');
                }
                break;
                
            case 'min':
                if (!empty($value) && strlen($value) < $parameters[0]) {
                    $this->addError($field, 'min', ['min' => $parameters[0]]);
                }
                break;
                
            case 'max':
                if (!empty($value) && strlen($value) > $parameters[0]) {
                    $this->addError($field, 'max', ['max' => $parameters[0]]);
                }
                break;
                
            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, 'numeric');
                }
                break;
                
            case 'integer':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, 'integer');
                }
                break;
                
            case 'in':
                if (!empty($value) && !in_array($value, $parameters)) {
                    $this->addError($field, 'in', ['values' => implode(', ', $parameters)]);
                }
                break;
                
            case 'unique':
                if (!empty($value)) {
                    $table = $parameters[0];
                    $column = $parameters[1] ?? $field;
                    $except = $parameters[2] ?? null;
                    
                    $db = Database::getInstance();
                    $where = "{$column} = :value";
                    $params = ['value' => $value];
                    
                    if ($except) {
                        $where .= " AND id != :except";
                        $params['except'] = $except;
                    }
                    
                    if ($db->exists($table, $where, $params)) {
                        $this->addError($field, 'unique');
                    }
                }
                break;
                
            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if ($value !== $this->getValue($confirmField)) {
                    $this->addError($field, 'confirmed');
                }
                break;
                
            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, 'url');
                }
                break;
                
            case 'alpha':
                if (!empty($value) && !preg_match('/^[a-zA-ZÀ-ÿ\s]+$/', $value)) {
                    $this->addError($field, 'alpha');
                }
                break;
                
            case 'alpha_num':
                if (!empty($value) && !preg_match('/^[a-zA-Z0-9À-ÿ\s]+$/', $value)) {
                    $this->addError($field, 'alpha_num');
                }
                break;
                
            case 'regex':
                if (!empty($value) && !preg_match($parameters[0], $value)) {
                    $this->addError($field, 'regex');
                }
                break;
                
            case 'date':
                if (!empty($value) && !strtotime($value)) {
                    $this->addError($field, 'date');
                }
                break;
                
            case 'boolean':
                if (!empty($value) && !is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'])) {
                    $this->addError($field, 'boolean');
                }
                break;
                
            case 'json':
                if (!empty($value)) {
                    json_decode($value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $this->addError($field, 'json');
                    }
                }
                break;
        }
    }
    
    /**
     * Obtenir la valeur d'un champ
     */
    private function getValue($field) {
        return $this->data[$field] ?? null;
    }
    
    /**
     * Ajouter une erreur
     */
    private function addError($field, $rule, $parameters = []) {
        $message = $this->messages[$rule] ?? 'Le champ :field est invalide';
        
        // Remplacer les placeholders
        $message = str_replace(':field', $field, $message);
        foreach ($parameters as $key => $value) {
            $message = str_replace(":{$key}", $value, $message);
        }
        
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }
    
    /**
     * Obtenir les erreurs de validation
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Obtenir la première erreur d'un champ
     */
    public function getError($field) {
        return $this->errors[$field][0] ?? null;
    }
    
    /**
     * Vérifier si un champ a des erreurs
     */
    public function hasError($field) {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }
    
    /**
     * Obtenir les données validées
     */
    public function getValidatedData() {
        $validated = [];
        foreach ($this->rules as $field => $rules) {
            if (isset($this->data[$field])) {
                $validated[$field] = $this->data[$field];
            }
        }
        return $validated;
    }
    
    /**
     * Validation rapide statique
     */
    public static function make($data, $rules) {
        $validator = new self($data, $rules);
        return $validator;
    }
    
    /**
     * Validation rapide avec exception
     */
    public static function validateOrFail($data, $rules) {
        $validator = self::make($data, $rules);
        
        if (!$validator->validate()) {
            throw new ValidationException($validator->getErrors());
        }
        
        return $validator->getValidatedData();
    }
    
    /**
     * Définir des messages personnalisés
     */
    public function setMessages($messages) {
        $this->messages = array_merge($this->messages, $messages);
        return $this;
    }
    
    /**
     * Ajouter une règle de validation personnalisée
     */
    public function addRule($name, $callback, $message = null) {
        // Pour simplicité, on peut étendre cette classe pour des règles personnalisées
        if ($message) {
            $this->messages[$name] = $message;
        }
        
        return $this;
    }
}

/**
 * Exception de validation
 */
class ValidationException extends Exception {
    private $errors;
    
    public function __construct($errors) {
        $this->errors = $errors;
        $message = "Validation failed: " . json_encode($errors);
        parent::__construct($message);
    }
    
    public function getErrors() {
        return $this->errors;
    }
}
?>