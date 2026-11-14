# Agent notes

Live WebGL ocean wave simulation driven by SmartAtlantic ERDDAP buoy data (St. John's). Canonical at art.adamsimms.xyz/waves/. Prefer one issue per chat when the work is mechanical.

Canonical files: [README.md](README.md), [CHANGELOG.md](CHANGELOG.md), `index.php`, `lib/`, `call-api.php`.

## Private material

Private notes live in the private studio repo (adamsimms/studio). Nothing from studio comes into this repo unless Adam approves it. Never put a phone number or home address here; a global pre-commit guard blocks them.

## Bibliography / Zotero

Zotero is the source library (public library; no project collection yet, ask Adam). Writing to Zotero is publishing: only approved batches. Do not query `~/Zotero/zotero.sqlite`. Do not invent citations.

## Hard rules

- Do not invent data sources or simulation behavior that is not in the README or code.
- The site deploys from this repo and an uptime check runs every 30 minutes (`.github/workflows/`); keep `call-api` and `health` responses compatible.
- Secrets only via `.env`; see `.env.example`.

Docs policy and the decisions rule (work decision vs proposal choice vs option): [`.cursor/rules/docs.mdc`](.cursor/rules/docs.mdc).

## GitHub

Issues here are about the work. Applications, calls and their deadlines are tracked in the private studio repo, not here. Issue titles have no kind prefix; kind lives on labels.

## Project media

Photos, renders, sound and video never stay in this repo's ignored folders: they are not backed up. They go to the project's media folder, `$MEDIA_DIR` (`30 STUDIO/32 PROJECTS/WAVES/`), a local outbox that a daily job copies to off-site storage and clears 14 days after the copies are verified. Older media is fetched from that storage, not read back from `$MEDIA_DIR`. Stages: `01-REFERENCES`, `02-STUDIES`, `03-TESTS`, `04-ARTWORK`, `05-DOCUMENTATION`. Inside 02 and 03, one dated folder per piece of work: `<YYYY-MM>-<NAME>`.

## Support

Funder acknowledgements are not recorded here yet. Ask Adam before adding any.
