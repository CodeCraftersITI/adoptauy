-- Base separada para PHPUnit (phpunit.xml usa adoptauy_test). Solo corre al crear el volumen.
CREATE DATABASE IF NOT EXISTS adoptauy_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON adoptauy_test.* TO 'adoptauy'@'%';
FLUSH PRIVILEGES;
