# MET Plugin

Ez a plugin a MET Industry Kft. részére készült. Következő útmutató segít a helyi tesztelésben és a konfigurációban.

## Szükséges beállítások (WP options)
A plugin a következő WP option-okat használja (admin felület: Tartalom Ügynök -> Beállítások):

- `gemrag_google_project_id` — Google Cloud project ID (pl. `my-project-123`)
- `gemrag_service_account_path` — Service account JSON fájl elérési útja a szerveren (pl. `/var/www/private/service-account.json`)
- `gemrag_pinecone_api_key` — Pinecone API kulcs
- `gemrag_pinecone_host` — Pinecone host (index endpoint), pl. `index-name-12345.svc.host.pinecone.io`

## Lokális tesztelés (Docker)
1. Indítsd el a környezetet:

```bash
docker compose -f docker-compose.test.yml up -d --build
```

2. Futtasd a setup scriptet, ami telepíti a WP-t és aktiválja a plugint:

```bash
bash scripts/wp-setup.sh
```

3. Nyisd meg az admin felületet: `http://localhost:8000/wp-admin` (user: `admin`, pass: `password`).

## Megjegyzések
- A plugin a `vendor/autoload.php`-t betölti, ha a `vendor/` mappa elérhető (composer install után). Győződj meg róla, hogy a szükséges PHP csomagok telepítve vannak: `composer install` a projekt gyökérében.
- A Gemini RAG funkciók használatához a Google Service Accountnak megfelelő engedélyekkel kell rendelkeznie (Vertex AI használata).
 - A plugin a `vendor/autoload.php`-t betölti, ha a `vendor/` mappa elérhető (composer install után). Győződj meg róla, hogy a szükséges PHP csomagok telepítve vannak: `composer install` a projekt gyökérében.
 - A Gemini RAG funkciók használatához a Google Service Accountnak megfelelő engedélyekkel kell rendelkeznie (Vertex AI használata).

## Következő lépések
- Unit tesztek hozzáadása a helper logika mockolt HTTP hívásokkal.
- Teljes OO-migráció: a helper osztály jelenleg implementálja a logikát; később lehetőség van további refaktorokra és tesztelésre.
