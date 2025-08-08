<?php
/**
 * Validateur de données
 * Validation côté serveur avec règles flexibles
 */

class Validator {
    private $data;
    private $rules;
    private $errors = [];
    private $messages = [];
    
    public function __construct($data = []) {
        $this->data = $data;
        $this->initDefaultMessages();
    }
    
    private function initDefaultMessages() {
        $this->messages = [
            'required' => 'Le champ {field} est requis',
            'email' => 'Le champ {field} doit être une adresse email valide',
            'min' => 'Le champ {field} doit contenir au moins {param} caractères',
            'max' => 'Le champ {field} ne peut pas dépasser {param} caractères',
            'numeric' => 'Le champ {field} doit être numérique',
            'integer' => 'Le champ {field} doit être un entier',
            'url' => 'Le champ {field} doit être une URL valide',
            'match' => 'Le champ {field} ne correspond pas au champ {param}',
            'unique' => 'Cette valeur pour {field} existe déjà',
            'exists' => 'Cette valeur pour {field} n\'existe pas',
            'in' => 'Le champ {field} doit être une des valeurs: {param}',
            'password' => 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre'
        ];
    }
    
    public function validate($rules) {
        $this->rules = $rules;
        $this->errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $this->validateField($field, $fieldRules);
        }
        
        return empty($this->errors);
    }
    
    private function validateField($field, $rules) {
        $rulesArray = is_string($rules) ? explode('|', $rules) : $rules;
        $value = $this->data[$field] ?? null;
        
        foreach ($rulesArray as $rule) {
            if (is_string($rule)) {
                $this->applyRule($field, $rule, $value);
            }
        }
    }
    
    private function applyRule($field, $rule, $value) {
        $params = [];
        
        // Extraire les paramètres de la règle (ex: min:8)
        if (strpos($rule, ':') !== false) {
            list($rule, $paramStr) = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
        }
        
        $methodName = 'validate' . ucfirst($rule);
        
        if (method_exists($this, $methodName)) {
            $result = call_user_func([$this, $methodName], $field, $value, $params);
            
            if ($result !== true) {
                $this->addError($field, $rule, $params, $result);
            }
        }
    }
    
    private function addError($field, $rule, $params = [], $customMessage = null) {
        if ($customMessage && is_string($customMessage)) {
            $message = $customMessage;
        } else {
            $message = $this->messages[$rule] ?? 'Validation failed for {field}';
        }
        
        $message = str_replace('{field}', $field, $message);
        
        if (!empty($params)) {
            $message = str_replace('{param}', implode(', ', $params), $message);
        }
        
        $this->errors[$field][] = $message;
    }
    
    // Règles de validation
    
    protected function validateRequired($field, $value, $params) {
        return !empty($value) || $value === '0' || $value === 0;
    }
    
    protected function validateEmail($field, $value, $params) {
        if (empty($value)) return true; // Skip if empty (use required rule)
        return Utils::validateEmail($value);
    }
    
    protected function validateMin($field, $value, $params) {
        if (empty($value)) return true;
        $min = $params[0] ?? 0;
        return strlen($value) >= $min;
    }
    
    protected function validateMax($field, $value, $params) {
        if (empty($value)) return true;
        $max = $params[0] ?? 0;
        return strlen($value) <= $max;
    }
    
    protected function validateNumeric($field, $value, $params) {
        if (empty($value)) return true;
        return is_numeric($value);
    }
    
    protected function validateInteger($field, $value, $params) {
        if (empty($value)) return true;
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    protected function validateUrl($field, $value, $params) {
        if (empty($value)) return true;
        return Utils::isValidUrl($value);
    }
    
    protected function validateMatch($field, $value, $params) {
        if (empty($value)) return true;
        $matchField = $params[0] ?? '';
        $matchValue = $this->data[$matchField] ?? '';
        return $value === $matchValue;
    }
    
    protected function validatePassword($field, $value, $params) {
        if (empty($value)) return true;
        return Utils::validatePassword($value);
    }
    
    protected function validateUnique($field, $value, $params) {
        if (empty($value)) return true;
        
        $table = $params[0] ?? '';
        $column = $params[1] ?? $field;
        $exceptId = $params[2] ?? null;
        
        if (empty($table)) return true;
        
        $db = Database::getInstance();
        $whereCondition = "$column = ?";
        $whereParams = [$value];
        
        if ($exceptId) {
            $whereCondition .= " AND id != ?";
            $whereParams[] = $exceptId;
        }
        
        $existing = $db->selectOne($table, 'id', $whereCondition, $whereParams);
        return !$existing;
    }
    
    protected function validateExists($field, $value, $params) {
        if (empty($value)) return true;
        
        $table = $params[0] ?? '';
        $column = $params[1] ?? 'id';
        
        if (empty($table)) return true;
        
        $db = Database::getInstance();
        $existing = $db->selectOne($table, 'id', "$column = ?", [$value]);
        return !!$existing;
    }
    
    protected function validateIn($field, $value, $params) {
        if (empty($value)) return true;
        return in_array($value, $params);
    }
    
    // Getters
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function getFirstError($field = null) {
        if ($field) {
            return $this->errors[$field][0] ?? null;
        }
        
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }
        
        return null;
    }
    
    public function hasError($field = null) {
        if ($field) {
            return isset($this->errors[$field]);
        }
        
        return !empty($this->errors);
    }
    
    public function getValidatedData() {
        $validated = [];
        
        foreach (array_keys($this->rules) as $field) {
            if (isset($this->data[$field])) {
                $validated[$field] = $this->data[$field];
            }
        }
        
        return $validated;
    }
    
    // Méthodes statiques pour validation rapide
    
    public static function make($data, $rules) {
        $validator = new self($data);
        $validator->validate($rules);
        return $validator;
    }
    
    public static function quick($data, $rules) {
        $validator = self::make($data, $rules);
        
        if ($validator->hasError()) {
            throw new Exception($validator->getFirstError());
        }
        
        return $validator->getValidatedData();
    }
    
    // Règles communes pré-définies
    
    public static function userRegistrationRules() {
        return [
            'first_name' => 'required|min:2|max:100',
            'last_name' => 'required|min:2|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|password',
            'establishment_id' => 'required|integer|exists:establishments,id'
        ];
    }
    
    public static function userUpdateRules($userId = null) {
        $emailRule = 'required|email';
        if ($userId) {
            $emailRule .= "|unique:users,email,$userId";
        }
        
        return [
            'first_name' => 'required|min:2|max:100',
            'last_name' => 'required|min:2|max:100',
            'email' => $emailRule
        ];
    }
    
    public static function passwordChangeRules() {
        return [
            'current_password' => 'required',
            'new_password' => 'required|password',
            'confirm_password' => 'required|match:new_password'
        ];
    }
    
    public static function establishmentRules() {
        return [
            'name' => 'required|min:3|max:255',
            'slug' => 'required|min:3|max:100|unique:establishments,slug',
            'description' => 'max:1000',
            'contact_email' => 'required|email',
            'category' => 'required|in:universite,ecole,formation,entreprise,autre'
        ];
    }
    
    public static function courseRules() {
        return [
            'title' => 'required|min:5|max:255',
            'description' => 'required|min:10',
            'category' => 'required|max:100',
            'level' => 'required|in:debutant,intermediaire,avance',
            'duration' => 'required|integer|min:1',
            'establishment_id' => 'required|integer|exists:establishments,id'
        ];
    }
}
?>