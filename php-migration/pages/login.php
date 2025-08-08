<?php
/**
 * Page de connexion et inscription
 * Correspond à client/src/pages/login.tsx
 */

// Rediriger si déjà connecté
if (Auth::check()) {
    Router::redirect('/dashboard');
}

$pageTitle = "Connexion - StacGateLMS";
$pageDescription = "Connectez-vous à votre compte StacGateLMS pour accéder à vos cours et formations.";

// Obtenir les établissements pour le sélecteur
$establishmentService = new EstablishmentService();
$establishments = $establishmentService->getAllEstablishments();

// Gestion des erreurs
$errors = [];
$successMessage = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'login') {
                // Connexion
                $validator = Validator::make($_POST, [
                    'email' => 'required|email',
                    'password' => 'required',
                    'establishment_id' => 'required|integer'
                ]);
                
                if ($validator->validate()) {
                    $authService = new AuthService();
                    $user = $authService->authenticate(
                        $_POST['email'],
                        $_POST['password'],
                        $_POST['establishment_id']
                    );
                    
                    if ($user) {
                        Router::redirect('/dashboard');
                    } else {
                        $errors[] = "Email ou mot de passe incorrect";
                    }
                } else {
                    $errors = array_merge($errors, array_values($validator->getErrors()));
                }
                
            } elseif ($_POST['action'] === 'register') {
                // Inscription
                $validator = Validator::make($_POST, [
                    'first_name' => 'required|max:100',
                    'last_name' => 'required|max:100',
                    'email' => 'required|email',
                    'password' => 'required|min:8',
                    'password_confirm' => 'required',
                    'establishment_id' => 'required|integer'
                ]);
                
                if ($validator->validate()) {
                    if ($_POST['password'] !== $_POST['password_confirm']) {
                        $errors[] = "Les mots de passe ne correspondent pas";
                    } else {
                        $authService = new AuthService();
                        
                        // Vérifier si l'email existe déjà
                        $existingUser = $authService->getUserByEmail($_POST['email'], $_POST['establishment_id']);
                        if ($existingUser) {
                            $errors[] = "Un compte avec cet email existe déjà";
                        } else {
                            try {
                                $user = $authService->createUser([
                                    'first_name' => $_POST['first_name'],
                                    'last_name' => $_POST['last_name'],
                                    'email' => $_POST['email'],
                                    'password' => $_POST['password'],
                                    'establishment_id' => $_POST['establishment_id'],
                                    'role' => 'apprenant'
                                ]);
                                
                                $successMessage = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
                            } catch (Exception $e) {
                                $errors[] = "Erreur lors de la création du compte : " . $e->getMessage();
                            }
                        }
                    }
                } else {
                    $errors = array_merge($errors, array_values($validator->getErrors()));
                }
            }
        } catch (Exception $e) {
            $errors[] = "Une erreur est survenue : " . $e->getMessage();
        }
    }
}

require_once ROOT_PATH . '/includes/header.php';
?>

<div style="min-height: calc(100vh - 200px); display: flex; align-items: center; justify-content: center; padding: 2rem;">
    <div class="glassmorphism" style="max-width: 500px; width: 100%; padding: 2.5rem;">
        <!-- Logo et titre -->
        <div class="text-center mb-8">
            <div class="nav-logo" style="font-size: 2rem; margin-bottom: 1rem;">
                StacGateLMS
            </div>
            <h1 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem;">
                Accès à votre espace d'apprentissage
            </h1>
            <p style="opacity: 0.8;">
                Connectez-vous ou créez votre compte pour commencer
            </p>
        </div>
        
        <!-- Messages -->
        <?php if (!empty($errors)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: var(--border-radius); padding: 1rem; margin-bottom: 1.5rem;">
                <?php foreach ($errors as $error): ?>
                    <div style="color: #ef4444; font-size: 0.875rem;">• <?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($successMessage): ?>
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: var(--border-radius); padding: 1rem; margin-bottom: 1.5rem;">
                <div style="color: #10b981; font-size: 0.875rem;"><?= htmlspecialchars($successMessage) ?></div>
            </div>
        <?php endif; ?>
        
        <!-- Onglets -->
        <div style="display: flex; border-bottom: 1px solid var(--glass-border); margin-bottom: 2rem;">
            <button class="tab-button active" onclick="switchTab('login')" style="flex: 1; padding: 1rem; background: none; border: none; color: inherit; cursor: pointer; border-bottom: 2px solid rgb(var(--color-primary));">
                Connexion
            </button>
            <button class="tab-button" onclick="switchTab('register')" style="flex: 1; padding: 1rem; background: none; border: none; color: inherit; cursor: pointer; border-bottom: 2px solid transparent;">
                Inscription
            </button>
        </div>
        
        <!-- Formulaire de connexion -->
        <div id="loginForm" class="tab-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                
                <div class="form-group">
                    <label class="form-label">Établissement</label>
                    <select name="establishment_id" class="glass-input" style="width: 100%;" required>
                        <option value="">Sélectionnez votre établissement</option>
                        <?php foreach ($establishments as $establishment): ?>
                            <option value="<?= $establishment['id'] ?>" <?= (isset($_POST['establishment_id']) && $_POST['establishment_id'] == $establishment['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($establishment['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="glass-input" style="width: 100%;" 
                           placeholder="votre.email@exemple.com" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mot de passe</label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="loginPassword" class="glass-input" style="width: 100%; padding-right: 3rem;" 
                               placeholder="Votre mot de passe" required>
                        <button type="button" onclick="togglePassword('loginPassword')" 
                                style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: inherit; cursor: pointer;">
                            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="remember" style="accent-color: rgb(var(--color-primary));">
                        <span style="font-size: 0.875rem;">Se souvenir de moi</span>
                    </label>
                    
                    <a href="#" style="font-size: 0.875rem; color: rgb(var(--color-primary)); text-decoration: none;">
                        Mot de passe oublié ?
                    </a>
                </div>
                
                <button type="submit" class="glass-button" style="width: 100%; padding: 1rem;">
                    Se connecter
                </button>
            </form>
        </div>
        
        <!-- Formulaire d'inscription -->
        <div id="registerForm" class="tab-content" style="display: none;">
            <form method="POST" action="">
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                
                <div class="form-group">
                    <label class="form-label">Établissement</label>
                    <select name="establishment_id" class="glass-input" style="width: 100%;" required>
                        <option value="">Sélectionnez votre établissement</option>
                        <?php foreach ($establishments as $establishment): ?>
                            <option value="<?= $establishment['id'] ?>">
                                <?= htmlspecialchars($establishment['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Prénom</label>
                        <input type="text" name="first_name" class="glass-input" style="width: 100%;" 
                               placeholder="Votre prénom" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nom</label>
                        <input type="text" name="last_name" class="glass-input" style="width: 100%;" 
                               placeholder="Votre nom" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="glass-input" style="width: 100%;" 
                           placeholder="votre.email@exemple.com" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mot de passe</label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="registerPassword" class="glass-input" style="width: 100%; padding-right: 3rem;" 
                               placeholder="Minimum 8 caractères" required minlength="8">
                        <button type="button" onclick="togglePassword('registerPassword')" 
                                style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: inherit; cursor: pointer;">
                            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirmer le mot de passe</label>
                    <input type="password" name="password_confirm" class="glass-input" style="width: 100%;" 
                           placeholder="Répétez votre mot de passe" required>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" required style="margin-top: 0.25rem; accent-color: rgb(var(--color-primary));">
                        <span style="font-size: 0.875rem; line-height: 1.4;">
                            J'accepte les <a href="#" style="color: rgb(var(--color-primary)); text-decoration: none;">conditions d'utilisation</a> 
                            et la <a href="#" style="color: rgb(var(--color-primary)); text-decoration: none;">politique de confidentialité</a>
                        </span>
                    </label>
                </div>
                
                <button type="submit" class="glass-button" style="width: 100%; padding: 1rem;">
                    Créer mon compte
                </button>
            </form>
        </div>
        
        <!-- Lien vers l'accueil -->
        <div class="text-center mt-6">
            <a href="/" style="color: rgba(var(--color-text), 0.7); text-decoration: none; font-size: 0.875rem;">
                ← Retour à l'accueil
            </a>
        </div>
    </div>
</div>

<script>
// Gestion des onglets
function switchTab(tabName) {
    // Cacher tous les contenus
    document.querySelectorAll('.tab-content').forEach(content => {
        content.style.display = 'none';
    });
    
    // Retirer la classe active de tous les boutons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
        button.style.borderBottomColor = 'transparent';
    });
    
    // Afficher le contenu sélectionné
    document.getElementById(tabName + 'Form').style.display = 'block';
    
    // Ajouter la classe active au bouton sélectionné
    event.target.classList.add('active');
    event.target.style.borderBottomColor = 'rgb(var(--color-primary))';
}

// Toggle visibilité mot de passe
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
    input.setAttribute('type', type);
}

// Validation du formulaire d'inscription côté client
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.querySelector('#registerForm form');
    const passwordInput = registerForm.querySelector('input[name="password"]');
    const confirmInput = registerForm.querySelector('input[name="password_confirm"]');
    
    confirmInput.addEventListener('input', function() {
        if (passwordInput.value !== confirmInput.value) {
            confirmInput.setCustomValidity('Les mots de passe ne correspondent pas');
        } else {
            confirmInput.setCustomValidity('');
        }
    });
    
    passwordInput.addEventListener('input', function() {
        if (confirmInput.value && passwordInput.value !== confirmInput.value) {
            confirmInput.setCustomValidity('Les mots de passe ne correspondent pas');
        } else {
            confirmInput.setCustomValidity('');
        }
    });
});
</script>

<style>
@media (max-width: 768px) {
    .glassmorphism {
        margin: 1rem;
        padding: 1.5rem !important;
    }
    
    #registerForm .form-group:first-child + div {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>