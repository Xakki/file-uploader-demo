# file-uploader — standalone demo

A non-Laravel, plain-PHP backend built on `xakki/file-uploader` core, speaking
**[Upload Protocol v1](https://github.com/Xakki/file-uploader/blob/main/protocol/SPEC.md)** and serving the JS widget. It doubles as the
**custom / PSR-style usage example**: the only glue is a `ChunkPayload` adapter
([`src/PlainChunkPayload.php`](src/PlainChunkPayload.php)) and a tiny router
([`public/index.php`](public/index.php)) — all upload logic lives in the core.

Storage is switchable between the local **filesystem** and **S3** (MinIO), both via
`league/flysystem`, chosen by the `STORAGE` env var.

## Run

Everything runs through `make` (it wraps `docker compose`; PHP/Node aren't native). Run
`make help` for the full list.

```bash
cd file-uploader-demo

# filesystem (default)
make up            # nginx + php (local storage)

# S3 (MinIO)
make s3-up         # layers s3.yml over the env COMPOSE_FILE: php + minio + bucket
```

`make up` serves on the host port from `EXT_NGINX_PORT` in `.env` (currently
`127.0.0.1:80`, so `http://127.0.0.1:80`); `make info` prints the resolved URL and storage
mode. The live deployment is **https://file-uploader.xakki.ru**.

With `make s3-up`, the MinIO console is at http://127.0.0.1:9001 (`minioadmin` / `minioadmin`).

Open the app and use the ⇪ button (bottom-right) to upload. Other handy targets:
`make down` (stop + remove), `make logs` (last 200 lines, optional `name=php|nginx`),
`make ps` (status), `make bash` (shell in the php container),
`make gc` (runs the expiry GC once — see [Expiry & garbage collection](#expiry--garbage-collection)).

## Configuration

Upload behaviour is env-driven via [`src/Config.php`](src/Config.php) — set these in your
`.env` (defaults shown; leave unset to keep them). They are forwarded into the PHP container
by `docker-compose.yml`.

| Env var | Default | Meaning |
|---------|---------|---------|
| `FU_CHUNK_SIZE` | `1048576` (1 MiB) | Chunk size the widget slices files into. |
| `FU_MAX_SIZE` | `209715200` (200 MiB) | Max accepted upload size in bytes (`0` = unlimited). |
| `FU_MAX_FILES` | `100` | Cap on active (non-deleted) files (`0` = unlimited). |
| `FU_ALLOWED_EXTENSIONS` | `jpg,jpeg,png,gif,webp,svg,bmp,txt,csv,md` | Comma-separated allow-list — images + text. Use `*` to allow any. |
| `FU_TRASH_TTL_DAYS` | `30` | Days a soft-deleted file lingers before `cleanupTrash()` purges it. |
| `EXPIRE_MINUTES` | `60` | Minutes before an active guest file is hard-deleted by the GC (`0` = disabled). See [Expiry & garbage collection](#expiry--garbage-collection). |
| `GC_INTERVAL` | `300` | Seconds between GC runs in the `cron` container (compose-level var, not read by `Config.php`). |

The defaults above are the code defaults in [`src/Config.php`](src/Config.php) (`EXPIRE_MINUTES`
included; `GC_INTERVAL` is a compose/shell default in `docker-compose.yml`). Setting any of these
in `.env` overrides the code default — e.g. drop `FU_MAX_SIZE`/`FU_MAX_FILES` to tighten the demo
caps below 200 MiB / 100 files.

`FU_MAX_SIZE`, `FU_MAX_FILES`, `FU_ALLOWED_EXTENSIONS` and `FU_TRASH_TTL_DAYS` are enforced by
the core (`xakki/file-uploader`); `FU_CHUNK_SIZE` is advertised to the widget; `EXPIRE_MINUTES`
drives the demo's GC (`bin/gc.php`). Example `.env`:

```dotenv
FU_CHUNK_SIZE=1048576
FU_MAX_SIZE=209715200
FU_MAX_FILES=100
FU_ALLOWED_EXTENSIONS=jpg,jpeg,png,gif,webp,svg,bmp,txt,csv,md
FU_TRASH_TTL_DAYS=30
EXPIRE_MINUTES=60
GC_INTERVAL=300
```

## Local development

This repo is self-contained — no sibling checkout of the core is needed. The PHP core
`xakki/file-uploader` is a normal Composer dependency from Packagist (`^0.3`), and the JS
widget UMD bundle is pulled from the published [`@xakki/file-uploader`](https://www.npmjs.com/package/@xakki/file-uploader)
npm package by `make vendor-umd` into `public/file-uploader.umd.js` (gitignored). The widget
version is pinned by `FU_VERSION` in the [`Makefile`](Makefile) — bump it there to track a new
release.

## How it maps to the core

| HTTP | Core call |
|------|-----------|
| `POST /file-upload/chunks` | `FileManager::handleChunk(PlainChunkPayload)` |
| `GET /file-upload/files` | `FileManager::list()` |
| `DELETE /file-upload/files/{id}` | `FileManager::delete($id)` |
| `POST /file-upload/files/{id}/restore` | `FileManager::restore($id)` |
| `DELETE /file-upload/trash/cleanup` | `FileManager::cleanupTrash()` |

The response envelope comes from the shared `Xakki\FileUploader\Protocol\ResponseFactory`,
so the wire shape is identical to the Laravel/Symfony bindings.

## Expiry & garbage collection

Guest files are not permanent. A separate **`cron` container** (defined in
[`docker-compose.yml`](docker-compose.yml)) loops [`bin/gc.php`](bin/gc.php) every
`GC_INTERVAL` seconds (default 300). The GC makes two passes, both gated on `EXPIRE_MINUTES`
(`0` = disabled, the GC is a no-op):

1. **Expire actives** — for each guest scope `uploads/<fu_sid>/`, hard-delete files whose
   metadata `createdAt` is older than `EXPIRE_MINUTES` (default 60).
2. **Sweep stale chunks** — drop orphaned `.chunks/` parts (by mtime) left by aborted uploads.

Everything is scoped per guest: the GC only ever touches paths under each
`uploads/<fu_sid>/`, never another visitor's files. Each run emits one structured `gc run`
JSON log line (removed files, swept chunks, scopes). Run it once on demand with `make gc`.

**Hard-delete vs. trash.** The GC's expiry is a *hard* delete — the file and its metadata are
removed outright. That is distinct from the user-facing **soft-delete**: deleting a file via
the UI moves it to `.trash`, where it lingers for `FU_TRASH_TTL_DAYS` until `cleanupTrash()`
purges it (and can be restored before then). The GC bypasses soft-delete to enforce the
time-based quota.

## Per-session isolation

There is no login. On the first request each visitor gets a random id in a long-lived
`fu_sid` cookie ([`src/Session.php`](src/Session.php)); the id is sanitized to a safe charset
since it becomes a path segment. Every visitor's files live under their own folder:

```
storage/uploads/<fu_sid>/         ← assembled files
storage/uploads/<fu_sid>/.meta    ← metadata   (so list/dedup are per session)
storage/uploads/<fu_sid>/.chunks  ← in-flight chunks
storage/uploads/<fu_sid>/.trash   ← soft-deleted
```

This is pure core config: the `Factory` builds the `FileManager` with the four directory keys
scoped to the session id and a `SessionUserResolver` ([the core's per-instance config seam](https://github.com/Xakki/file-uploader#per-user--per-session-isolation)),
so `list()`, dedup and `cleanupTrash()` only ever see the caller's own files. The `/dl`
download route is likewise confined to the caller's `uploads/<fu_sid>/` prefix.

The upload caps from [Configuration](#configuration) (`FU_MAX_FILES`, `FU_MAX_SIZE`,
`FU_ALLOWED_EXTENSIONS`) and the trash TTL all apply *per guest folder* — each `fu_sid` gets
its own quota, independent of every other visitor. The
[expiry GC](#expiry--garbage-collection) is per-folder too: a guest's active files are
auto-hard-deleted after `EXPIRE_MINUTES` (default 60 = 1h).
