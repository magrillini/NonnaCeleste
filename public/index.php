<?php
require_once __DIR__ . '/../src/bootstrap.php';

$action = query('action', 'home');
$user = current_user();
$flash = consume_flash();

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([post('email')]);
    $candidate = $stmt->fetch();
    if ($candidate && password_verify((string) post('password'), $candidate['password'])) {
        $_SESSION['user_id'] = $candidate['id'];
        flash('success', 'Accesso effettuato con successo.');
    } else {
        flash('error', 'Credenziali non valide.');
    }
    redirect('/');
}

if ($action === 'logout') {
    session_destroy();
    header('Location: /');
    exit;
}

if ($action === 'save_recipe' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$user) {
        flash('error', 'Devi accedere per inserire una ricetta.');
        redirect('/?action=submit');
    }

    $cookId = (int) post('cook_id', 0);
    $cook = null;
    if ($cookId > 0) {
        $cookStmt = $db->prepare('SELECT * FROM cooks WHERE id = ?');
        $cookStmt->execute([$cookId]);
        $cook = $cookStmt->fetch();
    }

    if (!$cook) {
        flash('error', 'Seleziona un cuoco presente nell\'elenco approvato dall\'admin.');
        redirect('/?action=submit');
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare('INSERT INTO recipes(title,cook_name,cook_id,author_user_id,visibility_type,holiday,meal_time,course_type,execution_method,is_traditional,approved_by_admin,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $visibility = post('visibility_type', 'familiare');
        $stmt->execute([
            post('title'),
            $cook['full_name'],
            $cook['id'],
            $user['id'],
            $visibility,
            post('holiday') ?: null,
            post('meal_time'),
            post('course_type') ?: null,
            post('execution_method'),
            $visibility === 'tradizionale' ? 1 : 0,
            is_admin() ? 1 : 0,
            date(DATE_ATOM),
            date(DATE_ATOM),
        ]);
        $recipeId = (int) $db->lastInsertId();

        foreach ((array) ($_POST['ingredients'] ?? []) as $index => $ingredientId) {
            if (!$ingredientId) {
                continue;
            }
            $quantityUnit = $_POST['ingredient_units'][$index] ?? 'gr';
            $quantityValue = $quantityUnit === 'qb' ? null : (float) ($_POST['ingredient_quantities'][$index] ?? 0);
            $db->prepare('INSERT INTO recipe_ingredients(recipe_id,ingredient_id,quantity_value,quantity_unit) VALUES(?,?,?,?)')
                ->execute([$recipeId, (int) $ingredientId, $quantityValue, $quantityUnit]);
        }

        foreach (array_unique(array_map('intval', (array) ($_POST['utensils'] ?? []))) as $utensilId) {
            if ($utensilId <= 0) {
                continue;
            }
            $db->prepare('INSERT INTO recipe_utensils(recipe_id,utensil_id) VALUES(?,?)')->execute([$recipeId, $utensilId]);
        }

        foreach ((array) ($_POST['cooking_methods'] ?? []) as $index => $methodId) {
            if (!$methodId) {
                continue;
            }
            $minutes = (int) ($_POST['cooking_minutes'][$index] ?? 0);
            $db->prepare('INSERT INTO recipe_cooking_methods(recipe_id,cooking_method_id,minutes) VALUES(?,?,?)')->execute([$recipeId, (int) $methodId, $minutes]);
        }

        save_uploaded_images($_FILES['gallery'] ?? [], $recipeId, (int) $user['id']);
        $db->commit();
        flash('success', 'Ricetta salvata correttamente.');
        redirect('/?action=recipe&id=' . $recipeId);
    } catch (Throwable $e) {
        $db->rollBack();
        flash('error', 'Errore nel salvataggio: ' . $e->getMessage());
        redirect('/?action=submit');
    }
}

if ($action === 'save_comment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$user) {
        flash('error', 'Devi accedere per commentare.');
        redirect('/?action=recipe&id=' . (int) post('recipe_id'));
    }
    $recipeId = (int) post('recipe_id');
    $commentId = (int) post('comment_id', 0);
    if ($commentId > 0) {
        $comment = $db->prepare('SELECT * FROM comments WHERE id = ?');
        $comment->execute([$commentId]);
        $existing = $comment->fetch();
        if ($existing && (int) $existing['user_id'] === (int) $user['id']) {
            $db->prepare('UPDATE comments SET body = ?, updated_at = ? WHERE id = ?')->execute([post('body'), date(DATE_ATOM), $commentId]);
        }
    } else {
        $db->prepare('INSERT INTO comments(recipe_id,user_id,body,created_at,updated_at) VALUES(?,?,?,?,?)')
            ->execute([$recipeId, $user['id'], post('body'), date(DATE_ATOM), date(DATE_ATOM)]);
    }
    flash('success', 'Commento salvato.');
    redirect('/?action=recipe&id=' . $recipeId);
}

if ($action === 'save_contact_request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestType = post('request_type', 'general');
    $db->prepare('INSERT INTO contact_requests(name,email,phone,request_type,message,recipe_reference,cook_full_name,cook_birth_date,cook_phone,cook_email,cook_parent_names,status,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([
            trim((string) post('name')),
            trim((string) post('email')),
            trim((string) post('phone')),
            $requestType,
            trim((string) post('message')),
            trim((string) post('recipe_reference')) ?: null,
            trim((string) post('cook_full_name')) ?: null,
            trim((string) post('cook_birth_date')) ?: null,
            trim((string) post('cook_phone')) ?: null,
            trim((string) post('cook_email')) ?: null,
            trim((string) post('cook_parent_names')) ?: null,
            'nuova',
            date(DATE_ATOM),
        ]);
    flash('success', $requestType === 'cook' ? 'Richiesta per nuovo cuoco inviata all\'admin.' : 'Richiesta inviata correttamente.');
    redirect('/?action=contacts');
}

if ($action === 'admin_add_catalog' && $_SERVER['REQUEST_METHOD'] === 'POST' && is_admin()) {
    $table = post('catalog_type') === 'utensil' ? 'utensils' : 'ingredients';
    $name = trim((string) post('name'));
    if ($name !== '') {
        $db->prepare("INSERT OR IGNORE INTO {$table}(name,created_by_role) VALUES(?,?)")->execute([$name, current_user()['role']]);
        flash('success', 'Elemento catalogo aggiunto.');
    }
    redirect('/?action=admin');
}

if ($action === 'admin_add_cook' && $_SERVER['REQUEST_METHOD'] === 'POST' && is_admin()) {
    $fullName = trim((string) post('full_name'));
    if ($fullName === '') {
        flash('error', 'Il nome del cuoco è obbligatorio.');
        redirect('/?action=admin');
    }

    $db->prepare('INSERT INTO cooks(full_name,birth_date,phone,email,parent_names,notes,created_by_user_id,created_at) VALUES(?,?,?,?,?,?,?,?)')
        ->execute([
            $fullName,
            trim((string) post('birth_date')) ?: null,
            trim((string) post('phone')) ?: null,
            trim((string) post('email')) ?: null,
            trim((string) post('parent_names')) ?: null,
            trim((string) post('notes')) ?: null,
            (int) $user['id'],
            date(DATE_ATOM),
        ]);

    $contactRequestId = (int) post('contact_request_id', 0);
    if ($contactRequestId > 0) {
        $db->prepare("UPDATE contact_requests SET status = 'gestita' WHERE id = ?")->execute([$contactRequestId]);
    }

    flash('success', 'Cuoco aggiunto all\'elenco.');
    redirect('/?action=admin');
}

if ($action === 'admin_update_home_hero' && $_SERVER['REQUEST_METHOD'] === 'POST' && is_admin()) {
    try {
        $didUpload = save_home_hero_image($_FILES['home_hero_image'] ?? [], (int) $user['id']);
        if ($didUpload) {
            flash('success', 'Foto Home aggiornata correttamente.');
        } else {
            flash('error', 'Seleziona un\'immagine da caricare.');
        }
    } catch (Throwable $e) {
        flash('error', 'Errore upload Home: ' . $e->getMessage());
    }

    redirect('/?action=admin');
}

if ($action === 'admin_reset_home_hero' && $_SERVER['REQUEST_METHOD'] === 'POST' && is_admin()) {
    reset_home_hero_image();
    flash('success', 'Foto Home ripristinata all\'immagine predefinita.');
    redirect('/?action=admin');
}

$ingredients = fetch_all($db, 'SELECT * FROM ingredients ORDER BY name');
$utensils = fetch_all($db, 'SELECT * FROM utensils ORDER BY name');
$cookingMethods = fetch_all($db, 'SELECT * FROM cooking_methods ORDER BY name');
$cooks = fetch_all($db, 'SELECT * FROM cooks ORDER BY full_name');
$contactRequests = is_admin() ? fetch_all($db, 'SELECT * FROM contact_requests ORDER BY created_at DESC') : [];
$cookRequests = is_admin() ? fetch_all($db, "SELECT * FROM contact_requests WHERE request_type = 'cook' ORDER BY created_at DESC") : [];

$recipesSql = 'SELECT recipes.*, users.name AS author_name FROM recipes JOIN users ON users.id = recipes.author_user_id WHERE 1=1';
$params = [];
if ($action === 'traditional') {
    $recipesSql .= " AND recipes.visibility_type IN ('tradizionale','variante')";
}
if ($action === 'family') {
    $recipesSql .= " AND recipes.visibility_type = 'familiare'";
    if ($holiday = query('holiday')) {
        $recipesSql .= ' AND recipes.holiday = ?';
        $params[] = $holiday;
    }
    if ($meal = query('meal_time')) {
        $recipesSql .= ' AND recipes.meal_time = ?';
        $params[] = $meal;
    }
}
$recipesSql .= ' ORDER BY recipes.created_at DESC';
$recipes = fetch_all($db, $recipesSql, $params);

$recipe = null;
$recipeIngredients = $recipeUtensils = $recipeMethods = $comments = $gallery = [];
if ($action === 'recipe' && ($recipeId = (int) query('id'))) {
    $stmt = $db->prepare('SELECT recipes.*, users.name AS author_name FROM recipes JOIN users ON users.id = recipes.author_user_id WHERE recipes.id = ?');
    $stmt->execute([$recipeId]);
    $recipe = $stmt->fetch();
    if ($recipe) {
        $recipeIngredients = fetch_all($db, 'SELECT ingredients.name, recipe_ingredients.quantity_value, recipe_ingredients.quantity_unit FROM recipe_ingredients JOIN ingredients ON ingredients.id = recipe_ingredients.ingredient_id WHERE recipe_ingredients.recipe_id = ?', [$recipeId]);
        $recipeUtensils = fetch_all($db, 'SELECT utensils.name FROM recipe_utensils JOIN utensils ON utensils.id = recipe_utensils.utensil_id WHERE recipe_utensils.recipe_id = ?', [$recipeId]);
        $recipeMethods = fetch_all($db, 'SELECT cooking_methods.name, recipe_cooking_methods.minutes FROM recipe_cooking_methods JOIN cooking_methods ON cooking_methods.id = recipe_cooking_methods.cooking_method_id WHERE recipe_cooking_methods.recipe_id = ?', [$recipeId]);
        $comments = fetch_all($db, 'SELECT comments.*, users.name FROM comments JOIN users ON users.id = comments.user_id WHERE comments.recipe_id = ? ORDER BY comments.created_at DESC', [$recipeId]);
        $gallery = fetch_all($db, 'SELECT * FROM recipe_images WHERE recipe_id = ? ORDER BY created_at DESC', [$recipeId]);
    }
}

$galleryRecipes = fetch_all($db, 'SELECT recipes.id, recipes.title, recipes.cook_name, MIN(recipe_images.path) AS image_path FROM recipes LEFT JOIN recipe_images ON recipe_images.recipe_id = recipes.id GROUP BY recipes.id ORDER BY recipes.created_at DESC');
$homeHeroImage = home_hero_image_path();

include __DIR__ . '/views/layout.php';
