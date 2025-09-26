# Copilot Instructions for MET-Plugin

## Áttekintés
Ez a projekt egy WordPress plugin a MET Industry Kft. számára. A cél egy bővíthető, jól strukturált, WordPress-kompatibilis plugin fejlesztése, amely megfelel a vállalat igényeinek.

## Főbb irányelvek
- **WordPress szabványok**: Kövesd a WordPress plugin fejlesztési irányelveit (fájlszerkezet, elnevezések, hook-ok, filterek, nemzetköziesítés).
- **Fő plugin fájl**: A fő belépési pont legyen a plugin gyökérkönyvtárában, pl. `met-plugin.php`.
- **OOP preferált**: Használj osztályokat a logika szervezésére, kerüld a globális függvényeket, ahol lehetséges.
- **Nemzetköziesítés**: Használd a WordPress `__()` és `_e()` függvényeit a szövegek fordíthatóságához.
- **Biztonság**: Minden bemenetet validálj és szűrj, használj `nonce`-okat az űrlapoknál, és WordPress beépített escape függvényeit.
- **Külső függőségek**: Csak szükség esetén, lehetőleg Composer-rel vagy WordPress-hez illeszkedő módon.

## Fejlesztői workflow
- **Fejlesztés**: Minden új funkció külön osztályba/fájlba kerüljön, a fő plugin fájl csak inicializáljon.
- **Tesztelés**: Manuális tesztelés WordPress helyi környezetben (pl. LocalWP, Docker).
- **Build**: Ha szükséges, használj build scriptet (pl. SCSS/JS fordítás), de a PHP kód legyen önmagában futtatható.
- **Dokumentáció**: Minden publikus osztályhoz és metódushoz írj rövid PHPDoc-ot.

## Példák
- Fő plugin fájl: `met-plugin.php`
- Osztályok: `includes/Met_Plugin_Admin.php`, `includes/Met_Plugin_Public.php`
- Aktiválás/deaktiválás: `register_activation_hook`, `register_deactivation_hook`
- Hook használat: `add_action('admin_menu', [...])`

## Fontos fájlok
- `README.md`: Projektleírás, célok
- `.github/copilot-instructions.md`: AI fejlesztési irányelvek (ez a fájl)

## Egyéb
- Tartsd be a WordPress kódolási szabályokat: https://developer.wordpress.org/coding-standards/wordpress-coding-standards/
- Kérdés esetén nézd meg a WordPress Plugin Handbook-ot: https://developer.wordpress.org/plugins/
