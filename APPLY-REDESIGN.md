# Apply the Staark theme redesign

Copy the files in this package over the same paths in the `staark-inc/wordpress` repository.

Then run:

```bash
git status
git diff --check
npm install
bash scripts/rc-smoke.sh
```

Start the WordPress environment if needed:

```bash
npm run dev
```

Recommended visual checks:

- 1440px
- 1024px
- 782px
- 600px
- 390px

Then commit:

```bash
git add README.md themes/staark
git commit -m "feat: redesign Staark block theme and add project README"
git push origin main
```

Important: this redesign intentionally does not modify `plugins/staark-core`, so the current Staark Hub RC remains isolated from the public theme redesign.
