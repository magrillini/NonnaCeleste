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
        <?php include __DIR__ . '/partials/home-hero.php'; ?>
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
            <p>Gli ingredienti si possono aggiungere uno alla volta, gli utensili restano visibili solo se selezionati e il cuoco deve essere scelto dall'elenco approvato dall'admin.</p>
            <form method="post" action="/?action=save_recipe" enctype="multipart/form-data" class="stack-form">
                <label>Nome ricetta <input type="text" name="title" required></label>
                <label>Cuoco
                    <select name="cook_id" required>
                        <option value="">Seleziona cuoco approvato</option>
                        <?php foreach ($cooks as $cook): ?>
                            <option value="<?= (int) $cook['id'] ?>"><?= e($cook['full_name']) ?><?= $cook['birth_date'] ? ' - ' . e($cook['birth_date']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
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

                <fieldset class="dynamic-ingredients" data-next-index="1">
                    <legend>Ingredienti</legend>
                    <div class="ingredient-list">
                        <div class="ingredient-row inline-grid" data-index="0">
                            <div class="ingredient-picker">
                                <input type="text" class="ingredient-search" placeholder="Cerca ingrediente (minimo 3 caratteri)" autocomplete="off">
                                <select name="ingredients[]" class="ingredient-select">
                                    <option value="">Seleziona ingrediente</option>
                                    <?php foreach ($ingredients as $ingredient): ?><option value="<?= (int) $ingredient['id'] ?>"><?= e($ingredient['name']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <input type="number" step="0.1" min="0" name="ingredient_quantities[]" placeholder="Quantità">
                            <select name="ingredient_units[]">
                                <option value="gr">gr</option>
                                <option value="cl">cl</option>
                                <option value="qb">qb</option>
                            </select>
                        </div>
                    </div>
                    <button type="button" class="secondary-button add-ingredient-button">Aggiungi ingrediente</button>
                    <template id="ingredient-row-template">
                        <div class="ingredient-row inline-grid" data-index="__INDEX__">
                            <div class="ingredient-picker">
                                <input type="text" class="ingredient-search" placeholder="Cerca ingrediente (minimo 3 caratteri)" autocomplete="off">
                                <select name="ingredients[]" class="ingredient-select">
                                    <option value="">Seleziona ingrediente</option>
                                    <?php foreach ($ingredients as $ingredient): ?><option value="<?= (int) $ingredient['id'] ?>"><?= e($ingredient['name']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <input type="number" step="0.1" min="0" name="ingredient_quantities[]" placeholder="Quantità">
                            <select name="ingredient_units[]">
                                <option value="gr">gr</option>
                                <option value="cl">cl</option>
                                <option value="qb">qb</option>
                            </select>
                        </div>
                    </template>
                </fieldset>

                <fieldset>
                    <legend>Utensili</legend>
                    <div class="selection-layout">
                        <div class="selection-column">
                            <label for="utensil-search">Ricerca utensili</label>
                            <input type="text" id="utensil-search" class="filter-input" placeholder="Scrivi per filtrare gli utensili">
                            <div class="utensil-options">
                                <?php foreach ($utensils as $utensil): ?>
                                    <label class="checkbox-chip utensil-option" data-name="<?= e(mb_strtolower($utensil['name'])) ?>">
                                        <input type="checkbox" name="utensils[]" value="<?= (int) $utensil['id'] ?>">
                                        <span><?= e($utensil['name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="selection-column selected-column">
                            <h3>Utensili selezionati</h3>
                            <div class="selected-utensils empty">Nessun utensile selezionato.</div>
                        </div>
                    </div>
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
                <?php foreach ($gallery as $image): ?><img src="<?= e(media_url($image['path'])) ?>" alt="Foto ricetta <?= e($recipe['title']) ?>"><?php endforeach; ?>
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
                        <?php if (!empty($item['image_path'])): ?><img src="<?= e(media_url($item['image_path'])) ?>" alt="<?= e($item['title']) ?>"><?php endif; ?>
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
            <p>Da questa pagina si possono inviare richieste generali, cancellazioni ricette e richieste per inserire un nuovo cuoco nell'elenco gestito dall'admin.</p>
            <div class="admin-grid">
                <form class="stack-form" action="/?action=save_contact_request" method="post">
                    <input type="hidden" name="request_type" value="deletion">
                    <h3>Richiesta cancellazione ricetta</h3>
                    <label>Nome <input type="text" name="name" required></label>
                    <label>Email <input type="email" name="email" required></label>
                    <label>Telefono <input type="text" name="phone"></label>
                    <label>ID o nome ricetta da cancellare <input type="text" name="recipe_reference" required></label>
                    <label>Motivazione dettagliata <textarea name="message" rows="6" required></textarea></label>
                    <button type="submit">Invia richiesta</button>
                </form>
                <form class="stack-form" action="/?action=save_contact_request" method="post">
                    <input type="hidden" name="request_type" value="cook">
                    <h3>Richiesta inserimento cuoco</h3>
                    <label>Richiedente <input type="text" name="name" required></label>
                    <label>Email richiedente <input type="email" name="email" required></label>
                    <label>Telefono richiedente <input type="text" name="phone"></label>
                    <label>Nome e cognome cuoco <input type="text" name="cook_full_name" required></label>
                    <label>Data di nascita <input type="date" name="cook_birth_date"></label>
                    <label>Telefono cuoco <input type="text" name="cook_phone"></label>
                    <label>Mail cuoco <input type="email" name="cook_email"></label>
                    <label>Figlio/a di... Babbo e Mamma <input type="text" name="cook_parent_names" placeholder="Es. figlia di Mario Rossi e Anna Verdi"></label>
                    <label>Dettagli richiesta <textarea name="message" rows="5" required></textarea></label>
                    <button type="submit">Invia richiesta all'admin</button>
                </form>
            </div>
        </section>
    <?php elseif ($action === 'admin' && is_admin()): ?>
        <section>
            <h2>Pannello amministrazione</h2>
            <div class="admin-grid">
                <form method="post" action="/?action=admin_save_home_media" enctype="multipart/form-data" class="stack-form">
                    <h3>Slider e grafica Home</h3>
                    <p class="helper-text">Carica una o più foto insieme e scegli la grafica della Home. Le immagini scorrono automaticamente con dissolvenza.</p>
                    <label>Grafica Home
                        <select name="home_theme">
                            <?php foreach ($homeThemeOptions as $themeKey => $themeLabel): ?>
                                <option value="<?= e($themeKey) ?>" <?= $homeHeroTheme === $themeKey ? 'selected' : '' ?>><?= e($themeLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Nuove foto Home
                        <input type="file" name="home_slides[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
                    </label>
                    <p class="helper-text">Puoi selezionare più file nello stesso caricamento. Se non scegli nuovi file, viene salvata solo la grafica.</p>
                    <button type="submit">Carica e pubblica</button>
                </form>
                <form method="post" action="/?action=admin_reset_home_hero" class="stack-form">
                    <h3>Ripristina slider predefinito</h3>
                    <p class="helper-text">Elimina tutte le foto caricate e torna all'immagine standard della Home.</p>
                    <button type="submit" class="secondary-button">Ripristina foto default</button>
                </form>
            </div>
            <div class="stack-form">
                <h3>Foto attuali della Home</h3>
                <p class="helper-text">Queste immagini vengono usate nello slider automatico della Home. Puoi cancellarle singolarmente.</p>
                <div class="home-slide-admin-grid">
                    <?php foreach ($homeHeroSlides as $slide): ?>
                        <article class="recipe-card home-slide-admin-card">
                            <img src="<?= e(media_url($slide['path'])) ?>" alt="<?= e($slide['caption'] ?: 'Foto Home') ?>" class="admin-preview">
                            <p><strong><?= e($slide['caption'] ?: 'Foto Home') ?></strong></p>
                            <?php if (!empty($slide['id'])): ?>
                                <form method="post" action="/?action=admin_delete_home_slide" class="stack-form small-form">
                                    <input type="hidden" name="slide_id" value="<?= (int) $slide['id'] ?>">
                                    <button type="submit" class="secondary-button">Elimina foto</button>
                                </form>
                            <?php else: ?>
                                <p class="helper-text">Immagine predefinita di fallback.</p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
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
                <form method="post" action="/?action=admin_add_cook" class="stack-form">
                    <h3>Aggiungi cuoco approvato</h3>
                    <label>Nome e cognome <input type="text" name="full_name" required></label>
                    <label>Data di nascita <input type="date" name="birth_date"></label>
                    <label>Telefono <input type="text" name="phone"></label>
                    <label>Mail <input type="email" name="email"></label>
                    <label>Figlio/a di... <input type="text" name="parent_names"></label>
                    <label>Note admin <textarea name="notes" rows="4"></textarea></label>
                    <button type="submit">Salva cuoco</button>
                </form>
            </div>
            <div class="admin-grid">
                <article class="recipe-card">
                    <h3>Cuochi approvati</h3>
                    <ul class="plain-list">
                        <?php foreach ($cooks as $cook): ?>
                            <li><strong><?= e($cook['full_name']) ?></strong><br><small><?= e(trim(($cook['birth_date'] ?: '') . ' ' . ($cook['parent_names'] ? '• ' . $cook['parent_names'] : ''))) ?></small></li>
                        <?php endforeach; ?>
                    </ul>
                </article>
                <article class="recipe-card">
                    <h3>Richieste contatti</h3>
                    <ul class="plain-list">
                        <?php foreach ($contactRequests as $request): ?>
                            <li>
                                <strong><?= e($request['name']) ?></strong> - <?= e($request['request_type']) ?>
                                <br><small><?= e($request['email']) ?><?= $request['phone'] ? ' • ' . e($request['phone']) : '' ?><?= $request['status'] ? ' • ' . e($request['status']) : '' ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </article>
            </div>
            <?php if ($cookRequests): ?>
                <h3>Richieste nuovi cuochi</h3>
                <div class="recipe-grid">
                    <?php foreach ($cookRequests as $request): ?>
                        <form method="post" action="/?action=admin_add_cook" class="stack-form recipe-card">
                            <input type="hidden" name="contact_request_id" value="<?= (int) $request['id'] ?>">
                            <h4><?= e($request['cook_full_name'] ?: 'Nuovo cuoco') ?></h4>
                            <label>Nome e cognome <input type="text" name="full_name" value="<?= e($request['cook_full_name'] ?: '') ?>" required></label>
                            <label>Data di nascita <input type="date" name="birth_date" value="<?= e($request['cook_birth_date'] ?: '') ?>"></label>
                            <label>Telefono <input type="text" name="phone" value="<?= e($request['cook_phone'] ?: '') ?>"></label>
                            <label>Mail <input type="email" name="email" value="<?= e($request['cook_email'] ?: '') ?>"></label>
                            <label>Figlio/a di... <input type="text" name="parent_names" value="<?= e($request['cook_parent_names'] ?: '') ?>"></label>
                            <label>Note admin <textarea name="notes" rows="4"><?= e($request['message']) ?></textarea></label>
                            <button type="submit">Approva e inserisci cuoco</button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <p>Solo admin e superadmin possono estendere i cataloghi e approvare i cuochi richiesti dalla pagina contatti.</p>
        </section>
    <?php else: ?>
        <section><h2>Pagina non trovata</h2></section>
    <?php endif; ?>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const heroSlider = document.querySelector('[data-hero-slider]');
    if (heroSlider) {
        const slides = Array.from(heroSlider.querySelectorAll('[data-hero-slide]'));
        const dots = Array.from(heroSlider.querySelectorAll('[data-hero-dot]'));
        let activeIndex = 0;

        const showSlide = (index) => {
            slides.forEach((slide, slideIndex) => slide.classList.toggle('is-active', slideIndex === index));
            dots.forEach((dot, dotIndex) => dot.classList.toggle('is-active', dotIndex === index));
            activeIndex = index;
        };

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => showSlide(index));
        });

        if (slides.length > 1) {
            window.setInterval(() => {
                showSlide((activeIndex + 1) % slides.length);
            }, 4000);
        }
    }

    const ingredientFieldset = document.querySelector('.dynamic-ingredients');
    if (ingredientFieldset) {
        const list = ingredientFieldset.querySelector('.ingredient-list');
        const template = document.getElementById('ingredient-row-template');
        const addButton = ingredientFieldset.querySelector('.add-ingredient-button');

        const wireIngredientRow = (row) => {
            const search = row.querySelector('.ingredient-search');
            const select = row.querySelector('.ingredient-select');
            const allOptions = Array.from(select.options).map(option => ({ value: option.value, label: option.textContent }));

            search.addEventListener('input', function () {
                const term = this.value.trim().toLowerCase();
                const currentValue = select.value;
                select.innerHTML = '';
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = term.length >= 3 ? 'Seleziona ingrediente filtrato' : 'Digita almeno 3 caratteri';
                select.appendChild(defaultOption);

                const filtered = term.length >= 3
                    ? allOptions.filter(option => option.value === '' || option.label.toLowerCase().includes(term))
                    : allOptions.filter(option => option.value === '');

                filtered.forEach(option => {
                    if (option.value === '') {
                        return;
                    }
                    const element = document.createElement('option');
                    element.value = option.value;
                    element.textContent = option.label;
                    if (option.value === currentValue) {
                        element.selected = true;
                    }
                    select.appendChild(element);
                });
            });
        };

        ingredientFieldset.querySelectorAll('.ingredient-row').forEach(wireIngredientRow);
        addButton.addEventListener('click', function () {
            const index = Number(ingredientFieldset.dataset.nextIndex || '1');
            const html = template.innerHTML.replace(/__INDEX__/g, String(index));
            list.insertAdjacentHTML('beforeend', html);
            ingredientFieldset.dataset.nextIndex = String(index + 1);
            wireIngredientRow(list.lastElementChild);
        });
    }

    const utensilSearch = document.getElementById('utensil-search');
    if (utensilSearch) {
        const options = Array.from(document.querySelectorAll('.utensil-option'));
        const selectedBox = document.querySelector('.selected-utensils');
        const renderSelected = () => {
            const selected = options
                .filter(option => option.querySelector('input').checked)
                .map(option => option.querySelector('span').textContent);
            if (selected.length === 0) {
                selectedBox.textContent = 'Nessun utensile selezionato.';
                selectedBox.classList.add('empty');
                return;
            }
            selectedBox.classList.remove('empty');
            selectedBox.innerHTML = selected.map(label => `<span class="tag">${label}</span>`).join(' ');
        };

        utensilSearch.addEventListener('input', function () {
            const term = this.value.trim().toLowerCase();
            options.forEach(option => {
                const matches = option.dataset.name.includes(term);
                option.style.display = matches ? 'flex' : 'none';
            });
        });

        options.forEach(option => option.querySelector('input').addEventListener('change', renderSelected));
        renderSelected();
    }
});
</script>
</body>
</html>
