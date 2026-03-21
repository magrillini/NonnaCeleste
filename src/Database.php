<?php

declare(strict_types=1);

final class Database
{
    public static function connection(): PDO
    {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        if (!is_dir(DATA_PATH)) {
            mkdir(DATA_PATH, 0777, true);
        }

        $pdo = new PDO('sqlite:' . DATA_PATH . '/nonnaceleste.sqlite');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }

    public static function migrate(PDO $db): void
    {
        $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('superadmin','admin','user')),
    created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS cooks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name TEXT NOT NULL UNIQUE,
    birth_date TEXT,
    phone TEXT,
    email TEXT,
    parent_names TEXT,
    notes TEXT,
    created_by_user_id INTEGER,
    created_at TEXT NOT NULL,
    FOREIGN KEY(created_by_user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS contact_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT,
    request_type TEXT NOT NULL CHECK(request_type IN ('general','deletion','cook')),
    message TEXT NOT NULL,
    recipe_reference TEXT,
    cook_full_name TEXT,
    cook_birth_date TEXT,
    cook_phone TEXT,
    cook_email TEXT,
    cook_parent_names TEXT,
    status TEXT NOT NULL DEFAULT 'nuova',
    created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS recipes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    cook_name TEXT NOT NULL,
    cook_id INTEGER,
    author_user_id INTEGER NOT NULL,
    visibility_type TEXT NOT NULL CHECK(visibility_type IN ('tradizionale','variante','familiare')),
    holiday TEXT,
    meal_time TEXT NOT NULL,
    course_type TEXT,
    execution_method TEXT NOT NULL,
    is_traditional INTEGER NOT NULL DEFAULT 0,
    approved_by_admin INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(cook_id) REFERENCES cooks(id),
    FOREIGN KEY(author_user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS ingredients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    created_by_role TEXT NOT NULL DEFAULT 'seed'
);

CREATE TABLE IF NOT EXISTS utensils (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    created_by_role TEXT NOT NULL DEFAULT 'seed'
);

CREATE TABLE IF NOT EXISTS cooking_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS recipe_ingredients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    recipe_id INTEGER NOT NULL,
    ingredient_id INTEGER NOT NULL,
    quantity_value REAL,
    quantity_unit TEXT NOT NULL CHECK(quantity_unit IN ('gr','cl','qb')),
    FOREIGN KEY(recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY(ingredient_id) REFERENCES ingredients(id)
);

CREATE TABLE IF NOT EXISTS recipe_utensils (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    recipe_id INTEGER NOT NULL,
    utensil_id INTEGER NOT NULL,
    FOREIGN KEY(recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY(utensil_id) REFERENCES utensils(id)
);

CREATE TABLE IF NOT EXISTS recipe_cooking_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    recipe_id INTEGER NOT NULL,
    cooking_method_id INTEGER NOT NULL,
    minutes INTEGER NOT NULL,
    FOREIGN KEY(recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY(cooking_method_id) REFERENCES cooking_methods(id)
);

CREATE TABLE IF NOT EXISTS recipe_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    recipe_id INTEGER NOT NULL,
    path TEXT NOT NULL,
    caption TEXT,
    uploaded_by_user_id INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY(recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY(uploaded_by_user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    recipe_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    body TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS site_settings (
    key_name TEXT PRIMARY KEY,
    value TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
SQL);

        self::ensureColumn($db, 'recipes', 'cook_id', 'INTEGER REFERENCES cooks(id)');
        self::ensureColumn($db, 'contact_requests', 'phone', 'TEXT');
        self::ensureColumn($db, 'contact_requests', 'request_type', "TEXT NOT NULL DEFAULT 'general'");
        self::ensureColumn($db, 'contact_requests', 'recipe_reference', 'TEXT');
        self::ensureColumn($db, 'contact_requests', 'cook_full_name', 'TEXT');
        self::ensureColumn($db, 'contact_requests', 'cook_birth_date', 'TEXT');
        self::ensureColumn($db, 'contact_requests', 'cook_phone', 'TEXT');
        self::ensureColumn($db, 'contact_requests', 'cook_email', 'TEXT');
        self::ensureColumn($db, 'contact_requests', 'cook_parent_names', 'TEXT');
        self::ensureColumn($db, 'contact_requests', 'status', "TEXT NOT NULL DEFAULT 'nuova'");
    }

    private static function ensureColumn(PDO $db, string $table, string $column, string $definition): void
    {
        $columns = $db->query(sprintf('PRAGMA table_info(%s)', $table))->fetchAll();
        foreach ($columns as $info) {
            if (($info['name'] ?? null) === $column) {
                return;
            }
        }

        $db->exec(sprintf('ALTER TABLE %s ADD COLUMN %s %s', $table, $column, $definition));
    }

    public static function seed(PDO $db): void
    {
        $count = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count === 0) {
            $users = [
                ['Super Admin', 'superadmin@nonnaceleste.local', password_hash('superadmin123', PASSWORD_DEFAULT), 'superadmin'],
                ['Admin Ricette', 'admin@nonnaceleste.local', password_hash('admin123', PASSWORD_DEFAULT), 'admin'],
                ['Maria Rossi', 'maria@example.com', password_hash('user123', PASSWORD_DEFAULT), 'user'],
            ];
            $stmt = $db->prepare('INSERT INTO users(name,email,password,role,created_at) VALUES(?,?,?,?,?)');
            foreach ($users as $user) {
                $stmt->execute([$user[0], $user[1], $user[2], $user[3], date(DATE_ATOM)]);
            }
        }

        $ingredientsCount = (int) $db->query('SELECT COUNT(*) FROM ingredients')->fetchColumn();
        if ($ingredientsCount === 0) {
            $ingredients = [
                'Pomodoro San Marzano','Pomodorino del Piennolo','Passata di pomodoro','Melanzana viola','Zucchina romanesca','Peperone rosso','Peperone giallo','Carciofo romanesco','Carciofo spinoso','Cavolfiore','Broccolo siciliano','Friarielli','Cime di rapa','Bietola','Spinaci','Cicoria','Scarola','Radicchio','Finocchio','Sedano','Carota','Cipolla rossa','Cipolla bianca','Aglio','Porro','Patata novella','Patata dolce','Zucca mantovana','Fagiolino','Piselli','Fava fresca','Lenticchia di Castelluccio','Ceci','Fagiolo cannellino','Fagiolo borlotto','Cicerchia','Roveja','Grano duro','Farro','Orzo perlato','Riso arborio','Riso carnaroli','Polenta di mais','Pane casereccio','Pane di Altamura','Pangrattato','Pasta di semola','Orecchiette','Paccheri','Spaghetti','Tagliatelle','Gnocchi di patate','Lasagna secca','Cous cous integrale','Semolino','Farina 00','Farina integrale','Farina di castagne','Farina di ceci','Farina di mandorle','Lievito di birra','Lievito madre','Olio extravergine d\'oliva','Olive taggiasche','Olive nere','Capperi','Origano','Basilico','Prezzemolo','Rosmarino','Salvia','Timo','Maggiorana','Alloro','Finocchietto selvatico','Peperoncino calabrese','Zafferano','Noce moscata','Cannella','Chiodi di garofano','Sale marino','Sale grosso','Pepe nero','Pepe bianco','Aceto di vino','Aceto balsamico','Succo di limone','Arancia bionda','Mandarino tardivo','Cedro','Fico d\'India','Melograno','Uva fragola','Uvetta','Pinoli','Noci','Nocciole','Mandorle','Pistacchi di Bronte','Fichi secchi','Datteri','Miele millefiori','Miele di castagno','Zucchero di canna','Zucchero semolato','Cacao amaro','Cioccolato fondente','Ricotta vaccina','Ricotta di pecora','Mozzarella fiordilatte','Mozzarella di bufala','Provola affumicata','Scamorza','Parmigiano Reggiano','Grana Padano','Pecorino Romano','Pecorino sardo','Gorgonzola dolce','Mascarpone','Burro','Latte intero','Latte di mandorla','Yogurt bianco','Panna fresca','Uova','Albume','Tuorlo','Guanciale','Pancetta tesa','Prosciutto crudo','Speck','Salsiccia fresca','Pollo ruspante','Tacchino','Coniglio','Vitello','Manzo','Maiale','Agnello','Cinghiale','Baccalà','Acciughe','Sarde','Tonno fresco','Tonno sott\'olio','Orata','Branzino','Cozze','Vongole','Calamari','Seppie','Gamberi','Polpo','Salmone affumicato','Pane carasau','Burrata','Stracciatella','Caciocavallo','Taleggio','Asiago','Ragusano','Feta mediterranea','Tofu alle erbe','Fiori di zucca','Asparagi','Porcini','Champignon','Tartufo nero','Castagne','Erbette di campo','Rucola','Songino','Lattuga romana','Cetriolo','Barbabietola','Topinambur','Cardo','Lampascioni','Cipolla di Tropea','Alici sotto sale','Bottarga','Colatura di alici','Mostarda di frutta','Amarene','Confettura di fichi','Liquore Strega','Marsala','Vermouth bianco','Amido di mais','Gelatina alimentare','Acqua di fiori d\'arancio','Anice','Semi di sesamo','Semi di finocchio','Semi di zucca','Semi di girasole','Farina di grano saraceno','Miglio','Quinoa mediterranea','Avena','Kefir','Lupini','Papaccelle','Pomodori secchi','Carne salada','Soppressata','Taralli','Friselle','Pane raffermo','Basilico rosso','Menta fresca','Erba cipollina','Lemongrass mediterraneo'
            ];
            $stmt = $db->prepare('INSERT INTO ingredients(name,created_by_role) VALUES(?,?)');
            foreach ($ingredients as $ingredient) {
                $stmt->execute([$ingredient, 'seed']);
            }
        }

        $utensilsCount = (int) $db->query('SELECT COUNT(*) FROM utensils')->fetchColumn();
        if ($utensilsCount === 0) {
            $utensils = ['Pentola','Padella','Casseruola','Teglia','Tortiera','Coltello da chef','Coltellino','Tagliere','Mestolo','Schiumarola','Frusta','Spatola','Cucchiaio di legno','Pelapatate','Grattugia','Scolapasta','Setaccio','Bilancia','Ciotola capiente','Planetaria','Frullatore','Mixer a immersione','Mattarello','Trafila pasta','Stampo biscotti','Pirofila','Leccarda','Griglia','Cestello vapore','Moka'];
            $stmt = $db->prepare('INSERT INTO utensils(name,created_by_role) VALUES(?,?)');
            foreach ($utensils as $utensil) {
                $stmt->execute([$utensil, 'seed']);
            }
        }

        $methodsCount = (int) $db->query('SELECT COUNT(*) FROM cooking_methods')->fetchColumn();
        if ($methodsCount === 0) {
            $methods = ['Bollitura','Forno statico','Forno ventilato','Vapore','Brasatura','Rosolatura','Frittura','Griglia','Stufatura','Mantecatura'];
            $stmt = $db->prepare('INSERT INTO cooking_methods(name) VALUES(?)');
            foreach ($methods as $method) {
                $stmt->execute([$method]);
            }
        }

        $cooksCount = (int) $db->query('SELECT COUNT(*) FROM cooks')->fetchColumn();
        if ($cooksCount === 0) {
            $cooks = [
                ['Admin Ricette', '1968-05-14', '3331112222', 'admin.ricette@nonnaceleste.local', 'Figlio di Giovanni e Teresa', 'Cuoco storico del ricettario', 2],
                ['Maria Rossi', '1988-09-02', '3334445555', 'maria@example.com', 'Figlia di Antonio e Lucia', 'Cuoca di famiglia', 3],
            ];
            $stmt = $db->prepare('INSERT INTO cooks(full_name,birth_date,phone,email,parent_names,notes,created_by_user_id,created_at) VALUES(?,?,?,?,?,?,?,?)');
            foreach ($cooks as $cook) {
                $stmt->execute([$cook[0], $cook[1], $cook[2], $cook[3], $cook[4], $cook[5], $cook[6], date(DATE_ATOM)]);
            }
        }

        $recipeCount = (int) $db->query('SELECT COUNT(*) FROM recipes')->fetchColumn();
        if ($recipeCount === 0) {
            $db->exec("INSERT INTO recipes(title,cook_name,cook_id,author_user_id,visibility_type,holiday,meal_time,course_type,execution_method,is_traditional,approved_by_admin,created_at,updated_at) VALUES
            ('Lasagna di Carnevale','Admin Ricette',1,2,'tradizionale','Carnevale','pranzo','primo','Preparare il ragù, cuocere la besciamella, comporre gli strati e cuocere in forno fino a gratinatura.',1,1,'" . date(DATE_ATOM) . "','" . date(DATE_ATOM) . "'),
            ('Pastiera di famiglia','Maria Rossi',2,3,'familiare','Pasqua','merenda','dolce','Impastare la frolla, preparare il ripieno con ricotta e grano, cuocere e far riposare 24 ore.',0,1,'" . date(DATE_ATOM) . "','" . date(DATE_ATOM) . "')");
            $db->exec("INSERT INTO recipe_ingredients(recipe_id,ingredient_id,quantity_value,quantity_unit) VALUES (1,48,500,'gr'),(1,118,300,'gr'),(1,126,100,'gr'),(2,116,400,'gr'),(2,38,250,'gr'),(2,125,3,'qb')");
            $db->exec("INSERT INTO recipe_utensils(recipe_id,utensil_id) VALUES (1,1),(1,4),(2,5),(2,19)");
            $db->exec("INSERT INTO recipe_cooking_methods(recipe_id,cooking_method_id,minutes) VALUES (1,2,35),(2,2,60)");
            $db->exec("INSERT INTO comments(recipe_id,user_id,body,created_at,updated_at) VALUES (1,3,'Ricetta perfetta per il pranzo della domenica.', '" . date(DATE_ATOM) . "', '" . date(DATE_ATOM) . "')");
        }
    }
}
