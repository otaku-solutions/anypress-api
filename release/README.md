# Release images

The four Coolify apps built from this repository never build on the server. GitHub Actions builds each one's image
(`.github/workflows/image.yml`, started by the app's own workflow when its files change on `main`), publishes it to
GHCR as `ghcr.io/otaku-solutions/<app>:sha-<commit>`, then moves the branch `release/<app>` to that commit.

| Coolify app | Image and branch | Workflow | Built from |
| --- | --- | --- | --- |
| waas-api (api.anypress.io) | `anypress-api` | `api-image.yml` | `Dockerfile` |
| anypress-assistant | `anypress-assistant` | `assistant-image.yml` | `agent/Dockerfile` |
| anypress-assistant-panel | `anypress-assistant-panel` | `panel-image.yml` | `agent/panel/Dockerfile` |
| anypress-demo-playground (demo.anypress.site) | `anypress-demo-playground` | `playground-image.yml` | `playground/Dockerfile` |

Each app in Coolify deploys from its `release/<app>` branch with `/release/<app>.Dockerfile`, base directory `/`, and
"Include source commit in build" on. That Dockerfile only names the published image for the commit Coolify passes in as
`SOURCE_COMMIT`, so a deploy downloads the image and builds nothing. Because the branch moves only after the image is
published, Coolify's GitHub webhook never deploys a commit whose image is missing.

To redeploy without a code change, run the app's workflow from the Actions tab (`workflow_dispatch`): it publishes the
image for the current `main` and moves the branch. The packages are public, like the repository, so the server needs no
registry login.
