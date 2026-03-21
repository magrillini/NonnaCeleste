<section class="hero hero-theme-<?= e($homeHeroTheme) ?>">
    <div class="hero-media" data-hero-slider>
        <?php foreach ($homeHeroSlides as $index => $slide): ?>
            <figure class="hero-slide <?= $index === 0 ? 'is-active' : '' ?>" data-hero-slide>
                <img src="<?= e(media_url($slide['path'])) ?>" alt="<?= e($slide['caption'] ?: 'Foto Home Nonna Celeste') ?>">
            </figure>
        <?php endforeach; ?>
        <?php if (count($homeHeroSlides) > 1): ?>
            <div class="hero-dots" aria-label="Selettore foto Home">
                <?php foreach ($homeHeroSlides as $index => $slide): ?>
                    <button type="button" class="hero-dot <?= $index === 0 ? 'is-active' : '' ?>" data-hero-dot="<?= (int) $index ?>" aria-label="Vai alla foto <?= (int) $index + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="hero-copy hero-copy-<?= e($homeHeroTheme) ?>">
        <span class="tag">Home</span>
        <h2 class="page-title home-title">NONNA CELESTE</h2>
        <p>Le ricette di famiglia, la memoria della tradizione e le varianti moderne raccolte in un unico archivio.</p>
        <div class="grid-buttons">
            <a class="card-button" href="/?action=traditional">Ricetta tradizionale</a>
            <a class="card-button" href="/?action=family">Ricette familiari</a>
            <a class="card-button" href="/?action=submit">Inserimento ricetta</a>
        </div>
    </div>
</section>
