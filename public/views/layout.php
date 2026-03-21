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
<?php if ($action === 'landing'): ?>
    <main class="landing-page<?= $loginCoverImage ? ' has-cover' : '' ?>"<?php if ($loginCoverImage): ?> style="background-image: url(<?= e(media_url($loginCoverImage)) ?>);"<?php endif; ?>>
        <section class="landing-shell">
            <div class="landing-card">
                <p class="landing-kicker">Benvenuti</p>
                <h1 class="landing-title">WWW.NONNACELESTE.IT</h1>
                <p class="landing-copy">Accedi per entrare nella casa digitale di Nonna Celeste. L'immagine di sfondo può essere gestita soltanto da Admin e Super Admin.</p>
                <form method="post" action="/?action=login" class="stack-form landing-form">
                    <label>Email <input type="email" name="email" placeholder="email" required></label>
                    <label>Password <input type="password" name="password" placeholder="password" required></label>
                    <button type="submit">Entra nel sito</button>
                </form>
            </div>
        </section>
    </main>
<?php else: ?>
<header class="site-header">
    <div class="site-branding">
        <h1>WWW.NONNACELESTE.IT</h1>
        <p>Archivio di ricette della Tradizione Familiare e le Moderne varianti</p>
    <div class="site-header-top">
        <div class="site-header-spacer" aria-hidden="true"></div>
        <div class="site-branding">
            <h1>WWW.NONNACELESTE.IT</h1>
            <p>Archivio di ricette della Tradizione Familiare e le Moderne varianti</p>
        </div>
        <div class="auth-box">
            <?php if ($user): ?>
                <div class="user-panel user-panel-compact">
                    <div class="user-summary">
                        <strong><?= e($user['name']) ?></strong>
                        <span class="role"><?= e($user['role']) ?></span>
                    </div>
                    <div class="user-links">
                        <a href="/?action=profile">Area dati personali</a>
                        <a href="/?action=profile#password-box">Cambio password</a>
                        <a href="/?action=logout">Esci</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="site-stats">
        <span><strong>Pagine visitate:</strong> <?= (int) $pageViews ?></span>
        <span><strong>Utenti loggati ora:</strong> <?= (int) $activeUsersCount ?></span>
        <span><strong>Ricette inserite:</strong> <?= (int) $totalRecipes ?></span>
    </div>
    <nav>
        <a href="/">Home</a>
        <a href="/?action=home">Home</a>
        <a href="/?action=book">Libro delle ricette</a>
        <a href="/?action=traditional">Ricette tradizionali</a>
        <a href="/?action=family">Ricette familiari</a>
        <a href="/?action=photo_gallery">Photo Gallery</a>
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
            <h2 class="page-title"><?= $action === 'traditional' ? 'Ricette tradizionali e varianti' : 'Ricette familiari' ?></h2>
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
@@ -243,64 +266,107 @@ $courseTypes = ['antipasto','primo','secondo','contorno','dolce'];
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
    <?php elseif ($action === 'book'): ?>
        <section>
            <h2 class="page-title">Galleria ricette</h2>
            <h2 class="page-title">Libro delle ricette</h2>
            <p class="helper-text section-intro">Una libreria completa con tutte le ricette pubblicate, indipendentemente dalla categoria.</p>
            <div class="recipe-grid">
                <?php foreach ($galleryRecipes as $item): ?>
                <?php foreach ($bookRecipes as $item): ?>
                    <article class="recipe-card">
                        <?php if (!empty($item['image_path'])): ?><img src="<?= e(media_url($item['image_path'])) ?>" alt="<?= e($item['title']) ?>"><?php endif; ?>
                        <span class="tag"><?= e($item['visibility_type']) ?></span>
                        <h3><?= e($item['title']) ?></h3>
                        <p><strong>Cuoco:</strong> <?= e($item['cook_name']) ?></p>
                        <a href="/?action=recipe&id=<?= (int) $item['id'] ?>">Vai alla ricetta</a>
                        <p><strong>Festività:</strong> <?= e($item['holiday'] ?: 'Nessuna') ?></p>
                        <p><strong>Momento:</strong> <?= e($item['meal_time']) ?><?= $item['course_type'] ? ' / ' . e($item['course_type']) : '' ?></p>
                        <a href="/?action=recipe&id=<?= (int) $item['id'] ?>">Apri la ricetta</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php elseif ($action === 'photo_gallery'): ?>
        <section>
            <h2 class="page-title">Photo Gallery</h2>
            <p class="helper-text section-intro">Qui sono raccolte tutte le foto presenti nel sito: immagini delle ricette e foto usate nella Home.</p>
            <div class="photo-gallery-grid">
                <?php foreach ($photoGallery as $photo): ?>
                    <article class="photo-card">
                        <img src="<?= e(media_url($photo['path'])) ?>" alt="<?= e($photo['caption'] ?: $photo['title']) ?>">
                        <div class="photo-card-copy">
                            <span class="tag"><?= $photo['source_type'] === 'home' ? 'home' : 'ricetta' ?></span>
                            <h3><?= e($photo['caption'] ?: $photo['title']) ?></h3>
                            <p><strong>Origine:</strong> <?= e($photo['title']) ?></p>
                            <p><strong>Riferimento:</strong> <?= e($photo['cook_name']) ?></p>
                            <?php if (!empty($photo['recipe_id'])): ?><a href="/?action=recipe&id=<?= (int) $photo['recipe_id'] ?>">Vai alla ricetta</a><?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php elseif ($action === 'profile' && $user): ?>
        <section>
            <h2 class="page-title">Area dati personali</h2>
            <div class="admin-grid">
                <form method="post" action="/?action=update_profile" class="stack-form">
                    <h3>Dati profilo</h3>
                    <label>Nome completo <input type="text" name="name" value="<?= e($user['name']) ?>" required></label>
                    <label>Email <input type="email" name="email" value="<?= e($user['email']) ?>" required></label>
                    <label>Ruolo <input type="text" value="<?= e($user['role']) ?>" disabled></label>
                    <button type="submit">Salva dati personali</button>
                </form>
                <form method="post" action="/?action=change_password" class="stack-form" id="password-box">
                    <h3>Cambio password</h3>
                    <label>Password attuale <input type="password" name="current_password" required></label>
                    <label>Nuova password <input type="password" name="new_password" minlength="8" required></label>
                    <label>Conferma nuova password <input type="password" name="confirm_password" minlength="8" required></label>
                    <button type="submit">Aggiorna password</button>
                </form>
            </div>
        </section>
    <?php elseif ($action === 'contacts'): ?>
        <section>
            <h2 class="page-title">Contatti e richieste</h2>
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
@@ -308,50 +374,63 @@ $courseTypes = ['antipasto','primo','secondo','contorno','dolce'];
                    <label>Dettagli richiesta <textarea name="message" rows="5" required></textarea></label>
                    <button type="submit">Invia richiesta all'admin</button>
                </form>
            </div>
        </section>
    <?php elseif ($action === 'admin' && is_admin()): ?>
        <section>
            <h2 class="page-title">Pannello amministrazione</h2>
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
                <form method="post" action="/?action=admin_save_login_cover" enctype="multipart/form-data" class="stack-form">
                    <h3>Immagine pagina login</h3>
                    <p class="helper-text">Questa foto viene mostrata a pagina piena prima del login al sito.</p>
                    <label>Nuova immagine login
                        <input type="file" name="login_cover_image" accept="image/jpeg,image/png,image/webp,image/gif" required>
                    </label>
                    <button type="submit">Aggiorna pagina login</button>
                </form>
                <form method="post" action="/?action=admin_reset_login_cover" class="stack-form">
                    <h3>Ripristina pagina login</h3>
                    <p class="helper-text">Rimuove la foto fullscreen e torna alla pagina bianca di accesso.</p>
                    <button type="submit" class="secondary-button">Rimuovi foto login</button>
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
@@ -403,50 +482,51 @@ $courseTypes = ['antipasto','primo','secondo','contorno','dolce'];
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
<?php endif; ?>
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
