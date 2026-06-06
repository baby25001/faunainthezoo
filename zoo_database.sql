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
    image_url   VARCHAR(512),
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
('African Lion',      'Panthera leo',                1, 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/020_The_lion_king_Snyggve_in_the_Serengeti_National_Park_Photo_by_Giles_Laurent.jpg/330px-020_The_lion_king_Snyggve_in_the_Serengeti_National_Park_Photo_by_Giles_Laurent.jpg'),
('African Elephant',  'Loxodonta africana',          1, 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/bf/African_Elephant_%28Loxodonta_africana%29_male_%2817289351322%29.jpg/330px-African_Elephant_%28Loxodonta_africana%29_male_%2817289351322%29.jpg'),
('Giraffe',           'Giraffa camelopardalis',      1, 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9e/Giraffe_Mikumi_National_Park.jpg/330px-Giraffe_Mikumi_National_Park.jpg'),
('Zebra',             'Equus quagga',                1, 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/96/Plains_Zebra_Equus_quagga_cropped.jpg/330px-Plains_Zebra_Equus_quagga_cropped.jpg'),
('Cheetah',           'Acinonyx jubatus',            1, 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/92/Male_cheetah_facing_left_in_South_Africa.jpg/330px-Male_cheetah_facing_left_in_South_Africa.jpg'),
('Polar Bear',        'Ursus maritimus',             2, 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/66/Polar_Bear_-_Alaska_%28cropped%29.jpg/330px-Polar_Bear_-_Alaska_%28cropped%29.jpg'),
('Arctic Fox',        'Vulpes lagopus',              2, 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/dc/Vulpes_lagopus_in_Iceland_%28cropped_3%29.jpg/330px-Vulpes_lagopus_in_Iceland_%28cropped_3%29.jpg'),
('Orangutan',         'Pongo pygmaeus',              3, 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/be/Orang_Utan%2C_Semenggok_Forest_Reserve%2C_Sarawak%2C_Borneo%2C_Malaysia.JPG/330px-Orang_Utan%2C_Semenggok_Forest_Reserve%2C_Sarawak%2C_Borneo%2C_Malaysia.JPG'),
('Bengal Tiger',      'Panthera tigris tigris',      3, 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/84/Bengal_tiger_in_Sanjay_Dubri_Tiger_Reserve_December_2024_by_Tisha_Mukherjee_11.jpg/330px-Bengal_tiger_in_Sanjay_Dubri_Tiger_Reserve_December_2024_by_Tisha_Mukherjee_11.jpg'),
('Gorilla',           'Gorilla gorilla',             3, 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/bb/Gorille_des_plaines_de_l%27ouest_%C3%A0_l%27Espace_Zoologique.jpg/330px-Gorille_des_plaines_de_l%27ouest_%C3%A0_l%27Espace_Zoologique.jpg'),
('Flamingo',          'Phoenicopterus roseus',       4, 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/38/Flamingos_Laguna_Colorada.jpg/330px-Flamingos_Laguna_Colorada.jpg'),
('Nile Crocodile',    'Crocodylus niloticus',        4, 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/81/NileCrocodile.jpg/330px-NileCrocodile.jpg'),
('Hippopotamus',      'Hippopotamus amphibius',      4, 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f2/Portrait_Hippopotamus_in_the_water.jpg/330px-Portrait_Hippopotamus_in_the_water.jpg'),
('Snow Leopard',      'Panthera uncia',              5, 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a5/Irbis4.JPG/330px-Irbis4.JPG'),
('Bald Eagle',        'Haliaeetus leucocephalus',    5, 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/db/Bald_eagle_about_to_fly_in_Alaska_%282016%29.jpg/330px-Bald_eagle_about_to_fly_in_Alaska_%282016%29.jpg'),
('Komodo Dragon',     'Varanus komodoensis',         6, 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f5/202306_Varanus_komodoensis.jpg/330px-202306_Varanus_komodoensis.jpg'),
('Giant Tortoise',    'Chelonoidis niger',           6, 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/aa/A._gigantea_Aldabra_Giant_Tortoise.jpg/330px-A._gigantea_Aldabra_Giant_Tortoise.jpg'),
('African Penguin',   'Spheniscus demersus',         7, 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Wikimania_2018%2C_Cape_Town_%28_1050602%29%2C_crop.jpg/330px-Wikimania_2018%2C_Cape_Town_%28_1050602%29%2C_crop.jpg'),
('Bottlenose Dolphin','Tursiops truncatus',          8, 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/10/Tursiops_truncatus_01.jpg/330px-Tursiops_truncatus_01.jpg'),
('Red Panda',         'Ailurus fulgens',             3, 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/fd/Red_Panda%2C_Gentle_Tree-Dweller_of_the_Himalayas.jpg/330px-Red_Panda%2C_Gentle_Tree-Dweller_of_the_Himalayas.jpg'),
('Giant Panda',       'Ailuropoda melanoleuca',      3, 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0f/Grosser_Panda.JPG/330px-Grosser_Panda.JPG'),
('Meerkat',           'Suricata suricatta',          1, 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9a/Meerkat_%28Suricata_suricatta%29_Tswalu.jpg/330px-Meerkat_%28Suricata_suricatta%29_Tswalu.jpg'),
('Mandrill',          'Mandrillus sphinx',           3, 'https://upload.wikimedia.org/wikipedia/commons/e/ed/Mandrill_Albert_September_2015_Zoo_Berlin_%282%29.jpg'),
('African Wild Dog',  'Lycaon pictus',               1, 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/75/African_Wild_Dog_at_Working_with_Wildlife.jpg/330px-African_Wild_Dog_at_Working_with_Wildlife.jpg'),
('Reticulated Python','Malayopython reticulatus',    3, 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b4/Python_reticulatus_%D1%81%D0%B5%D1%82%D1%87%D0%B0%D1%82%D1%8B%D0%B9_%D0%BF%D0%B8%D1%82%D0%BE%D0%BD-2.jpg/330px-Python_reticulatus_%D1%81%D0%B5%D1%82%D1%87%D0%B0%D1%82%D1%8B%D0%B9_%D0%BF%D0%B8%D1%82%D0%BE%D0%BD-2.jpg  ');

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
('keeper1',  '$2y$10$l4w9q9ndq2tRlOuQ7p2qAOfNpnu27/0.kO4t49NLQmexVoGn8Whwe', 'zookeeper'),
('visitor1', '$2y$10$l4w9q9ndq2tRlOuQ7p2qAOfNpnu27/0.kO4t49NLQmexVoGn8Whwe', 'visitor');

-- 3. Nyalakan kembali pengecekan Foreign Key demi keamanan data kedepannya
SET FOREIGN_KEY_CHECKS = 1;