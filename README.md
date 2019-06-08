# Checklists REST API

## Installation

- clone this repo
    ```bash
    git clone https://github.com/fzldn/lumen-checklists.git
    cd lumen-checklists
    ```
- make database `checklist`
- copy `.env.example` to `.env`
- edit `.env`
    ```
    DB_DATABASE=checklist
    DB_USERNAME={your_db_user}
    DB_PASSWORD={your_db_password}
    ```
- run composer
    ```bash
    composer install
    ```
- database migrate and seed
    ```bash
    php artisan migrate --seed
    ```
- run app
    ```bash
    php -S localhost:8000 -t public
    ```

## Authentication
Use random apiKey in Header, example:
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImJjMzg2NTBi
```


## Tests

- make database `checklist_tests`
- run tests
    ```bash
    vendor/bin/phpunit
    ```
