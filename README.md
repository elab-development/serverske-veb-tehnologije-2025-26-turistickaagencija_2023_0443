# Turistička Agencija - REST API

Backend REST API aplikacija za turističku agenciju razvijena u Laravel frameworku.

## Tehnologije

- PHP 8.2
- Laravel 11
- MySQL
- Laravel Sanctum (autentifikacija)

## Funkcionalnosti

- Upravljanje aranžmanima (CRUD, filtriranje, sortiranje, paginacija)
- Sistem rezervacija
- Sistem recenzija
- Autentifikacija korisnika (register, login, logout)
- Reset lozinke
- Tri uloge korisnika: administrator, menadžer i registrovani korisnik
- Eksport podataka u CSV format
- Integracija sa OpenWeatherMap API-jem
- Keširanje odgovora

## Pokretanje projekta

### Preduslovi
- PHP 8.2+
- Composer
- MySQL

### Instalacija

1. Klonirati repozitorijum
git clone https://github.com/M4TEJ4/STEH_turisticka_agencija.git

2. Instalirati zavisnosti
composer install

3. Kopirati .env fajl
cp .env.example .env

4. Generisati aplikacioni ključ
php artisan key:generate

5. Podesiti bazu podataka u .env fajlu
DB_DATABASE=turisticka_agencija
DB_USERNAME=root
DB_PASSWORD=

6. Pokrenuti migracije i seedere
php artisan migrate --seed

7. Pokrenuti aplikaciju
php artisan serve

Aplikacija je dostupna na: http://127.0.0.1:8000