<?php
$holidays = ['Natale','Capodanno','Epifania','Carnevale','Pasqua','Pasquetta','Ferragosto','Ognissanti','Immacolata','Festa patronale'];
$mealTimes = ['colazione','pranzo','merenda','cena'];
$courseTypes = ['antipasto','primo','secondo','contorno','dolce'];
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nonna Celeste</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="site-header">
    <div>
        <h1>Nonna Celeste</h1>
        <p>Archivio di ricette tradizionali italiane, varianti e ricette familiari.</p>
    </div>
    <nav>
        <a href="/">Home</a>
        <a href="/?action=traditional">Ricette tradizionali</a>
        <a href="/?action=family">Ricette familiari</a>
        <a href="/?action=submit">Inserisci ricetta</a>
        <a href="/?action=gallery">Galleria</a>
        <a href="/?action=contacts">Contatti</a>
        <?php if (is_admin()): ?><a href="/?action=admin">Admin</a><?php endif; ?>
    </nav>
    <div class="auth-box">
        <?php if ($user): ?>
            <strong><?= e($user['name']) ?></strong> <span class="role"><?= e($user['role']) ?></span>
            <a href="/?action=logout">Esci</a>
        <?php else: ?>
            <form method="post" action="/?action=login" class="login-form">
                <input type="email" name="email" placeholder="email">
                <input type="password" name="password" placeholder="password">
                <button type="submit">Accedi</button>
            </form>
        <?php endif; ?>
    </div>
</header>

<main class="container">
    <?php if ($flash): ?>
        <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?php if ($action === 'home'): ?>
        <section class="hero">
            <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80" alt="Nonna Celeste in cucina">
            <div>
                <h2>La cucina di Nonna Celeste</h2>
                <p>Una casa digitale per custodire ricette tradizionali, ricette familiari, varianti regionali e i racconti di chi le cucina.</p>
                <div class="grid-buttons">
                    <a class="card-button" href="/?action=traditional">Ricetta tradizionale</a>
                    <a class="card-button" href="/?action=family">Ricette familiari</a>
                    <a class="card-button" href="/?action=submit">Inserimento ricetta</a>
                </div>
            </div>
        </section>
    <?php elseif (in_array($action, ['traditional','family'], true)): ?>
        <section>
            <h2><?= $action === 'traditional' ? 'Ricette tradizionali e varianti' : 'Ricette familiari' ?></h2>
            <?php if ($action === 'family'): ?>
                <form method="get" class="filters">
                    <input type="hidden" name="action" value="family">
                    <select name="holiday">
                        <option value="">Tutte le festività</option>
                        <?php foreach ($holidays as $holiday): ?><option value="<?= e($holiday) ?>" <?= query('holiday') === $holiday ? 'selected' : '' ?>><?= e($holiday) ?></option><?php endforeach; ?>
                    </select>
                    <select name="meal_time">
                        <option value="">Tutti i momenti</option>
                        <?php foreach ($mealTimes as $meal): ?><option value="<?= e($meal) ?>" <?= query('meal_time') === $meal ? 'selected' : '' ?>><?= e(ucfirst($meal)) ?></option><?php endforeach; ?>
                    </select>
                    <button type="submit">Filtra</button>
                </form>
            <?php endif; ?>
            <div class="recipe-grid">
                <?php foreach ($recipes as $item): ?>
                    <article class="recipe-card">
                        <span class="tag"><?= e($item['visibility_type']) ?></span>
                        <h3><a href="/?action=recipe&id=<?= (int) $item['id'] ?>"><?= e($item['title']) ?></a></h3>
                        <p><strong>Cuoco:</strong> <?= e($item['cook_name']) ?></p>
                        <p><strong>Festività:</strong> <?= e($item['holiday'] ?: 'Nessuna') ?></p>
                        <p><strong>Momento:</strong> <?= e($item['meal_time']) ?><?= $item['course_type'] ? ' / ' . e($item['course_type']) : '' ?></p>
                        <p><strong>Autore inserimento:</strong> <?= e($item['author_name']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php elseif ($action === 'submit'): ?>
        <section>
            <h2>Inserimento ricetta</h2>
            <p>Gli utenti possono inserire nuove ricette e varianti; l'amministratore può pubblicare anche ricette tradizionali e aggiornare le festività in calendario.</p>
            <form method="post" action="/?action=save_recipe" enctype="multipart/form-data" class="stack-form">
                <label>Nome ricetta <input type="text" name="title" required></label>
                <label>Cuoco <input type="text" name="cook_name" required value="<?= $user ? e($user['name']) : '' ?>"></label>
                <label>Tipologia
                    <select name="visibility_type">
                        <option value="familiare">Ricetta familiare</option>
                        <option value="variante">Variante tradizionale</option>
                        <?php if (is_admin()): ?><option value="tradizionale">Ricetta tradizionale</option><?php endif; ?>
                    </select>
                </label>
                <label>Festività tipica
                    <select name="holiday">
                        <option value="">Nessuna</option>
                        <?php foreach ($holidays as $holiday): ?><option value="<?= e($holiday) ?>"><?= e($holiday) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label>Momento della giornata
                    <select name="meal_time" required>
                        <?php foreach ($mealTimes as $meal): ?><option value="<?= e($meal) ?>"><?= e(ucfirst($meal)) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label>Portata pranzo/cena
                    <select name="course_type">
                        <option value="">Non applicabile</option>
                        <?php foreach ($courseTypes as $course): ?><option value="<?= e($course) ?>"><?= e(ucfirst($course)) ?></option><?php endforeach; ?>
                    </select>
                </label>

                <fieldset>
                    <legend>Ingredienti</legend>
                    <?php for ($i = 0; $i < 5; $i++): ?>
                        <div class="inline-grid">
                            <select name="ingredients[]">
                                <option value="">Seleziona ingrediente</option>
                                <?php foreach ($ingredients as $ingredient): ?><option value="<?= (int) $ingredient['id'] ?>"><?= e($ingredient['name']) ?></option><?php endforeach; ?>
                            </select>
                            <input type="number" step="0.1" min="0" name="ingredient_quantities[]" placeholder="Quantità">
                            <select name="ingredient_units[]">
                                <option value="gr">gr</option>
                                <option value="cl">cl</option>
                                <option value="qb">qb</option>
                            </select>
                        </div>
                    <?php endfor; ?>
                </fieldset>

                <fieldset>
                    <legend>Utensili</legend>
                    <select name="utensils[]" multiple size="8">
                        <?php foreach ($utensils as $utensil): ?><option value="<?= (int) $utensil['id'] ?>"><?= e($utensil['name']) ?></option><?php endforeach; ?>
                    </select>
                </fieldset>

                <fieldset>
                    <legend>Modalità di cottura</legend>
                    <?php for ($i = 0; $i < 3; $i++): ?>
                        <div class="inline-grid">
                            <select name="cooking_methods[]">
                                <option value="">Modalità</option>
                                <?php foreach ($cookingMethods as $method): ?><option value="<?= (int) $method['id'] ?>"><?= e($method['name']) ?></option><?php endforeach; ?>
                            </select>
                            <input type="number" min="0" name="cooking_minutes[]" placeholder="Minuti">
                        </div>
                    <?php endfor; ?>
                </fieldset>

                <label>Modalità di esecuzione descrittiva
                    <textarea name="execution_method" rows="7" required></textarea>
                </label>
                <label>Galleria foto
                    <input type="file" name="gallery[]" multiple accept="image/*">
                </label>
                <button type="submit">Salva ricetta</button>
            </form>
        </section>
    <?php elseif ($action === 'recipe' && $recipe): ?>
        <article class="recipe-detail printable">
            <div class="print-actions">
                <button onclick="window.print()">Stampa in PDF</button>
                <?php if (!empty($recipe['holiday'])): ?><a href="<?= e(google_calendar_link($recipe)) ?>" target="_blank" rel="noreferrer">Aggiungi a Google Calendar</a><?php endif; ?>
            </div>
            <h2><?= e($recipe['title']) ?></h2>
            <p><strong>Cuoco:</strong> <?= e($recipe['cook_name']) ?></p>
            <p><strong>Tipologia:</strong> <?= e($recipe['visibility_type']) ?></p>
            <p><strong>Festività:</strong> <?= e($recipe['holiday'] ?: 'Nessuna') ?></p>
            <p><strong>Momento della giornata:</strong> <?= e($recipe['meal_time']) ?><?= $recipe['course_type'] ? ' / ' . e($recipe['course_type']) : '' ?></p>
            <p><strong>Inserita da:</strong> <?= e($recipe['author_name']) ?></p>

            <h3>Ingredienti</h3>
            <ul>
                <?php foreach ($recipeIngredients as $item): ?>
                    <li><?= e($item['name']) ?> - <?= $item['quantity_unit'] === 'qb' ? 'qb' : e((string) $item['quantity_value'] . ' ' . $item['quantity_unit']) ?></li>
                <?php endforeach; ?>
            </ul>

            <h3>Utensili</h3>
            <ul>
                <?php foreach ($recipeUtensils as $item): ?><li><?= e($item['name']) ?></li><?php endforeach; ?>
            </ul>

            <h3>Cottura</h3>
            <ul>
                <?php foreach ($recipeMethods as $item): ?><li><?= e($item['name']) ?> - <?= (int) $item['minutes'] ?> minuti</li><?php endforeach; ?>
            </ul>

            <h3>Esecuzione</h3>
            <p><?= nl2br(e($recipe['execution_method'])) ?></p>

            <h3>Galleria</h3>
            <div class="gallery-grid">
                <?php foreach ($gallery as $image): ?><img src="/<?= e($image['path']) ?>" alt="Foto ricetta <?= e($recipe['title']) ?>"><?php endforeach; ?>
            </div>

            <h3>Commenti</h3>
            <?php foreach ($comments as $comment): ?>
                <div class="comment-box">
                    <strong><?= e($comment['name']) ?></strong>
                    <p><?= nl2br(e($comment['body'])) ?></p>
                    <?php if ($user && (int) $comment['user_id'] === (int) $user['id']): ?>
                        <form method="post" action="/?action=save_comment" class="stack-form small-form">
                            <input type="hidden" name="recipe_id" value="<?= (int) $recipe['id'] ?>">
                            <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">
                            <textarea name="body" rows="3"><?= e($comment['body']) ?></textarea>
                            <button type="submit">Modifica commento</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if ($user): ?>
                <form method="post" action="/?action=save_comment" class="stack-form small-form">
                    <input type="hidden" name="recipe_id" value="<?= (int) $recipe['id'] ?>">
                    <textarea name="body" rows="4" placeholder="Lascia un commento visibile a tutti"></textarea>
                    <button type="submit">Pubblica commento</button>
                </form>
            <?php endif; ?>
        </article>
    <?php elseif ($action === 'gallery'): ?>
        <section>
            <h2>Galleria ricette</h2>
            <div class="recipe-grid">
                <?php foreach ($galleryRecipes as $item): ?>
                    <article class="recipe-card">
                        <?php if (!empty($item['image_path'])): ?><img src="/<?= e($item['image_path']) ?>" alt="<?= e($item['title']) ?>"><?php endif; ?>
                        <h3><?= e($item['title']) ?></h3>
                        <p><strong>Cuoco:</strong> <?= e($item['cook_name']) ?></p>
                        <a href="/?action=recipe&id=<?= (int) $item['id'] ?>">Vai alla ricetta</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php elseif ($action === 'contacts'): ?>
        <section>
            <h2>Contatti e richieste</h2>
            <p>Le richieste di cancellazione vengono inviate via mail e gestite dall'amministratore.</p>
            <form class="stack-form" action="mailto:admin@nonnaceleste.local" method="post" enctype="text/plain">
                <label>Nome <input type="text" name="nome" required></label>
                <label>Email <input type="email" name="email" required></label>
                <label>ID o nome ricetta da cancellare <input type="text" name="ricetta" required></label>
                <label>Motivazione dettagliata <textarea name="motivazione" rows="6" required></textarea></label>
                <button type="submit">Invia richiesta via mail</button>
            </form>
        </section>
    <?php elseif ($action === 'admin' && is_admin()): ?>
        <section>
            <h2>Pannello amministrazione</h2>
            <div class="admin-grid">
                <form method="post" action="/?action=admin_add_catalog" class="stack-form">
                    <input type="hidden" name="catalog_type" value="ingredient">
                    <h3>Aggiungi ingrediente mancante</h3>
                    <input type="text" name="name" placeholder="Nuovo ingrediente">
                    <button type="submit">Aggiungi ingrediente</button>
                </form>
                <form method="post" action="/?action=admin_add_catalog" class="stack-form">
                    <input type="hidden" name="catalog_type" value="utensil">
                    <h3>Aggiungi utensile mancante</h3>
                    <input type="text" name="name" placeholder="Nuovo utensile">
                    <button type="submit">Aggiungi utensile</button>
                </form>
            </div>
            <p>Solo admin e superadmin possono estendere i cataloghi. Il superadmin rimane il proprietario del sistema.</p>
        </section>
    <?php else: ?>
        <section><h2>Pagina non trovata</h2></section>
    <?php endif; ?>
</main>
</body>
</html>
