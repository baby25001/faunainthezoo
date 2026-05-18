-- =============================================
-- ZOO MANAGEMENT SYSTEM - faunainthezoo
-- =============================================

-- 1. Hancurkan database lama jika ada, lalu buat baru yang segar
DROP DATABASE IF EXISTS faunainthezoo;
CREATE DATABASE faunainthezoo;
USE faunainthezoo;

-- 2. Matikan pengecekan Foreign Key agar proses pembuatan tabel lancar tanpa interupsi
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================
-- STRUKTUR TABEL
-- =============================================

-- HABITATS
CREATE TABLE IF NOT EXISTS habitats (
    id_habitat   INT AUTO_INCREMENT PRIMARY KEY,
    habitat_name VARCHAR(100) NOT NULL,
    temperature  VARCHAR(50)  NOT NULL,
    description  TEXT
);

-- ANIMALS
CREATE TABLE IF NOT EXISTS animals (
    id_animal   INT AUTO_INCREMENT PRIMARY KEY,
    animal_name VARCHAR(100) NOT NULL,
    species     VARCHAR(100) NOT NULL,
    id_habitat  INT NOT NULL,
    image_url   VARCHAR(255),
    FOREIGN KEY (id_habitat) REFERENCES habitats(id_habitat)
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- FOODS
CREATE TABLE IF NOT EXISTS foods (
    id_food    INT AUTO_INCREMENT PRIMARY KEY,
    foods_name VARCHAR(100) NOT NULL,
    nutrition  VARCHAR(255)
);

-- USERS
CREATE TABLE IF NOT EXISTS users (
    id_user    INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('zookeeper','visitor') DEFAULT 'visitor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- MEMAKAN (Junction M:M)
CREATE TABLE IF NOT EXISTS memakan (
    id_animal INT NOT NULL,
    id_food   INT NOT NULL,
    PRIMARY KEY (id_animal, id_food),
    FOREIGN KEY (id_animal) REFERENCES animals(id_animal)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_food) REFERENCES foods(id_food)
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- SCHEDULE
CREATE TABLE IF NOT EXISTS schedule (
    feeding_schedule TIME PRIMARY KEY
);

-- PEMBERIAN_PAKAN (Junction Ternary)
CREATE TABLE IF NOT EXISTS pemberian_pakan (
    id_animal        INT  NOT NULL,
    id_food          INT  NOT NULL,
    feeding_schedule TIME NOT NULL,
    status           ENUM('pending','done') DEFAULT 'pending',
    PRIMARY KEY (id_animal, id_food, feeding_schedule),
    FOREIGN KEY (id_animal) REFERENCES animals(id_animal)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_food) REFERENCES foods(id_food)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (feeding_schedule) REFERENCES schedule(feeding_schedule)
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- =============================================
-- SEEDER DATA (PENGISIAN DATA)
-- =============================================

INSERT INTO habitats (habitat_name, temperature, description) VALUES
('Savannah',           '25-35°C',  'Padang rumput luas dengan pohon akasia'),
('Arctic Zone',        '-20-0°C',  'Zona es dan salju ekstrem'),
('Tropical Rainforest','24-30°C',  'Hutan hujan tropis yang lebat dan lembab'),
('Wetland',            '18-28°C',  'Area rawa dan perairan dangkal'),
('Mountain',           '5-15°C',   'Pegunungan berbatu dengan udara dingin'),
('Arid/Dry',           '30-45°C',  'Gurun dan zona kering'),
('Coastal',            '15-25°C',  'Pesisir pantai dan tebing'),
('Aquatic',            '10-20°C',  'Kolam dan area air dalam');

INSERT INTO foods (foods_name, nutrition) VALUES
('Daging sapi',     'Protein tinggi, Lemak, Zat besi'),
('Daging ayam',     'Protein tinggi, Rendah lemak'),
('Ikan salmon',     'Omega-3, Protein, Vitamin D'),
('Rumput segar',    'Serat tinggi, Karbohidrat'),
('Daun akasia',     'Serat, Mineral'),
('Buah pisang',     'Karbohidrat, Kalium, Vitamin C'),
('Bambu',           'Serat tinggi, Silika'),
('Udang',           'Protein, Yodium, Selenium'),
('Ikan herring',    'Omega-3, Protein, Vitamin B12'),
('Serangga',        'Protein, Chitin, Lemak'),
('Daging kelinci',  'Protein tinggi, Rendah lemak'),
('Buah apel',       'Vitamin C, Serat, Antioksidan'),
('Cumi-cumi',       'Protein, Mineral, Rendah lemak'),
('Kaktus',          'Air tinggi, Vitamin C, Serat'),
('Daun muda',       'Vitamin, Mineral, Serat');

INSERT INTO animals (animal_name, species, id_habitat, image_url) VALUES
('African Lion',      'Panthera leo',                1, 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/73/Lion_waiting_in_Namibia.jpg/320px-Lion_waiting_in_Namibia.jpg'),
('African Elephant',  'Loxodonta africana',          1, 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/37/African_Bush_Elephant.jpg/320px-African_Bush_Elephant.jpg'),
('Giraffe',           'Giraffa camelopardalis',      1, 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9e/Giraffe_Mikumi_National_Park.jpg/240px-Giraffe_Mikumi_National_Park.jpg'),
('Zebra',             'Equus quagga',                1, 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e3/Plains_Zebra_Equus_quagga.jpg/320px-Plains_Zebra_Equus_quagga.jpg'),
('Cheetah',           'Acinonyx jubatus',            1, 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/09/TheCheethcat.jpg/320px-TheCheethcat.jpg'),
('Polar Bear',        'Ursus maritimus',             2, 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/66/Polar_Bear_-_Alaska_%28cropped%29.jpg/320px-Polar_Bear_-_Alaska_%28cropped%29.jpg'),
('Arctic Fox',        'Vulpes lagopus',              2, 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8f/Arctic_Fox_in_November.jpg/320px-Arctic_Fox_in_November.jpg'),
('Orangutan',         'Pongo pygmaeus',              3, 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/be/Orang_Utan%2C_Semenggok_Forest_Reserve%2C_Sarawak%2C_Borneo%2C_Malaysia.JPG/240px-Orang_Utan%2C_Semenggok_Forest_Reserve%2C_Sarawak%2C_Borneo%2C_Malaysia.JPG'),
('Bengal Tiger',      'Panthera tigris tigris',      3, 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Tiger_in_Ranthambhore.jpg/320px-Tiger_in_Ranthambhore.jpg'),
('Gorilla',           'Gorilla gorilla',             3, 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/bb/Gorille_des_plaines_de_l%27ouest_%28Le_Zoo_de_Vincennes%29_%2814812043112%29.jpg/240px-Gorille_des_plaines_de_l%27ouest_%28Le_Zoo_de_Vincennes%29_%2814812043112%29.jpg'),
('Flamingo',          'Phoenicopterus roseus',       4, 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/Pink_flamingo_2.jpg/240px-Pink_flamingo_2.jpg'),
('Nile Crocodile',    'Crocodylus niloticus',        4, 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a2/Nile_crocodile_head.jpg/320px-Nile_crocodile_head.jpg'),
('Hippopotamus',      'Hippopotamus amphibius',      4, 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a9/Hippo_at_SF_Zoo.jpg/320px-Hippo_at_SF_Zoo.jpg'),
('Snow Leopard',      'Panthera uncia',              5, 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3b/Snow_leopard_%28Panthera_uncia%29_in_the_Toronto_Zoo_1.jpg/320px-Snow_leopard_%28Panthera_uncia%29_in_the_Toronto_Zoo_1.jpg'),
('Bald Eagle',        'Haliaeetus leucocephalus',    5, 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1e/Bald_Eagle_Portrait.jpg/240px-Bald_Eagle_Portrait.jpg'),
('Komodo Dragon',     'Varanus komodoensis',         6, 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e0/Komodo_dragon_with_tongue.jpg/320px-Komodo_dragon_with_tongue.jpg'),
('Giant Tortoise',    'Chelonoidis niger',           6, 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a1/Giant_Tortoise_Chelonoidis_nigra.jpg/320px-Giant_Tortoise_Chelonoidis_nigra.jpg'),
('African Penguin',   'Spheniscus demersus',         7, 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/02/African_Penguin_%28Spheniscus_demersus%29_at_Boulders_Beach.jpg/240px-African_Penguin_%28Spheniscus_demersus%29_at_Boulders_Beach.jpg'),
('Bottlenose Dolphin','Tursiops truncatus',          8, 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/10/Tursiops_truncatus_01.jpg/320px-Tursiops_truncatus_01.jpg'),
('Red Panda',         'Ailurus fulgens',             3, 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/50/RedPandaFull_gringer.jpg/320px-RedPandaFull_gringer.jpg'),
('Giant Panda',       'Ailuropoda melanoleuca',      3, 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0f/Grosser_Panda.JPG/320px-Grosser_Panda.JPG'),
('Meerkat',           'Suricata suricatta',          1, 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b6/Meerkat_%28Suricata_suricatta%29_Tswalu.jpg/240px-Meerkat_%28Suricata_suricatta%29_Tswalu.jpg'),
('Mandrill',          'Mandrillus sphinx',           3, 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/71/Mandrill_at_Bristol_Zoo.jpg/240px-Mandrill_at_Bristol_Zoo.jpg'),
('African Wild Dog',  'Lycaon pictus',               1, 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8f/African_Wild_Dog.jpg/320px-African_Wild_Dog.jpg'),
('Reticulated Python','Malayopython reticulatus',    3, 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/59/Malayopython_reticulatus_2019.jpg/320px-Malayopython_reticulatus_2019.jpg');

-- Relasi memakan
INSERT INTO memakan (id_animal, id_food) VALUES
(1,1),(1,2),(2,4),(2,5),(2,6),(3,5),(3,6),(4,4),(5,1),(5,11),(6,3),(6,1),(7,3),(7,10),
(8,6),(8,15),(9,1),(9,2),(10,15),(10,6),(10,12),(11,8),(11,4),(12,1),(12,3),(13,4),(13,15),
(14,1),(14,11),(15,1),(15,11),(16,1),(16,2),(17,14),(17,4),(18,9),(18,13),(19,3),(19,13),
(20,7),(20,12),(21,7),(21,12),(22,10),(22,6),(23,6),(23,10),(23,12),(24,1),(24,2),(25,2),(25,11);

-- Jadwal waktu makan
INSERT INTO schedule (feeding_schedule) VALUES
('07:00:00'), ('12:00:00'), ('17:00:00');

-- Pemberian pakan
INSERT INTO pemberian_pakan (id_animal, id_food, feeding_schedule, status) VALUES
(1,1,'07:00:00','pending'),(1,2,'17:00:00','pending'),
(2,4,'07:00:00','pending'),(2,6,'12:00:00','pending'),
(3,5,'07:00:00','pending'),(3,6,'17:00:00','pending'),
(4,4,'07:00:00','pending'),(4,4,'17:00:00','pending'),
(5,1,'07:00:00','pending'),(5,11,'17:00:00','pending'),
(6,3,'07:00:00','pending'),(6,1,'12:00:00','pending'),
(7,3,'07:00:00','pending'),
(8,6,'07:00:00','pending'),(8,15,'12:00:00','pending'),
(9,1,'07:00:00','pending'),(9,2,'17:00:00','pending'),
(10,15,'07:00:00','pending'),(10,6,'12:00:00','pending'),
(11,8,'07:00:00','pending'),(11,8,'17:00:00','pending'),
(12,1,'12:00:00','pending'),
(13,4,'07:00:00','pending'),(13,4,'17:00:00','pending'),
(14,1,'07:00:00','pending'),(14,11,'17:00:00','pending'),
(15,1,'12:00:00','pending'),
(16,1,'12:00:00','pending'),(16,2,'17:00:00','pending'),
(17,14,'07:00:00','pending'),
(18,9,'07:00:00','pending'),(18,9,'17:00:00','pending'),
(19,3,'07:00:00','pending'),(19,13,'17:00:00','pending'),
(20,7,'07:00:00','pending'),(20,7,'17:00:00','pending'),
(21,7,'07:00:00','pending'),(21,12,'12:00:00','pending'),
(22,10,'07:00:00','pending'),
(23,6,'07:00:00','pending'),(23,10,'12:00:00','pending'),
(24,1,'07:00:00','pending'),(24,2,'17:00:00','pending'),
(25,11,'12:00:00','pending');

-- User default
INSERT INTO users (username, password, role) VALUES
('keeper1',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'zookeeper'),
('visitor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'visitor');

-- 3. Nyalakan kembali pengecekan Foreign Key demi keamanan data kedepannya
SET FOREIGN_KEY_CHECKS = 1;