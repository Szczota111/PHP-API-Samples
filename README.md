# Contractors.es PHP API Samples

Lightweight PHP examples showing how to communicate with the Contractors.es API. The package ships with a small `Api` helper built on top of Guzzle and a collection of self-contained scenarios (`test_*.php`) that cover CRM tasks, projects, orders, webhooks, and more.

## Requirements

- PHP 8.1+ with cURL and JSON extensions
- Composer
- Access to a Contractors.es environment (for example the public demo `https://demo.contractors.es`)

## Quick start

```bash
composer install
```

1. Adjust the credentials and language inside the constructor `new Api(<url>, <login>, <password>, <lang>)` in the scenario you plan to run.
2. Execute any script, e.g. `php test_tasks.php` or `php test_invoices.php`.
3. Responses and potential errors will be printed to your console; failed calls include the HTTP payload for easier debugging.

The access token is cached locally in `token_<hash>.txt`, so repeated runs do not re-authenticate unless you delete that file.

## Structure

- `api.php` – HTTP client with automatic login, pagination helpers, search shortcuts, and custom exceptions.
- `test_*.php` – standalone examples for tasks, orders, meetings, invoices, webhook management, etc.
- `webhook_endpoint.php` – minimal receiver you can host to validate webhook deliveries.

Official API documentation: [https://api.contractors.es](https://api.contractors.es).

## Customisation

- Pass your own headers or timeouts via the optional second argument when calling `get`, `post`, `put`, etc.
- Helper methods such as `getAll`, `getFirst`, `searchAll`, `create`, and `update` wrap common CRUD flows.
- Remove `token_<hash>.txt` if you need to force a fresh login.

## License

Released under the MIT License – see `LICENSE`.

---

## Wersja polska

Lekki zestaw przykładów w PHP pokazujący, jak pracować z API Contractors.es. Repozytorium zawiera klasę `Api` opartą na Guzzle oraz kilkanaście niezależnych scenariuszy `test_*.php` obejmujących CRM, projekty, zamówienia czy webhooki.

### Wymagania

- PHP 8.1+ z włączonymi rozszerzeniami cURL i JSON
- Composer
- Dostęp do instancji Contractors.es (np. demo `https://demo.contractors.es`)

### Szybki start

```bash
composer install
```

1. Uzupełnij login, hasło i język w konstruktorze `new Api(<url>, <login>, <hasło>, <lang>)` w wybranym pliku `test_*.php`.
2. Uruchom scenariusz, np. `php test_tasks.php` albo `php test_invoices.php`.
3. Odpowiedzi API pojawią się w konsoli; w przypadku błędów zobaczysz również pełen payload HTTP.

Token dostępowy jest cache'owany lokalnie w pliku `token_<hash>.txt`, dzięki czemu kolejne uruchomienia nie wymagają ponownego logowania.

### Struktura

- `api.php` – klient HTTP z auto-logowaniem, paginacją, wyszukiwaniem i własnymi wyjątkami.
- `test_*.php` – pojedyncze przykłady dotyczące zadań, zamówień, spotkań, faktur, webhooków itd.
- `webhook_endpoint.php` – prosty endpoint do szybkiego testowania webhooków.

Dokumentacja API: [https://api.contractors.es](https://api.contractors.es).

### Dostosowanie

- Własne nagłówki lub timeouty możesz przekazać jako drugi argument (tablica opcji) dla metod `get`, `post`, `put`, itp.
- Funkcje pomocnicze `getAll`, `getFirst`, `searchAll`, `create`, `update` skracają typowe operacje CRUD.
- Aby wymusić nowe logowanie, usuń plik `token_<hash>.txt` przypisany do danego URL i użytkownika.

### Licencja

Kod udostępniono na licencji MIT – szczegóły w `LICENSE`.
